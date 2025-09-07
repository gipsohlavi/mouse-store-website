<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>


<div class="container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-history"></i>
            購入履歴
        </h1>
        <p class="page-description">これまでにご購入いただいた商品の履歴をご確認いただけます</p>
    </div>

    <?php
    if (isset($_SESSION['customer'])) {
        $sql_purchase = $pdo->prepare(
            'select * from purchase where customer_id=? order by id desc'
        );
        $sql_purchase->bindParam(1, $_SESSION['customer']['id']);
        $sql_purchase->execute();
        $purchases = $sql_purchase->fetchAll();
        
        if (count($purchases) > 0) {
            echo '<div class="history-container">';
            echo '<div class="history-header">';
            echo '<div class="history-summary">';
            echo '<div class="summary-item">';
            echo '<div class="summary-icon"><i class="fas fa-shopping-bag"></i></div>';
            echo '<div class="summary-content">';
            echo '<div class="summary-number">', count($purchases), '</div>';
            echo '<div class="summary-label">総注文数</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="purchase-history">';
            
            foreach ($purchases as $row_purchase) {
                // 購入時の配送先情報を取得（実際の購入時点での情報）
                $shipping_info_sql = $pdo->prepare('
                    SELECT sa.prefecture, sa.city, sa.address_line1, sa.address_line2, 
                           sa.region_id, sa.remote_island_check, sa.recipient_name
                    FROM shipping_addresses sa 
                    WHERE sa.id = ?
                    LIMIT 1
                ');
                $shipping_info_sql->execute([$row_purchase['address_id']]);
                $shipping_info = $shipping_info_sql->fetch();

                // address_idが0または無効な場合のフォールバック
                if (!$shipping_info) {
                    $shipping_info_sql = $pdo->prepare('
                        SELECT sa.prefecture, sa.city, sa.address_line1, sa.address_line2, 
                               sa.region_id, sa.remote_island_check, sa.recipient_name
                        FROM shipping_addresses sa 
                        WHERE sa.customer_id = ? AND sa.is_default = 1
                        LIMIT 1
                    ');
                    $shipping_info_sql->execute([$_SESSION['customer']['id']]);
                    $shipping_info = $shipping_info_sql->fetch();
                }

                // 配送料計算の初期化
                $shipping_fee = 0;
                $remote_island_fee = 0;
                $total_shipping_fee = 0;
                $is_free_shipping = false;
                
                if ($shipping_info) {
                    // 購入日時点での商品合計を計算
                    $cart_subtotal_sql = $pdo->prepare('
                        SELECT SUM(total) as subtotal 
                        FROM purchase_detail 
                        WHERE purchase_id = ?
                    ');
                    $cart_subtotal_sql->execute([$row_purchase['id']]);
                    $cart_subtotal_result = $cart_subtotal_sql->fetch();
                    $cart_subtotal = $cart_subtotal_result ? $cart_subtotal_result['subtotal'] : 0;
                    
                    // 送料無料基準額（現在の設定を使用）
                    $free_shipping_sql = $pdo->prepare('SELECT postage_fee_free FROM postage_free WHERE start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) AND del_kbn = 0 ORDER BY start_date DESC LIMIT 1');
                    $free_shipping_sql->execute();
                    $free_shipping_info = $free_shipping_sql->fetch();
                    $free_shipping_threshold = $free_shipping_info ? $free_shipping_info['postage_fee_free'] : 0;
                    
                    $is_free_shipping = ($free_shipping_threshold > 0 && $cart_subtotal >= $free_shipping_threshold);
                    
                    if (!$is_free_shipping) {
                        // 地域IDを都道府県名から動的解決（保存region_idの不整合対策）
                        $resolved_region_id = $shipping_info['region_id'];
                        try {
                            $pref_stmt = $pdo->prepare('SELECT master_id FROM master WHERE kbn = 12 AND name = ? LIMIT 1');
                            $pref_stmt->execute([$shipping_info['prefecture']]);
                            $pref_id = $pref_stmt->fetchColumn();
                            if ($pref_id) {
                                $region_stmt = $pdo->prepare('SELECT region_id FROM region WHERE prefectures_id = ? LIMIT 1');
                                $region_stmt->execute([$pref_id]);
                                $resolved = $region_stmt->fetchColumn();
                                if ($resolved) { $resolved_region_id = (int)$resolved; }
                            }
                        } catch (Exception $e) { /* noop */ }

                        // 基本送料（購入日時点の有効料金）
                        $postage_sql = $pdo->prepare('SELECT postage_fee FROM postage WHERE region_id = ? AND start_date <= ? AND (end_date IS NULL OR end_date > ?) ORDER BY start_date DESC LIMIT 1');
                        $postage_sql->execute([$resolved_region_id, $row_purchase['purchase_date'], $row_purchase['purchase_date']]);
                        $postage_info = $postage_sql->fetch();
                        if ($postage_info) {
                            $shipping_fee = $postage_info['postage_fee'];
                        }
                        
                        // 離島追加料金（購入日時点の有効料金）
                        if ($shipping_info['remote_island_check']) {
                            $remote_sql = $pdo->prepare('SELECT remote_island_fee FROM postage_remote_island WHERE start_date <= ? AND (end_date IS NULL OR end_date > ?) ORDER BY start_date DESC LIMIT 1');
                            $remote_sql->execute([$row_purchase['purchase_date'], $row_purchase['purchase_date']]);
                            $remote_info = $remote_sql->fetch();
                            if ($remote_info) {
                                $remote_island_fee = $remote_info['remote_island_fee'];
                            }
                        }
                    }
                    
                    $total_shipping_fee = $shipping_fee + $remote_island_fee;
                }
                
                echo '<div class="purchase-card">';
                echo '<div class="purchase-header">';
                echo '<h3>購入日時：', date('Y年m月d日 H:i', strtotime($row_purchase['purchase_date'])), '</h3>';
                echo '<div class="purchase-meta">';
                echo '<div class="order-id">注文番号: KEL', str_pad($row_purchase['id'], 6, '0', STR_PAD_LEFT), '</div>';
                if ($shipping_info) {
                    $shipping_address = $shipping_info['prefecture'] . $shipping_info['city'] . $shipping_info['address_line1'];
                    if ($shipping_info['address_line2']) {
                        $shipping_address .= ' ' . $shipping_info['address_line2'];
                    }
                    echo '<div class="shipping-address">';
                    echo '<i class="fas fa-map-marker-alt"></i> ';
                    echo h($shipping_info['recipient_name']) . ' 様 - ';
                    echo h($shipping_address);
                    echo '</div>';
                }
                echo '</div>';
                echo '</div>';
                
                // 商品詳細取得
                $sql_detail = $pdo->prepare(
                    'select product.id as product_id, product.name, purchase_detail.unit_price, purchase_detail.count, purchase_detail.total ' .
                        'from purchase_detail inner join product on purchase_detail.product_id = product.id ' .
                        'where purchase_id=?'
                );
                $sql_detail->bindParam(1, $row_purchase['id']);
                $sql_detail->execute();
                
                echo '<div class="purchase-table-container">';
                echo '<table class="purchase-table">';
                echo '<thead>';
                echo '<tr><th>商品番号</th><th></th><th>商品名</th><th>価格</th><th>個数</th><th>小計</th></tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($sql_detail as $row_detail) {
                    // 商品IDに基づいて画像パスを直接設定
                    $image_path = "images/{$row_detail['product_id']}.jpg";
                    if (!file_exists($image_path)) {
                        $image_path = "images/no-image.jpg";  // デフォルト画像
                    }
                    
                    echo '<tr>';
                    echo '<td>', $row_detail['product_id'], '</td>';
                    echo '<td class="image-cell"><img alt="', h($row_detail['name']), '" src="', $image_path, '"></td>';
                    echo '<td class="product-name-cell">';
                    echo '<a href="detail.php?id=', $row_detail['product_id'], '">', h($row_detail['name']), '</a>';
                    echo '</td>';
                    echo '<td class="price-cell">¥', number_format($row_detail['unit_price']), '</td>';
                    echo '<td class="quantity-cell">', $row_detail['count'], '</td>';
                    echo '<td class="total-cell">¥', number_format($row_detail['total']), '</td>';
                    echo '</tr>';
                }
                
                // 価格計算
                $prices = getPrices($row_purchase['id'], $pdo);
                
                // 消費税等
                $tax = [];
                $tax_total = 0;
                foreach ($prices as $row) {
                    // 税対象金額
                    array_push($tax, $row["sub_total"] - $row["tax_amount"]);
                    // 税額
                    $tax_total += (int)$row["tax_amount"];
                }
                
                // 商品小計
                $total = $prices[0]["total"];
                
                echo '<tr class="subtotal-row"><td colspan="5">商品小計</td><td>¥', number_format($total), '</td></tr>';
                echo '<tr class="tax-row"><td colspan="5">消費税等</td><td>¥', number_format($tax_total), '</td></tr>';
                
                if (isset($tax[0])) {
                    echo '<tr class="tax-detail-row"><td colspan="3"></td><td>', $prices[0]["tax"] * 100, '%対象 ¥', number_format($tax[0]), '</td><td>消費税</td><td>¥', number_format($prices[0]["tax_amount"]), '</td></tr>';
                }
                if (isset($tax[1])) {
                    echo '<tr class="tax-detail-row"><td colspan="3"></td><td>', $prices[1]["tax"] * 100, '%対象 ¥', number_format($tax[1]), '</td><td>消費税</td><td>¥', number_format($prices[1]["tax_amount"]), '</td></tr>';
                }
                
                // 送料表示
                echo '<tr class="shipping-row">';
                echo '<td colspan="5">';
                echo '配送料';
                if ($shipping_info && $shipping_info['remote_island_check']) {
                    echo '<span class="remote-island-badge">離島</span>';
                }
                echo '</td>';
                echo '<td>';
                if ($is_free_shipping) {
                    echo '<span class="free-shipping">無料</span>';
                } else {
                    echo '¥', number_format($total_shipping_fee);
                    if ($shipping_info && $shipping_info['remote_island_check'] && $remote_island_fee > 0) {
                        echo '<br><small class="shipping-breakdown">';
                        echo '基本: ¥', number_format($shipping_fee);
                        echo ' + 離島: ¥', number_format($remote_island_fee);
                        echo '</small>';
                    }
                }
                echo '</td>';
                echo '</tr>';
                
                // 総計（送料込み）
                $grand_total_with_shipping = $total + $tax_total + $total_shipping_fee;
                echo '<tr class="grand-total-row"><td colspan="5">合計</td><td>¥', number_format($grand_total_with_shipping), '</td></tr>';
                
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
                
                // アクションボタン
                echo '<div class="purchase-actions">';
                echo '<a href="#" class="btn-outline btn-sm">再注文</a>';
                echo '<a href="#" class="btn-outline btn-sm">レビューを書く</a>';
                echo '</div>';
                
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
            
        } else {
            // 購入履歴なし
            echo '<div class="empty-history">';
            echo '<div class="empty-history-content">';
            echo '<div class="empty-icon">';
            echo '<i class="fas fa-shopping-bag"></i>';
            echo '</div>';
            echo '<h2>購入履歴がありません</h2>';
            echo '<p>まだ商品をご購入いただいておりません。<br>お気に入りの商品を見つけてご注文ください。</p>';
            echo '<div class="empty-actions">';
            echo '<a href="product.php" class="btn btn-primary">';
            echo '<i class="fas fa-search"></i>';
            echo '商品を探す';
            echo '</a>';
            echo '<a href="favorite-show.php" class="btn btn-outline">';
            echo '<i class="fas fa-heart"></i>';
            echo 'お気に入りを見る';
            echo '</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        // 未ログイン
        echo '<div class="login-required">';
        echo '<div class="login-content">';
        echo '<div class="login-icon">';
        echo '<i class="fas fa-user-circle"></i>';
        echo '</div>';
        echo '<h2>ログインが必要です</h2>';
        echo '<p>購入履歴を表示するには、ログインしてください。</p>';
        echo '<div class="login-actions">';
        echo '<a href="login-input.php" class="btn btn-primary">';
        echo '<i class="fas fa-sign-in-alt"></i>';
        echo 'ログイン';
        echo '</a>';
        echo '<a href="customer-input.php" class="btn btn-outline">';
        echo '<i class="fas fa-user-plus"></i>';
        echo '新規会員登録';
        echo '</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    ?>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e5e7eb;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.page-description {
    color: #6b7280;
    font-size: 1.1rem;
}

/* 履歴コンテナ */
.history-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.history-header {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    padding: 30px;
}

.history-summary {
    display: flex;
    justify-content: center;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 15px;
}

.summary-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.summary-number {
    font-size: 2rem;
    font-weight: 700;
}

.summary-label {
    font-size: 1.1rem;
    opacity: 0.9;
    color: white;
}

/* 購入履歴 */
.purchase-history {
    padding: 30px;
}

.purchase-card {
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 30px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.purchase-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.purchase-header {
    background: white;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.purchase-header h3 {
    margin: 0 0 10px 0;
    color: #1f2937;
    font-size: 1.25rem;
}

.purchase-meta {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.order-id {
    font-family: 'Courier New', monospace;
    background: #dbeafe;
    color: #1e40af;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
}

.shipping-address {
    color: #6b7280;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* テーブルスタイル */
.purchase-table-container {
    padding: 20px;
    overflow-x: auto;
}

.purchase-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.purchase-table thead {
    background: #f3f4f6;
}

.purchase-table th,
.purchase-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.purchase-table th {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.image-cell img {
    width: 50px;
    height: 50px;
    object-fit: contain;
    border-radius: 4px;
    background: white;
}

.product-name-cell a {
    color: #2563eb;
    text-decoration: none;
    font-weight: 500;
}

.product-name-cell a:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

.price-cell, .total-cell {
    font-weight: 600;
    color: #1f2937;
}

.quantity-cell {
    text-align: center;
    font-weight: 500;
}

/* 集計行のスタイル */
.subtotal-row {
    background: #f9fafb;
    font-weight: 600;
}

.tax-row, .shipping-row {
    background: #f3f4f6;
    font-weight: 500;
}

.tax-detail-row {
    background: #f8fafc;
    font-size: 0.85rem;
    color: #6b7280;
}

.grand-total-row {
    background: #2563eb;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
}

.grand-total-row td {
    border-bottom: none;
}

/* 送料関連スタイル */
.remote-island-badge {
    background: #f59e0b;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.7rem;
    margin-left: 8px;
    font-weight: 600;
}

.free-shipping {
    background: #dcfce7;
    color: #166534;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.9rem;
}

.shipping-breakdown {
    color: #9ca3af;
    font-size: 0.75rem;
    line-height: 1.2;
}

/* アクションボタン */
.purchase-actions {
    padding: 15px 20px;
    background: white;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-outline {
    padding: 6px 12px;
    background: white;
    color: #4b5563;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.3s;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.btn-sm {
    font-size: 0.8rem;
    padding: 4px 8px;
}

/* 空の履歴 */
.empty-history {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.empty-history-content {
    text-align: center;
    max-width: 400px;
}

.empty-icon {
    width: 100px;
    height: 100px;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: #9ca3af;
    font-size: 2.5rem;
}

.empty-history h2 {
    color: #1f2937;
    margin-bottom: 10px;
}

.empty-history p {
    color: #6b7280;
    margin-bottom: 30px;
    line-height: 1.6;
}

.empty-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

/* ログイン要求 */
.login-required {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.login-content {
    text-align: center;
    max-width: 400px;
}

.login-icon {
    width: 100px;
    height: 100px;
    background: #dbeafe;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: #2563eb;
    font-size: 2.5rem;
}

.login-content h2 {
    color: #1f2937;
    margin-bottom: 10px;
}

.login-content p {
    color: #6b7280;
    margin-bottom: 30px;
}

.login-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

/* ボタン共通スタイル */
.btn {
    padding: 12px 20px;
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
}

.btn-outline {
    background: white;
    color: #4b5563;
    border: 1px solid #d1d5db;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

/* レスポンシブ */
@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    
    .purchase-header h3 {
        font-size: 1.1rem;
    }
    
    .purchase-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .purchase-table {
        font-size: 0.85rem;
    }
    
    .purchase-table th,
    .purchase-table td {
        padding: 8px;
    }
    
    .image-cell img {
        width: 40px;
        height: 40px;
    }
    
    .purchase-actions {
        flex-direction: column;
    }
    
    .empty-actions, .login-actions {
        flex-direction: column;
    }
}
</style>

<?php require 'footer.php'; ?>