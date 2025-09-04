<?php
session_start();
require 'common.php';

// ログインチェック
if (!isset($_SESSION['customer'])) {
    header('Location: login-input.php');
    exit;
}

// カートの商品チェック
if (!isset($_SESSION['product']) || empty($_SESSION['product'])) {
    header('Location: cart-show.php');
    exit;
}

$customer_id = $_SESSION['customer']['id'];

// 顧客の現在のポイントを取得
$customer_sql = $pdo->prepare('SELECT point FROM customer WHERE id = ?');
$customer_sql->bindParam(1, $customer_id, PDO::PARAM_INT);
$customer_sql->execute();
$customer_data = $customer_sql->fetch();
$available_points = $customer_data ? $customer_data['point'] : 0;

// エラーメッセージが存在する場合
if (!empty($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    // 一度表示したら消す（必要な場合）
    unset($_SESSION['error']);
}


// 顧客のデフォルト配送先情報を取得
$selected_address_id = $_SESSION['selected_shipping_address'] ?? null;

if ($selected_address_id) {
    // 選択された配送先の情報を取得
    $shipping_sql = $pdo->prepare('
        SELECT sa.*, r.region_id 
        FROM shipping_addresses sa
        LEFT JOIN region r ON r.prefectures_id = (
            SELECT master_id FROM master WHERE kbn = 12 AND name = sa.prefecture
        )
        WHERE sa.id = ? AND sa.customer_id = ?
    ');
    $shipping_sql->bindParam(1, $selected_address_id, PDO::PARAM_INT);
    $shipping_sql->bindParam(2, $customer_id, PDO::PARAM_INT);
    $shipping_sql->execute();
    $shipping_info = $shipping_sql->fetch();
} else {
    // デフォルト配送先を取得
    $shipping_sql = $pdo->prepare('
        SELECT sa.*, r.region_id 
        FROM shipping_addresses sa
        LEFT JOIN region r ON r.prefectures_id = (
            SELECT master_id FROM master WHERE kbn = 12 AND name = sa.prefecture
        )
        WHERE sa.customer_id = ? AND sa.is_default = 1 
        LIMIT 1
    ');
    $shipping_sql->bindParam(1, $customer_id, PDO::PARAM_INT);
    $shipping_sql->execute();
    $shipping_info = $shipping_sql->fetch();

    if ($shipping_info) {
        $_SESSION['selected_shipping_address'] = $shipping_info['id'];
    }
}
// すべてのリダイレクト処理完了後にheader.phpを読み込み
require 'header.php';

// デフォルト配送先がない場合のフォールバック
// region_idが取得できない場合の処理を追加
$customer_region_id = 3; // デフォルト値
$customer_remote_island = 0;

if ($shipping_info) {
    // region_idが設定されていない場合は都道府県から取得
    if (empty($shipping_info['region_id'])) {
        $region_sql = $pdo->prepare('
            SELECT r.region_id 
            FROM region r
            INNER JOIN master m ON r.prefectures_id = m.master_id
            WHERE m.kbn = 12 AND m.name = ?
            LIMIT 1
        ');
        $region_sql->bindParam(1, $shipping_info['prefecture']);
        $region_sql->execute();
        $region_data = $region_sql->fetch();

        $customer_region_id = $region_data ? $region_data['region_id'] : 3;

        // データベースも更新
        $update_sql = $pdo->prepare('UPDATE shipping_addresses SET region_id = ? WHERE id = ?');
        $update_sql->execute([$customer_region_id, $shipping_info['id']]);
    } else {
        $customer_region_id = $shipping_info['region_id'];
    }

    $customer_remote_island = $shipping_info['remote_island_check'];
}
$shipping_address = $shipping_info ? [
    'recipient_name' => $shipping_info['recipient_name'],
    'postal_code' => $shipping_info['postal_code'],
    'prefecture' => $shipping_info['prefecture'],
    'city' => $shipping_info['city'],
    'address_line1' => $shipping_info['address_line1'],
    'address_line2' => $shipping_info['address_line2']
] : [
    'recipient_name' => $_SESSION['customer']['name'],
    'postal_code' => '',
    'prefecture' => '',
    'city' => '',
    'address_line1' => '',
    'address_line2' => ''
];

// 地域別基本送料を取得
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

// 離島追加料金を取得
$remote_island_fee = 0;
if ($customer_remote_island) {
    $remote_sql = $pdo->prepare('SELECT remote_island_fee FROM postage_remote_island WHERE start_date <= CURDATE() AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY start_date DESC LIMIT 1');
    $remote_sql->execute();
    $remote_info = $remote_sql->fetch();
    if ($remote_info) {
        $remote_island_fee = $remote_info['remote_island_fee'];
    }
}

// 送料無料基準額を取得
$free_shipping_threshold = 0;
$free_shipping_sql = $pdo->prepare('SELECT postage_fee_free FROM postage_free WHERE start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) AND del_kbn = 0 ORDER BY start_date DESC LIMIT 1');
$free_shipping_sql->execute();
$free_shipping_info = $free_shipping_sql->fetch();
if ($free_shipping_info) {
    $free_shipping_threshold = $free_shipping_info['postage_fee_free'];
}

// 地域名を取得
$region_name = '';
if ($customer_region_id) {
    $region_name_sql = $pdo->prepare('SELECT name FROM master WHERE master_id = ? AND kbn = 11');
    $region_name_sql->bindParam(1, $customer_region_id, PDO::PARAM_INT);
    $region_name_sql->execute();
    $region_name_result = $region_name_sql->fetch();
    $region_name = $region_name_result ? $region_name_result['name'] : '地域' . $customer_region_id;
}

// カート内商品の詳細取得
$cart_products = [];
$total_amount = 0;
$total_quantity = 0;

foreach ($_SESSION['product'] as $product_id => $product_data) {
    $sql = $pdo->prepare('SELECT * FROM product WHERE id = ?');
    $sql->bindParam(1, $product_data['id'], PDO::PARAM_INT);
    $sql->execute();
    $product = $sql->fetch();

    if ($product) {
        $images = getImage($product['id'], $pdo);
        $subtotal = $product_data['price'] * $product_data['count'];
        $cart_products[] = [
            'product' => $product,
            'product_data' => $product_data,
            'quantity' => $product_data['count'],
            'subtotal' => $subtotal,
            'images' => $images
        ];
        $total_amount += $subtotal;
        $total_quantity += $product_data['count'];
    }
}

// 税額計算
$tax_details = [];
$tax_total = 0;

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

// 送料計算
$is_free_shipping = ($free_shipping_threshold > 0 && $total_amount >= $free_shipping_threshold);
$final_shipping_fee = $is_free_shipping ? 0 : ($base_shipping_fee + $remote_island_fee);

// 使用ポイントの処理
$use_points = 0;
if (isset($_SESSION['use_points'])) {
    $use_points = min($_SESSION['use_points'], $available_points, $total_amount + $tax_total + $final_shipping_fee);
}

// 総合計
$grand_total = $total_amount + $tax_total + $final_shipping_fee - $use_points;
?>

<div class="purchase-container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-credit-card"></i>
            購入手続き
        </h1>
        <p class="page-description">配送先を確認して注文を確定してください</p>
    </div>

    <form method="POST" action="purchase-output.php" class="purchase-form" id="purchaseForm">
        <!-- 配送先情報セクション -->
        <div class="form-section shipping-section">
            <h2 class="section-title">
                <i class="fas fa-shipping-fast"></i>
                配送先情報
            </h2>

            <div class="customer-address">
                <div class="address-card">
                    <div class="address-header">
                        <h4 class="customer-name"><?= h($shipping_address['recipient_name']) ?>様</h4>
                        <div class="address-badges">
                            <span class="region-badge">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= h($region_name) ?>
                            </span>
                            <?php if ($customer_remote_island): ?>
                                <span class="remote-island-badge">
                                    <i class="fas fa-island-tropical"></i>
                                    離島配送
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="address-details">
                        <?php if ($shipping_address['postal_code']): ?>
                            <div class="postcode-info">
                                <i class="fas fa-mail-bulk"></i>
                                〒<?= h($shipping_address['postal_code']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="address-info">
                            <i class="fas fa-home"></i>
                            <span><?= h($shipping_address['prefecture'] . $shipping_address['city'] . $shipping_address['address_line1']) ?>
                                <?php if ($shipping_address['address_line2']): ?>
                                    <br><?= h($shipping_address['address_line2']) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <div class="shipping-fee-info">
                        <div class="shipping-calculation">
                            <div class="calc-row">
                                <span>基本配送料:</span>
                                <span>¥<?= number_format($base_shipping_fee) ?></span>
                            </div>
                            <?php if ($customer_remote_island && $remote_island_fee > 0): ?>
                                <div class="calc-row">
                                    <span>離島追加料金:</span>
                                    <span>¥<?= number_format($remote_island_fee) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="calc-row total-calc">
                                <span>配送料合計:</span>
                                <span class="shipping-total">
                                    <?php if ($is_free_shipping): ?>
                                        <span class="free-label">無料</span>
                                    <?php else: ?>
                                        ¥<?= number_format($final_shipping_fee) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($free_shipping_threshold > 0): ?>
                            <div class="free-shipping-info">
                                <i class="fas fa-info-circle"></i>
                                <?php if ($is_free_shipping): ?>
                                    送料無料条件を満たしています
                                <?php else: ?>
                                    ¥<?= number_format($free_shipping_threshold) ?>以上のお買い物で送料無料
                                    （あと¥<?= number_format($free_shipping_threshold - $total_amount) ?>）
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="address-actions">
                    <a href="purchase-shipping-select.php" class="btn btn-outline btn-sm">
                        <i class="fas fa-edit"></i>
                        配送先を変更
                    </a>
                </div>
            </div>
        </div>

        <!-- 注文内容確認セクション -->
        <div class="form-section order-section">
            <h2 class="section-title">
                <i class="fas fa-shopping-cart"></i>
                注文内容確認
            </h2>

            <div class="order-summary">
                <div class="cart-items">
                    <?php foreach ($cart_products as $item): ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <img src="images/<?= $item['images'][0] ?>.jpg"
                                    alt="<?= h($item['product']['name']) ?>">
                            </div>
                            <div class="item-details">
                                <h4 class="item-name"><?= h($item['product']['name']) ?></h4>
                                <div class="item-specs">
                                    <span class="spec-tag">
                                        <i class="fas fa-weight-hanging"></i>
                                        <?= $item['product']['weight'] ?>g
                                    </span>
                                    <span class="spec-tag">
                                        <i class="fas fa-mouse"></i>
                                        <?= number_format($item['product']['dpi_max']) ?> DPI
                                    </span>
                                    <?php if ($item['product']['polling_rate'] >= 8000): ?>
                                        <span class="spec-tag highlight">
                                            <i class="fas fa-bolt"></i>
                                            8KHz対応
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-price">
                                    <span class="unit-price">単価: ¥<?= number_format($item['product']['price']) ?></span>
                                    <span class="quantity">数量: <?= $item['quantity'] ?>個</span>
                                </div>
                            </div>
                            <div class="item-total">
                                ¥<?= number_format($item['subtotal']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ポイント利用セクション -->
        <div class="form-section points-section">
            <h2 class="section-title">
                <i class="fas fa-coins"></i>
                ポイント利用
            </h2>

            <div class="points-usage-card">
                <div class="available-points">
                    <div class="points-info">
                        <i class="fas fa-coins"></i>
                        <span class="points-label">利用可能ポイント:</span>
                        <span class="points-value"><?= number_format($available_points) ?> P</span>
                    </div>
                    <div class="points-note">
                        <i class="fas fa-info-circle"></i>
                        1ポイント = 1円として利用できます
                    </div>
                </div>

                <div class="points-input-section">
                    <label class="points-input-label">使用するポイント数</label>
                    <div class="points-input-group">
                        <input type="number"
                            id="use_points"
                            name="use_points"
                            class="points-input"
                            min="0"
                            max="<?= min($available_points, $total_amount + $tax_total + $final_shipping_fee) ?>"
                            value="<?= $use_points ?>"
                            placeholder="0">
                        <span class="points-unit">P</span>
                        <button type="button" id="use_all_points" class="btn btn-outline btn-sm">
                            全て使用
                        </button>
                        <button type="button" id="clear_points" class="btn btn-outline btn-sm">
                            クリア
                        </button>
                    </div>
                    <div class="points-max-info">
                        最大使用可能: <?= number_format(min($available_points, $total_amount + $tax_total + $final_shipping_fee)) ?> P
                    </div>
                </div>

                <div class="points-discount" id="points-discount" style="<?= $use_points > 0 ? '' : 'display: none;' ?>">
                    <div class="discount-amount">
                        <i class="fas fa-minus-circle"></i>
                        <span>ポイント割引: -¥<span id="discount-value"><?= number_format($use_points) ?></span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 料金詳細セクション -->
        <div class="form-section pricing-section">
            <h2 class="section-title">
                <i class="fas fa-calculator"></i>
                料金詳細
            </h2>

            <div class="price-breakdown">
                <div class="price-row">
                    <span class="price-label">商品合計（<?= $total_quantity ?>点）</span>
                    <span class="price-value">¥<?= number_format($total_amount) ?></span>
                </div>

                <?php foreach ($tax_details as $tax_id => $tax_detail): ?>
                    <div class="price-row tax-row">
                        <span class="price-label">
                            <i class="fas fa-receipt"></i>
                            消費税 <?= ($tax_id == 1 ? '8%' : '10%') ?>対象 ¥<?= number_format($tax_detail['subtotal']) ?>
                        </span>
                        <span class="price-value">¥<?= number_format($tax_detail['tax_amount']) ?></span>
                    </div>
                <?php endforeach; ?>

                <div class="price-row">
                    <span class="price-label">消費税等合計</span>
                    <span class="price-value">¥<?= number_format($tax_total) ?></span>
                </div>

                <div class="price-row shipping-fee-row">
                    <span class="price-label">
                        配送料
                        <span class="shipping-region">（<?= h($region_name) ?>）</span>
                        <?php if ($customer_remote_island): ?>
                            <span class="remote-note">（離島追加料金込み）</span>
                        <?php endif; ?>
                    </span>
                    <span class="price-value">
                        <?php if ($is_free_shipping): ?>
                            <span class="free-shipping-text">無料</span>
                        <?php else: ?>
                            ¥<?= number_format($final_shipping_fee) ?>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if ($use_points > 0): ?>
                    <div class="price-row points-discount-row">
                        <span class="price-label">
                            <i class="fas fa-coins"></i>
                            ポイント割引
                        </span>
                        <span class="price-value points-discount-value">
                            -¥<span id="total-discount"><?= number_format($use_points) ?></span>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="price-row total-row">
                    <span class="price-label">お支払い総額</span>
                    <span class="price-value total-price" id="final-total">¥<?= number_format($grand_total) ?></span>
                </div>
            </div>
        </div>

        <!-- 支払い方法選択セクション -->
        <div class="form-section payment-section">
            <h2 class="section-title">
                <i class="fas fa-credit-card"></i>
                お支払い方法
                <span class="required-badge">必須</span>
            </h2>

            <div class="payment-options">
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="credit_card" checked required>
                    <div class="payment-card">
                        <div class="payment-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="payment-info">
                            <h4>クレジットカード</h4>
                            <p>Visa, Mastercard, JCB, American Express</p>
                            <small>SSL暗号化通信で安全にお支払い</small>
                        </div>
                    </div>
                </label>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="bank_transfer" required>
                    <div class="payment-card">
                        <div class="payment-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="payment-info">
                            <h4>銀行振込</h4>
                            <p>指定口座への事前振込</p>
                            <small>振込手数料はお客様負担となります</small>
                        </div>
                    </div>
                </label>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="cod" required>
                    <div class="payment-card">
                        <div class="payment-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="payment-info">
                            <h4>代金引換</h4>
                            <p>商品受け取り時にお支払い</p>
                            <small>代引き手数料 ¥330</small>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- 注意事項・利用規約セクション -->
        <div class="form-section terms-section">
            <h2 class="section-title">
                <i class="fas fa-file-contract"></i>
                注意事項・利用規約
            </h2>

            <div class="terms-content">
                <div class="terms-box">
                    <h4>キャンセル・返品について</h4>
                    <ul>
                        <li>商品発送前であればキャンセル可能です</li>
                        <li>不良品・誤配送の場合は到着後7日以内にご連絡ください</li>
                        <li>お客様都合による返品は未開封に限り可能です</li>
                    </ul>

                    <h4>配送について</h4>
                    <ul>
                        <li>通常2-3営業日での発送となります</li>
                        <li>離島・遠隔地は追加日数がかかる場合があります</li>
                        <li>配送業者：ヤマト運輸、佐川急便</li>
                        <?php if ($customer_remote_island): ?>
                            <li class="remote-notice">
                                <i class="fas fa-exclamation-triangle"></i>
                                離島配送のため、通常より配送日数がかかる場合があります
                            </li>
                        <?php endif; ?>
                    </ul>

                    <?php if ($use_points > 0): ?>
                        <h4>ポイント利用について</h4>
                        <ul>
                            <li>使用したポイントは注文確定後に差し引かれます</li>
                            <li>注文キャンセル時は使用ポイントも復元されます</li>
                            <li>一度使用したポイントは他の注文では利用できません</li>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="agreement-checkbox">
                    <label class="checkbox-label">
                        <input type="checkbox" name="agree_terms" value="1" required>
                        <span class="checkbox-custom"></span>
                        <div class="agreement-text">
                            <strong>利用規約に同意します</strong>
                            <small>
                                <a href="terms.php" target="_blank">利用規約</a>および
                                <a href="privacy.php" target="_blank">プライバシーポリシー</a>をご確認の上、同意してください
                            </small>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- 注文確定ボタン -->
        <div class="form-actions">
            <div class="action-buttons">
                <a href="cart-show.php" class="btn btn-outline btn-large">
                    <i class="fas fa-arrow-left"></i>
                    カートに戻る
                </a>
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="fas fa-credit-card"></i>
                    注文を確定する
                    <span class="total-in-button" id="button-total">（¥<?= number_format($grand_total) ?>）</span>
                </button>
            </div>
        </div>

        <!-- ポイント利用の隠しフィールド -->
        <input type="hidden" name="final_use_points" id="final_use_points" value="<?= $use_points ?>">
    </form>
</div>

<style>
    /* ポイント利用セクションのスタイル */
    .points-section {
        border-left: 4px solid #f59e0b;
    }

    .points-usage-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 25px;
    }

    .available-points {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #e5e7eb;
    }

    .points-info {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .points-info i {
        color: #f59e0b;
        font-size: 1.2rem;
    }

    .points-label {
        color: #6b7280;
        font-weight: 500;
    }

    .points-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
    }

    .points-note {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #6b7280;
        font-size: 0.9rem;
    }

    .points-input-section {
        margin-bottom: 20px;
    }

    .points-input-label {
        display: block;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 10px;
    }

    .points-input-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }

    .points-input {
        flex: 1;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s;
        max-width: 200px;
    }

    .points-input:focus {
        outline: none;
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
    }

    .points-unit {
        color: #6b7280;
        font-weight: 500;
    }

    .points-max-info {
        font-size: 0.85rem;
        color: #6b7280;
    }

    .points-discount {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 15px;
    }

    .discount-amount {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #059669;
        font-weight: 600;
    }

    .discount-amount i {
        color: #059669;
    }

    .points-discount-row {
        background: #f0fdf4;
        border-radius: 8px;
        padding: 12px !important;
        margin: 10px 0 !important;
    }

    .points-discount-value {
        color: #059669 !important;
        font-weight: 700 !important;
    }

    /* 基本レイアウト */
    .purchase-container {
        max-width: 1000px;
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

    .purchase-form {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }

    .form-section {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.4rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f3f4f6;
    }

    .required-badge {
        background: #ef4444;
        color: white;
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 12px;
        margin-left: auto;
    }

    /* 配送先情報セクション */
    .shipping-section {
        border-left: 4px solid #2563eb;
    }

    .customer-address {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .address-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 25px;
    }

    .address-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .customer-name {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }

    .address-badges {
        display: flex;
        gap: 10px;
    }

    .region-badge {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .remote-island-badge {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .address-details {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 20px;
    }

    .postcode-info,
    .address-info {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #374151;
    }

    .postcode-info i,
    .address-info i {
        color: #6b7280;
        width: 16px;
    }

    .shipping-fee-info {
        background: white;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #d1d5db;
    }

    .shipping-calculation {
        margin-bottom: 15px;
    }

    .calc-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        color: #6b7280;
    }

    .total-calc {
        border-top: 1px solid #e5e7eb;
        margin-top: 10px;
        padding-top: 15px;
        font-weight: 600;
        color: #1f2937;
    }

    .shipping-total {
        font-size: 1.1rem;
        font-weight: 700;
    }

    .free-label {
        color: #10b981;
        background: #dcfce7;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 0.9rem;
    }

    .free-shipping-info {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 8px;
        padding: 12px 16px;
        color: #0369a1;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .address-actions {
        display: flex;
        justify-content: center;
    }

    /* 注文内容セクション */
    .order-section {
        border-left: 4px solid #10b981;
    }

    .cart-items {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .cart-item {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }

    .item-image {
        width: 80px;
        height: 80px;
        flex-shrink: 0;
    }

    .item-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 8px;
        background: white;
    }

    .item-details {
        flex: 1;
    }

    .item-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 8px;
    }

    .item-specs {
        margin-bottom: 8px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .spec-tag {
        background: #e5e7eb;
        color: #6b7280;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .spec-tag.highlight {
        background: linear-gradient(135deg, #667eea15, #764ba215);
        color: #667eea;
        border: 1px solid #667eea30;
    }

    .item-price {
        display: flex;
        gap: 15px;
        font-size: 0.9rem;
        color: #6b7280;
    }

    .item-total {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
    }

    /* 料金詳細セクション */
    .pricing-section {
        border-left: 4px solid #f59e0b;
    }

    .price-breakdown {
        background: #f8fafc;
        border-radius: 12px;
        padding: 25px;
    }

    .price-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .price-row:last-child {
        border-bottom: none;
    }

    .price-label {
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .shipping-region {
        font-size: 0.8rem;
        color: #3b82f6;
    }

    .remote-note {
        font-size: 0.8rem;
        color: #f59e0b;
    }

    .price-value {
        font-weight: 600;
        color: #1f2937;
    }

    .free-shipping-text {
        color: #10b981;
        font-weight: 700;
    }

    .total-row {
        border-top: 2px solid #e5e7eb;
        padding-top: 15px;
        margin-top: 15px;
    }

    .total-row .price-label {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
    }

    .total-price {
        font-size: 1.5rem !important;
        font-weight: 700;
        color: #2563eb;
    }

    /* 支払い方法セクション */
    .payment-section {
        border-left: 4px solid #8b5cf6;
    }

    .payment-options {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .payment-option {
        cursor: pointer;
    }

    .payment-option input[type="radio"] {
        display: none;
    }

    .payment-card {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        transition: all 0.3s ease;
        background: white;
    }

    .payment-option:has(input:checked) .payment-card {
        border-color: #8b5cf6;
        background: #faf5ff;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    .payment-card:hover {
        border-color: #d1d5db;
        background: #f9fafb;
    }

    .payment-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .payment-info h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 5px;
    }

    .payment-info p {
        color: #6b7280;
        margin-bottom: 5px;
    }

    .payment-info small {
        color: #9ca3af;
        font-size: 0.8rem;
    }

    /* 利用規約セクション */
    .terms-section {
        border-left: 4px solid #6b7280;
    }

    .terms-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 25px;
    }

    .terms-box h4 {
        color: #1f2937;
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 10px;
        margin-top: 20px;
    }

    .terms-box h4:first-child {
        margin-top: 0;
    }

    .terms-box ul {
        margin-left: 20px;
        color: #6b7280;
    }

    .terms-box li {
        margin-bottom: 5px;
        line-height: 1.5;
    }

    .remote-notice {
        color: #f59e0b;
        font-weight: 600;
    }

    .agreement-checkbox {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        padding: 20px;
    }

    .checkbox-label {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        cursor: pointer;
    }

    .checkbox-label input[type="checkbox"] {
        display: none;
    }

    .checkbox-custom {
        width: 20px;
        height: 20px;
        border: 2px solid #d1d5db;
        border-radius: 4px;
        background: white;
        position: relative;
        transition: all 0.3s ease;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .checkbox-label input[type="checkbox"]:checked+.checkbox-custom {
        background: #2563eb;
        border-color: #2563eb;
    }

    .checkbox-label input[type="checkbox"]:checked+.checkbox-custom::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 6px;
        width: 6px;
        height: 10px;
        border: solid white;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }

    .agreement-text strong {
        display: block;
        color: #1f2937;
        margin-bottom: 5px;
    }

    .agreement-text small {
        color: #6b7280;
        line-height: 1.4;
    }

    .agreement-text a {
        color: #2563eb;
        text-decoration: none;
    }

    .agreement-text a:hover {
        text-decoration: underline;
    }

    /* アクションボタン */
    .form-actions {
        text-align: center;
        padding: 30px 0;
    }

    .action-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 1rem;
    }

    .btn-large {
        padding: 18px 32px;
        font-size: 1.1rem;
    }

    .btn-primary {
        background: #2563eb;
        color: white;
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

    .btn-sm {
        padding: 8px 16px;
        font-size: 0.8rem;
    }

    .total-in-button {
        font-size: 0.9rem;
        opacity: 0.9;
    }

    /* レスポンシブ対応 */
    @media (max-width: 768px) {
        .purchase-container {
            padding: 15px;
        }

        .form-section {
            padding: 20px;
        }

        .address-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .address-badges {
            flex-wrap: wrap;
        }

        .action-buttons {
            flex-direction: column;
        }

        .cart-item {
            flex-direction: column;
            text-align: center;
        }

        .item-image {
            width: 120px;
            height: 120px;
        }

        .payment-card {
            flex-direction: column;
            text-align: center;
        }

        .calc-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const usePointsInput = document.getElementById('use_points');
        const useAllPointsBtn = document.getElementById('use_all_points');
        const clearPointsBtn = document.getElementById('clear_points');
        const pointsDiscount = document.getElementById('points-discount');
        const discountValue = document.getElementById('discount-value');
        const totalDiscount = document.getElementById('total-discount');
        const finalTotal = document.getElementById('final-total');
        const buttonTotal = document.getElementById('button-total');
        const finalUsePoints = document.getElementById('final_use_points');

        const availablePoints = <?= $available_points ?>;
        const baseTotal = <?= $total_amount + $tax_total + $final_shipping_fee ?>;
        const maxUsablePoints = Math.min(availablePoints, baseTotal);

        // ポイント入力値の変更処理
        function updatePointsCalculation() {
            const usePoints = Math.min(Math.max(0, parseInt(usePointsInput.value) || 0), maxUsablePoints);
            const newTotal = Math.max(0, baseTotal - usePoints);

            // 実際の入力値を制限内に修正
            usePointsInput.value = usePoints;
            finalUsePoints.value = usePoints;

            // 割引表示の更新
            if (usePoints > 0) {
                pointsDiscount.style.display = 'block';
                if (discountValue) discountValue.textContent = usePoints.toLocaleString();
                if (totalDiscount) totalDiscount.textContent = usePoints.toLocaleString();

                // 料金詳細のポイント割引行を表示/更新
                const pointsDiscountRow = document.querySelector('.points-discount-row');
                if (pointsDiscountRow) {
                    pointsDiscountRow.style.display = 'flex';
                }
            } else {
                pointsDiscount.style.display = 'none';
                const pointsDiscountRow = document.querySelector('.points-discount-row');
                if (pointsDiscountRow) {
                    pointsDiscountRow.style.display = 'none';
                }
            }

            // 最終合計の更新
            finalTotal.textContent = '¥' + newTotal.toLocaleString();
            buttonTotal.textContent = '（¥' + newTotal.toLocaleString() + '）';
        }

        // 入力イベントリスナー
        usePointsInput.addEventListener('input', updatePointsCalculation);
        usePointsInput.addEventListener('blur', updatePointsCalculation);

        // 全て使用ボタン
        useAllPointsBtn.addEventListener('click', function() {
            usePointsInput.value = maxUsablePoints;
            updatePointsCalculation();
        });

        // クリアボタン
        clearPointsBtn.addEventListener('click', function() {
            usePointsInput.value = 0;
            updatePointsCalculation();
        });

        // 初期計算の実行
        updatePointsCalculation();

        // フォーム送信前のバリデーション
        document.getElementById('purchaseForm').addEventListener('submit', function(e) {
            const usePoints = parseInt(usePointsInput.value) || 0;

            if (usePoints > availablePoints) {
                e.preventDefault();
                alert('利用可能ポイントを超えています。');
                usePointsInput.focus();
                return false;
            }

            if (usePoints > baseTotal) {
                e.preventDefault();
                alert('支払い金額を超えるポイントは使用できません。');
                usePointsInput.focus();
                return false;
            }

            // 最終的な使用ポイントを隠しフィールドに設定
            finalUsePoints.value = usePoints;
        });
    });
</script>
<?php if (!empty($errorMessage)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        alert(<?= json_encode($errorMessage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
    });
</script>
<?php endif; ?>

<?php require 'footer.php' ?>