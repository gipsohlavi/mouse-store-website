<?php
session_start();
require 'common.php';

// ログインチェック
if (!isset($_SESSION['customer'])) {
    header('Location: login-input.php');
    exit;
}

// カートの中身チェック
if (!isset($_SESSION['product']) || empty($_SESSION['product'])) {
    header('Location: cart-show.php');
    exit;
}

// 必須項目チェック
if (!isset($_POST['payment_method']) || !isset($_POST['agree_terms'])) {
    header('Location: purchase-input.php');
    exit;
}

// 購入処理の実行
$purchase_success = false;
$error_message = '';
$purchase_info = [];

try {
    $pdo->beginTransaction();
    
    $customer_id = $_SESSION['customer']['id'];
    
    // 顧客の現在のポイントを取得
    $customer_sql = $pdo->prepare('SELECT point FROM customer WHERE id = ?');
    $customer_sql->bindParam(1, $customer_id, PDO::PARAM_INT);
    $customer_sql->execute();
    $customer_data = $customer_sql->fetch();
    $current_points = $customer_data ? $customer_data['point'] : 0;
    
    // 使用ポイントの取得と検証
    $use_points = 0;
    if (isset($_POST['final_use_points']) && is_numeric($_POST['final_use_points'])) {
        $use_points = max(0, min(intval($_POST['final_use_points']), $current_points));
    }
    
    // 配送先情報の取得
    $shipping_sql = $pdo->prepare('
        SELECT sa.region_id, sa.remote_island_check 
        FROM shipping_addresses sa
        WHERE sa.customer_id = ? AND sa.is_default = 1 
        LIMIT 1
    ');
    $shipping_sql->bindParam(1, $customer_id, PDO::PARAM_INT);
    $shipping_sql->execute();
    $shipping_info = $shipping_sql->fetch();
    
    $customer_region_id = $shipping_info ? $shipping_info['region_id'] : 3;
    $customer_remote_island = $shipping_info ? $shipping_info['remote_island_check'] : 0;
    
    // 送料計算
    $base_shipping_fee = 0;
    if ($customer_region_id) {
        $postage_sql = $pdo->prepare('SELECT postage_fee FROM postage WHERE region_id = ? AND start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) ORDER BY start_date DESC LIMIT 1');
        $postage_sql->bindParam(1, $customer_region_id, PDO::PARAM_INT);
        $postage_sql->execute();
        $postage_info = $postage_sql->fetch();
        if ($postage_info) {
            $base_shipping_fee = $postage_info['postage_fee'];
        }
    }
    
    $remote_island_fee = 0;
    if ($customer_remote_island) {
        $remote_sql = $pdo->prepare('SELECT remote_island_fee FROM postage_remote_island WHERE start_date <= CURDATE() AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY start_date DESC LIMIT 1');
        $remote_sql->execute();
        $remote_info = $remote_sql->fetch();
        if ($remote_info) {
            $remote_island_fee = $remote_info['remote_island_fee'];
        }
    }
    
    // 送料無料判定
    $total_amount = 0;
    $purchased_products = [];
    foreach ($_SESSION['product'] as $product_data) {
        // 商品詳細情報も取得して購入商品リストに保存
        $product_sql = $pdo->prepare('SELECT * FROM product WHERE id = ?');
        $product_sql->bindParam(1, $product_data['id']);
        $product_sql->execute();
        $product_detail = $product_sql->fetch();
        
        $purchased_products[] = [
            'product' => $product_detail,
            'quantity' => $product_data['count'],
            'unit_price' => $product_data['price'],
            'subtotal' => $product_data['price'] * $product_data['count']
        ];
        
        $total_amount += $product_data['price'] * $product_data['count'];
    }
    
    $free_shipping_sql = $pdo->prepare('SELECT postage_fee_free FROM postage_free WHERE start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) AND del_kbn = 0 ORDER BY start_date DESC LIMIT 1');
    $free_shipping_sql->execute();
    $free_shipping_info = $free_shipping_sql->fetch();
    $free_shipping_threshold = $free_shipping_info ? $free_shipping_info['postage_fee_free'] : 0;
    
    $is_free_shipping = ($free_shipping_threshold > 0 && $total_amount >= $free_shipping_threshold);
    $final_shipping_fee = $is_free_shipping ? 0 : ($base_shipping_fee + $remote_island_fee);
    
    // 税額計算
    $tax_total = 0;
    $tax_details = [];
    foreach ($_SESSION['product'] as $product_data) {
        $tax_sql = $pdo->prepare('SELECT tax FROM tax WHERE tax_id = ?');
        $tax_sql->bindParam(1, $product_data['tax'], PDO::PARAM_INT);
        $tax_sql->execute();
        $tax_info = $tax_sql->fetch();
        
        if ($tax_info) {
            $subtotal = $product_data['price'] * $product_data['count'];
            $tax_id = $product_data['tax'];
            
            if (!isset($tax_details[$tax_id])) {
                $tax_details[$tax_id] = [
                    'rate' => $tax_info['tax'],
                    'subtotal' => $subtotal,
                    'tax_amount' => round($subtotal * $tax_info['tax'])
                ];
            } else {
                $tax_details[$tax_id]['subtotal'] += $subtotal;
                $tax_details[$tax_id]['tax_amount'] = round($tax_details[$tax_id]['subtotal'] * $tax_info['tax']);
            }
        }
    }
    
    foreach ($tax_details as $tax_detail) {
        $tax_total += $tax_detail['tax_amount'];
    }
    
    // 最終合計金額の計算
    $grand_total = $total_amount + $tax_total + $final_shipping_fee - $use_points;
    
    // 購入データの挿入
    $purchase_sql = $pdo->prepare('
    INSERT INTO purchase (customer_id, address_id, purchase_date, grand_total, get_point, use_point, postage_id, postage_free_id, remort_island_fee_id) 
    VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?)
');
    
    // ポイント付与計算
    $get_points = 0;
    
    // 基本ポイント付与率を取得
    $basic_rate_sql = $pdo->prepare('SELECT campaign_point_rate FROM point_campaign WHERE point_campaign_id = 1');
    $basic_rate_sql->execute();
    $basic_rate_data = $basic_rate_sql->fetch();
    $basic_rate = $basic_rate_data ? $basic_rate_data['campaign_point_rate'] : 0.01;
    
    foreach ($_SESSION['product'] as $product_data) {
        $product_total = $product_data['price'] * $product_data['count'];
        $product_points = floor($product_total * $basic_rate);
        
        // キャンペーンポイントをチェック
        $campaign_sql = $pdo->prepare('
            SELECT pc.campaign_point_rate 
            FROM point_campaign pc
            INNER JOIN campaign_target ct ON ct.point_campaign_id = pc.point_campaign_id 
            WHERE pc.point_campaign_id != 1 
            AND pc.del_kbn = 0 
            AND pc.start_date <= NOW()
            AND pc.end_date > NOW()
            AND ct.target_id = ?
            AND ct.del_kbn = 0
        ');
        $campaign_sql->bindParam(1, $product_data['id']);
        $campaign_sql->execute();
        $campaign_rate = $campaign_sql->fetch();
        
        if ($campaign_rate) {
            $campaign_points = floor($product_total * $campaign_rate['campaign_point_rate']);
            $product_points += $campaign_points;
        }
        
        $get_points += $product_points;
    }
    
// 配送先情報の取得（購入時点での情報を確実に保存）
$selected_address_id = $_SESSION['selected_shipping_address'] ?? null;

if (!$selected_address_id) {
    // デフォルト配送先を取得
    $default_addr_sql = $pdo->prepare('SELECT id FROM shipping_addresses WHERE customer_id = ? AND is_default = 1 LIMIT 1');
    $default_addr_sql->execute([$customer_id]);
    $default_addr = $default_addr_sql->fetch();
    $selected_address_id = $default_addr ? $default_addr['id'] : 0;
}

// 選択された配送先の詳細を取得
$shipping_sql = $pdo->prepare('
    SELECT sa.*, r.region_id 
    FROM shipping_addresses sa
    LEFT JOIN region r ON r.prefectures_id = (
        SELECT master_id FROM master WHERE kbn = 12 AND name = sa.prefecture
    )
    WHERE sa.id = ? AND sa.customer_id = ?
');
$shipping_sql->execute([$selected_address_id, $customer_id]);
$shipping_info = $shipping_sql->fetch();

// 変数に値を代入してからbindParam()を使用
$sql = $pdo->prepare('SELECT postage_fee_free_id FROM postage_free 
                    WHERE (start_date <= ? 
                    AND end_date > ?
                    AND del_kbn = 0 ) 
                    OR end_date IS NULL 
                    ORDER BY postage_fee_free_id DESC LIMIT 1');
$sql->bindParam(1, $today);
$sql->bindParam(2, $today);
$sql->execute();
$postage_free_id = $sql->fetch();
$remote_island_fee_id = $customer_remote_island ? 1 : 0;

$purchase_sql->bindParam(1, $customer_id);
$purchase_sql->bindParam(2, $selected_address_id);  // ←これを追加
$purchase_sql->bindParam(3, $grand_total);
$purchase_sql->bindParam(4, $get_points);
$purchase_sql->bindParam(5, $use_points);
$purchase_sql->bindParam(6, $customer_region_id);
$purchase_sql->bindParam(7, $postage_free_id[0]);
$purchase_sql->bindParam(8, $remote_island_fee_id);  // ←番号がずれるので修正
$purchase_sql->execute();
    $purchase_id = $pdo->lastInsertId();

    
    // 購入詳細の挿入
    $detail_counter = 1;
    foreach ($_SESSION['product'] as $product_data) {
        $detail_sql = $pdo->prepare('
            INSERT INTO purchase_detail (purchase_id, product_id, count, purchase_detail_id, unit_price, total) 
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $detail_total = $product_data['price'] * $product_data['count'];
        
        // 変数に値を代入
        $product_id = $product_data['id'];
        $product_count = $product_data['count'];
        $purchase_detail_id = $detail_counter;
        $unit_price = $product_data['price'];
        
        $detail_sql->bindParam(1, $purchase_id);
        $detail_sql->bindParam(2, $product_id);
        $detail_sql->bindParam(3, $product_count);
        $detail_sql->bindParam(4, $purchase_detail_id);
        $detail_sql->bindParam(5, $unit_price);
        $detail_sql->bindParam(6, $detail_total);
        $detail_sql->execute();
        
        $detail_counter++;
    }
    
    // 税額詳細の挿入
    foreach ($tax_details as $tax_id => $tax_detail) {
        $tax_total_sql = $pdo->prepare('
            INSERT INTO tax_total (id, tax_id, tax_amount, sub_total) 
            VALUES (?, ?, ?, ?)
        ');
        
        // 変数に値を代入
        $tax_id_value = $tax_id;
        $tax_amount = $tax_detail['tax_amount'];
        $sub_total = $tax_detail['subtotal'];
        
        $tax_total_sql->bindParam(1, $purchase_id);
        $tax_total_sql->bindParam(2, $tax_id_value);
        $tax_total_sql->bindParam(3, $tax_amount);
        $tax_total_sql->bindParam(4, $sub_total);
        $tax_total_sql->execute();
    }
    // 顧客の購入後ポイント
    $new_points = $current_points - $use_points + $get_points;

    // ポイント使用の記録
if ($use_points > 0) {
    $purchase_order_num = 'KEL' . str_pad($purchase_id, 6, '0', STR_PAD_LEFT);
    recordPointHistory($pdo, $customer_id, -$use_points, 'use', '購入時ポイント使用 (注文番号: ' . $purchase_order_num . ')', $purchase_id);
}

// ポイント獲得の記録
if ($get_points > 0) {
    $purchase_order_num = 'KEL' . str_pad($purchase_id, 6, '0', STR_PAD_LEFT);
    recordPointHistory($pdo, $customer_id, $get_points, 'purchase', '購入による獲得 (注文番号: ' . $purchase_order_num . ')', $purchase_id);
}
    
    // 在庫数更新
    foreach ($_SESSION['product'] as $product_data) {
        $stock_sql = $pdo->prepare('
            UPDATE product 
            SET stock_quantity = stock_quantity - ?, sales_quantity = sales_quantity + ? 
            WHERE id = ?
        ');
        
        // 変数に値を代入
        $product_count = $product_data['count'];
        $product_id = $product_data['id'];
        
        $stock_sql->bindParam(1, $product_count);
        $stock_sql->bindParam(2, $product_count);
        $stock_sql->bindParam(3, $product_id);
        $stock_sql->execute();
    }
    
    $pdo->commit();
    
    // セッション情報の更新
    $_SESSION['customer']['point'] = $new_points;
    
    // 購入成功情報の設定
    $purchase_info = [
        'purchase_id' => $purchase_id,
        'total_amount' => $total_amount,
        'tax_total' => $tax_total,
        'shipping_fee' => $final_shipping_fee,
        'use_points' => $use_points,
        'get_points' => $get_points,
        'grand_total' => $grand_total,
        'new_points' => $new_points,
        'purchased_products' => $purchased_products,
        'payment_method' => $_POST['payment_method'],
        'is_free_shipping' => $is_free_shipping
    ];
    
    $purchase_success = true;
    
    // カートのクリア
    unset($_SESSION['product']);
    unset($_SESSION['use_points']);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Purchase error: " . $e->getMessage());
    $error_message = '注文処理中にエラーが発生しました。もう一度お試しください。';
}

// エラーが発生した場合は購入画面にリダイレクト
if (!$purchase_success) {
    $_SESSION['error'] = $error_message;
    header('Location: purchase-input.php');
    exit;
}

// 成功時は以下で完了画面を表示
require 'header.php';
?>

<div class="purchase-complete-container">
    <div class="success-animation">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="confetti"></div>
    </div>

    <div class="success-content">
        <h1 class="success-title">
            <i class="fas fa-party-horn"></i>
            ご注文ありがとうございました！
        </h1>
        <p class="success-subtitle">
            注文ID: <strong>#<?= str_pad($purchase_info['purchase_id'], 8, '0', STR_PAD_LEFT) ?></strong>
        </p>
        <div class="order-time">
            <i class="fas fa-clock"></i>
            注文日時: <?= date('Y年m月d日 H:i') ?>
        </div>
    </div>

    <!-- 購入商品一覧 -->
    <div class="purchased-items-card">
        <h2 class="card-title">
            <i class="fas fa-shopping-bag"></i>
            ご購入商品
        </h2>
        
        <div class="purchased-items-list">
            <?php foreach ($purchase_info['purchased_products'] as $item): ?>
                <div class="purchased-item">
                    <div class="item-image">
                        <?php
                        $images = getImage($item['product']['id'], $pdo);
                        $image_path = "images/{$images[0]}.jpg";
                        if (!file_exists($image_path)) {
                            $image_path = "images/no-image.jpg";
                        }
                        ?>
                        <img src="<?= $image_path ?>" alt="<?= h($item['product']['name']) ?>">
                    </div>
                    <div class="item-details">
                        <h4 class="item-name"><?= h($item['product']['name']) ?></h4>
                        <div class="item-specs">
                            <span class="spec">重量: <?= $item['product']['weight'] ?>g</span>
                            <span class="spec">DPI: <?= number_format($item['product']['dpi_max']) ?></span>
                        </div>
                        <div class="item-price-info">
                            <span class="unit-price">¥<?= number_format($item['unit_price']) ?> × <?= $item['quantity'] ?>個</span>
                        </div>
                    </div>
                    <div class="item-total">
                        ¥<?= number_format($item['subtotal']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 注文詳細カード -->
    <div class="purchase-summary-card">
        <h2 class="card-title">
            <i class="fas fa-receipt"></i>
            ご注文内容
        </h2>
        
        <div class="summary-details">
            <div class="summary-row">
                <span class="summary-label">商品合計</span>
                <span class="summary-value">¥<?= number_format($purchase_info['total_amount']) ?></span>
            </div>
            
            <div class="summary-row">
                <span class="summary-label">消費税等</span>
                <span class="summary-value">¥<?= number_format($purchase_info['tax_total']) ?></span>
            </div>
            
            <div class="summary-row">
                <span class="summary-label">配送料</span>
                <span class="summary-value">
                    <?php if ($purchase_info['shipping_fee'] > 0): ?>
                        ¥<?= number_format($purchase_info['shipping_fee']) ?>
                    <?php else: ?>
                        <span class="free-label">無料</span>
                    <?php endif; ?>
                </span>
            </div>
            
            <?php if ($purchase_info['use_points'] > 0): ?>
            <div class="summary-row points-discount">
                <span class="summary-label">
                    <i class="fas fa-coins"></i>
                    ポイント割引
                </span>
                <span class="summary-value discount-value">
                    -¥<?= number_format($purchase_info['use_points']) ?>
                </span>
            </div>
            <?php endif; ?>
            
            <div class="summary-row total-row">
                <span class="summary-label">お支払い総額</span>
                <span class="summary-value total-amount">¥<?= number_format($purchase_info['grand_total']) ?></span>
            </div>
        </div>
    </div>

    <!-- ポイント情報カード -->
    <div class="points-info-card">
        <h2 class="card-title">
            <i class="fas fa-coins"></i>
            ポイント情報
        </h2>
        
        <div class="points-celebration">
            <div class="points-animation">
                <div class="coin-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="points-earned">
                    +<?= number_format($purchase_info['get_points']) ?> P
                </div>
                <div class="points-message">獲得！</div>
            </div>
        </div>
        
        <div class="points-breakdown">
            <?php if ($purchase_info['use_points'] > 0): ?>
            <div class="points-row used-points">
                <div class="points-icon">
                    <i class="fas fa-minus-circle"></i>
                </div>
                <div class="points-details">
                    <div class="points-label">今回使用したポイント</div>
                    <div class="points-value">-<?= number_format($purchase_info['use_points']) ?> P</div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="points-row earned-points">
                <div class="points-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="points-details">
                    <div class="points-label">今回獲得したポイント</div>
                    <div class="points-value">+<?= number_format($purchase_info['get_points']) ?> P</div>
                </div>
            </div>
            
            <div class="points-row current-points">
                <div class="points-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="points-details">
                    <div class="points-label">現在の保有ポイント</div>
                    <div class="points-value total-points"><?= number_format($purchase_info['new_points']) ?> P</div>
                </div>
            </div>
        </div>
        
        <div class="points-note">
            <i class="fas fa-info-circle"></i>
            <span>ポイントは1P = 1円として次回のお買い物でご利用いただけます</span>
        </div>
    </div>

    <!-- 配送・お支払い情報 -->
    <div class="delivery-payment-info">
        <div class="info-card">
            <h3 class="info-title">
                <i class="fas fa-truck"></i>
                配送について
            </h3>
            <div class="info-content">
                <p><strong>お届け予定：</strong> 2-3営業日後</p>
                <p><strong>配送業者：</strong> ヤマト運輸・佐川急便</p>
                <p><strong>追跡番号：</strong> 商品発送後にメールでお知らせいたします</p>
                <?php if ($purchase_info['is_free_shipping']): ?>
                    <p class="free-shipping-notice">
                        <i class="fas fa-check-circle"></i>
                        <strong>送料無料でお届けします！</strong>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="info-card">
            <h3 class="info-title">
                <i class="fas fa-credit-card"></i>
                お支払いについて
            </h3>
            <div class="info-content">
                <p><strong>お支払い方法：</strong> <?php
                    switch($purchase_info['payment_method']) {
                        case 'credit_card':
                            echo 'クレジットカード';
                            break;
                        case 'bank_transfer':
                            echo '銀行振込';
                            break;
                        case 'cod':
                            echo '代金引換';
                            break;
                        default:
                            echo 'クレジットカード';
                    }
                ?></p>
                <p><strong>決済状況：</strong> 処理完了</p>
            </div>
        </div>
    </div>

    <!-- 次のアクション -->
    <div class="next-actions">
        <div class="action-buttons">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i>
                トップページに戻る
            </a>
            <a href="product.php" class="btn btn-outline">
                <i class="fas fa-shopping-bag"></i>
                買い物を続ける
            </a>
            <a href="history.php" class="btn btn-outline">
                <i class="fas fa-history"></i>
                注文履歴を見る
            </a>
        </div>
    </div>

    <!-- サポート情報 -->
    <div class="support-info">
        <div class="support-card">
            <h3 class="support-title">
                <i class="fas fa-headset"></i>
                ご不明な点がございましたら
            </h3>
            <div class="support-content">
                <p>お問い合わせ先：<strong>0120-123-456</strong></p>
                <p>営業時間：平日 9:00-18:00</p>
                <p>メール：<a href="mailto:support@example.com">support@example.com</a></p>
            </div>
        </div>
    </div>
</div>

<style>
.purchase-complete-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px;
    text-align: center;
}

.success-animation {
    position: relative;
    margin-bottom: 40px;
}

.success-icon {
    font-size: 5rem;
    color: #10b981;
    margin-bottom: 20px;
    animation: successPulse 2s ease-in-out;
}

@keyframes successPulse {
    0% { transform: scale(0.8); opacity: 0; }
    50% { transform: scale(1.1); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}

.success-content {
    margin-bottom: 40px;
}

.success-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.success-subtitle {
    font-size: 1.2rem;
    color: #6b7280;
    font-weight: 500;
    margin-bottom: 10px;
}

.order-time {
    color: #9ca3af;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

/* 購入商品一覧 */
.purchased-items-card,
.purchase-summary-card,
.points-info-card,
.delivery-payment-info,
.next-actions,
.support-info {
    background: white;
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #e5e7eb;
    text-align: left;
}

.card-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 2px solid #f3f4f6;
    padding-bottom: 15px;
}

.purchased-items-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.purchased-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.purchased-item .item-image {
    width: 80px;
    height: 80px;
    flex-shrink: 0;
}

.purchased-item .item-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 8px;
    background: white;
}

.purchased-item .item-details {
    flex: 1;
}

.item-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.item-specs {
    display: flex;
    gap: 15px;
    margin-bottom: 8px;
}

.spec {
    background: #e5e7eb;
    color: #6b7280;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
}

.item-price-info {
    color: #6b7280;
    font-size: 0.9rem;
}

.purchased-item .item-total {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1f2937;
}

/* ポイント獲得アニメーション */
.points-celebration {
    text-align: center;
    margin-bottom: 30px;
}

.points-animation {
    display: inline-block;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 20px;
    padding: 30px;
    position: relative;
    overflow: hidden;
    margin-bottom: 20px;
    animation: celebrationGlow 3s ease-in-out infinite;
}

@keyframes celebrationGlow {
    0%, 100% { box-shadow: 0 0 20px rgba(251, 191, 36, 0.4); }
    50% { box-shadow: 0 0 40px rgba(251, 191, 36, 0.6); }
}

.coin-icon {
    font-size: 3rem;
    color: white;
    margin-bottom: 15px;
    animation: coinBounce 2s ease-in-out infinite;
}

@keyframes coinBounce {
    0%, 100% { transform: translateY(0) rotateY(0deg); }
    25% { transform: translateY(-10px) rotateY(180deg); }
    75% { transform: translateY(-5px) rotateY(360deg); }
}

.points-earned {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.points-message {
    font-size: 1.2rem;
    color: white;
    font-weight: 600;
    margin-top: 5px;
}

/* その他のスタイル（既存のものを継承） */
.summary-details {
    margin-bottom: 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-label {
    color: #6b7280;
    font-weight: 500;
}

.summary-value {
    font-weight: 600;
    color: #1f2937;
}

.points-discount .summary-value {
    color: #059669;
}

.discount-value {
    color: #059669 !important;
}

.free-label {
    background: #dcfce7;
    color: #16a34a;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 0.9rem;
    font-weight: 500;
}

.total-row {
    border-top: 2px solid #e5e7eb !important;
    border-bottom: none !important;
    padding-top: 15px !important;
    margin-top: 15px;
}

.total-row .summary-label {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
}

.total-amount {
    font-size: 1.4rem !important;
    font-weight: 700 !important;
    color: #2563eb !important;
}

/* ポイント情報スタイル */
.points-breakdown {
    margin-bottom: 20px;
}

.points-row {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #f3f4f6;
}

.points-row:last-child {
    border-bottom: none;
}

.points-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.used-points .points-icon {
    background: #fee2e2;
    color: #dc2626;
}

.earned-points .points-icon {
    background: #dcfce7;
    color: #16a34a;
}

.current-points .points-icon {
    background: #dbeafe;
    color: #2563eb;
}

.points-details {
    flex: 1;
}

.points-label {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 4px;
}

.points-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1f2937;
}

.total-points {
    font-size: 1.4rem !important;
    color: #2563eb !important;
}

.points-note {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0369a1;
    font-size: 0.9rem;
}

/* 配送・支払い情報 */
.delivery-payment-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    padding: 30px;
    background: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    margin-bottom: 30px;
}

.info-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 30px;  /* パディングを増やす */
    border: 1px solid #e2e8f0;
    min-height: 180px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.info-title {
    font-size: 1.3rem;  /* タイトルを大きく */
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 20px;  /* 余白を増やす */
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 2px solid #e5e7eb;  /* 境界線を追加 */
    padding-bottom: 12px;
}

.info-content p {
    margin-bottom: 12px;  /* 行間を広げる */
    color: #374151;  /* 文字色を濃く */
    font-size: 1rem;  /* フォントサイズを大きく */
    line-height: 1.6;  /* 行高を調整 */
}

.info-content strong {
    color: #1f2937;
    font-weight: 600;
    font-size: 1.05rem;  /* 強調部分を少し大きく */
}

.free-shipping-notice {
    background: #dcfce7;
    color: #16a34a;
    padding: 10px 15px;
    border-radius: 8px;
    margin-top: 15px;
    font-weight: 500;
}

/* アクションボタン */
.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: #2563eb;
    color: white;
    border: none;
}

.btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
}

.btn-outline {
    background: white;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
    color: #374151;
}

/* サポート情報 */
.support-card {
    text-align: center;
    background: #f8fafc;
    border-radius: 12px;
    padding: 25px;
    border: 1px solid #e2e8f0;
}

.support-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.support-content {
    color: #6b7280;
}

.support-content p {
    margin-bottom: 5px;
}

.support-content a {
    color: #2563eb;
    text-decoration: none;
}

.support-content a:hover {
    text-decoration: underline;
}

/* 紙吹雪のアニメーション */
@keyframes confettiFall {
    0% {
        transform: translateY(-100vh) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(100vh) rotate(720deg);
        opacity: 0;
    }
}

.confetti-piece {
    position: absolute;
    animation: confettiFall linear infinite;
    pointer-events: none;
}

@keyframes fall {
    0% {
        transform: translateY(-100vh) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(100vh) rotate(720deg);
        opacity: 0;
    }
}

/* レスポンシブ */
@media (max-width: 768px) {
    .purchase-complete-container {
        padding: 20px 15px;
    }
    
    .success-title {
        font-size: 2rem;
        flex-direction: column;
        gap: 10px;
    }
    
    .delivery-payment-info {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
    
    .purchased-item {
        flex-direction: column;
        text-align: center;
    }
    
    .purchased-item .item-image {
        width: 120px;
        height: 120px;
    }
    
    .points-animation {
        padding: 20px;
    }
    
    .coin-icon {
        font-size: 2.5rem;
    }
    
    .points-earned {
        font-size: 2rem;
    }
}

/* 配送・支払い情報を読みやすく */
.delivery-payment-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    padding: 0;
    background: none;
    box-shadow: none;
    border: none;
    margin-bottom: 30px;
}

.info-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    position: relative;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

/* 2つ目のカードは少し違う色に */
.info-card:nth-child(2) {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
}

.info-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 12px;
}

.info-title i {
    color: #6b7280;
    font-size: 1.2rem;
}

.info-content p {
    margin-bottom: 12px;
    color: #374151;
    font-size: 1rem;
    line-height: 1.6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-content strong {
    color: #1f2937;
    font-weight: 600;
}

/* 値の部分を少し強調 */
.info-content p strong:last-child {
    color: #2563eb;
    font-weight: 700;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .delivery-payment-info {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .info-card {
        padding: 25px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 紙吹雪のアニメーション
    createConfetti();
    
    // ポイント獲得アニメーションの遅延実行
    setTimeout(() => {
        const pointsAnimation = document.querySelector('.points-animation');
        if (pointsAnimation) {
            pointsAnimation.style.animation = 'celebrationGlow 3s ease-in-out infinite';
        }
    }, 1000);
    
    // 成功メッセージの音効果（オプション）
    // playSuccessSound();
});

function createConfetti() {
    const confettiContainer = document.querySelector('.confetti');
    if (!confettiContainer) return;
    
    const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57', '#ff9ff3', '#54a0ff'];
    
    for (let i = 0; i < 60; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti-piece';
        confetti.style.width = Math.random() * 8 + 6 + 'px';
        confetti.style.height = Math.random() * 8 + 6 + 'px';
        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
        confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
        confetti.style.animationDelay = Math.random() * 2 + 's';
        
        confettiContainer.appendChild(confetti);
    }
    
    // 10秒後に紙吹雪を削除
    setTimeout(() => {
        confettiContainer.innerHTML = '';
    }, 10000);
}

// 成功音の再生（オプション）
function playSuccessSound() {
    // Web Audio APIを使用した簡単な成功音
    if (typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined') {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        
        // 成功音の作成
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(523.25, audioContext.currentTime); // C5
        oscillator.frequency.setValueAtTime(659.25, audioContext.currentTime + 0.2); // E5
        oscillator.frequency.setValueAtTime(783.99, audioContext.currentTime + 0.4); // G5
        
        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.6);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.6);
    }
}

// ページ離脱前にセッションクリーンアップの確認
window.addEventListener('beforeunload', function(e) {
    // 特別な処理は不要（PHPで既にセッションクリーンアップ済み）
});

// スムーススクロール（ページ内リンクがある場合用）
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

<?php require 'footer.php'; ?>