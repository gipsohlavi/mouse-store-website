<?php
if (!empty($_SESSION['product'])) {
    // 顧客情報の取得（送料計算のため）
    $shipping_fee = 0;
    $free_shipping_threshold = 0;
    $customer_region_id = null;
    $remote_island_fee = 0;

    if (isset($_SESSION['customer'])) {
        $customer_id = $_SESSION['customer']['id'];

        // shipping_addressesテーブルからデフォルト住所の地域情報を取得
        $customer_sql = $pdo->prepare('
            SELECT region_id, remote_island_check 
            FROM shipping_addresses 
            WHERE customer_id = ? AND is_default = 1 
            LIMIT 1
        ');
        $customer_sql->bindParam(1, $customer_id, PDO::PARAM_INT);
        $customer_sql->execute();
        $customer_info = $customer_sql->fetch();

        if ($customer_info) {
            $customer_region_id = $customer_info['region_id'];

            // 地域別送料の取得
            $postage_sql = $pdo->prepare('SELECT postage_fee FROM postage WHERE region_id = ? AND start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) ORDER BY start_date DESC LIMIT 1');
            $postage_sql->bindParam(1, $customer_region_id, PDO::PARAM_INT);
            $postage_sql->execute();
            $postage_info = $postage_sql->fetch();

            if ($postage_info) {
                $shipping_fee = $postage_info['postage_fee'];
            }

            // 離島追加料金の確認
            if ($customer_info['remote_island_check']) {
                $remote_sql = $pdo->prepare('SELECT remote_island_fee FROM postage_remote_island WHERE start_date <= CURDATE() AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY start_date DESC LIMIT 1');
                $remote_sql->execute();
                $remote_info = $remote_sql->fetch();

                if ($remote_info) {
                    $remote_island_fee = $remote_info['remote_island_fee'];
                }
            }
        }
    }

    // 送料無料基準額の取得
    $free_shipping_sql = $pdo->prepare('SELECT postage_fee_free FROM postage_free WHERE start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) AND del_kbn = 0 ORDER BY start_date DESC LIMIT 1');
    $free_shipping_sql->execute();
    $free_shipping_info = $free_shipping_sql->fetch();

    if ($free_shipping_info) {
        $free_shipping_threshold = $free_shipping_info['postage_fee_free'];
    }
?>
    <div class="cart-container">
        <div class="cart-header">
            <h2><i class="fas fa-shopping-cart"></i> ショッピングカート</h2>
            <p class="cart-item-count"><?= count($_SESSION['product']) ?>件の商品</p>
        </div>

        <div class="cart-content">
            <div class="cart-items">
                <?php
                $total = 0;
                $tax = [];
                foreach ($_SESSION['product'] as $id => $product) {
                    // 税率の取得
                    $sql = $pdo->prepare('select tax from tax where tax_id = ?');
                    $sql->bindParam(1, $product['tax'], PDO::PARAM_INT);
                    $sql->execute();
                    $stmt = $sql->fetch();

                    // 画像ファイル名の取得
                    $images = getImage($product['id'], $pdo);

                    // 商品詳細情報を取得
                    $detail_sql = $pdo->prepare('SELECT * FROM product WHERE id = ?');
                    $detail_sql->bindParam(1, $product['id'], PDO::PARAM_INT);
                    $detail_sql->execute();
                    $product_detail = $detail_sql->fetch();

                    $subtotal = $product['price'] * $product['count'];
                    $total += $subtotal;
                ?>

                    <div class="cart-item" data-product-id="<?= $product['id'] ?>">
                        <div class="item-image">
                            <img src="images/<?= $images[0] ?>.jpg" alt="<?= h($product['name']) ?>" loading="lazy">
                        </div>

                        <div class="item-details">
                            <h3 class="item-name">
                                <a href="detail.php?id=<?= h($product['id']) ?>"><?= h($product['name']) ?></a>
                            </h3>

                            <div class="item-specs">
                                <?php if ($product_detail): ?>
                                    <span class="spec-item">
                                        <i class="fas fa-weight-hanging"></i>
                                        <?= $product_detail['weight'] ?>g
                                    </span>
                                    <span class="spec-item">
                                        <i class="fas fa-mouse"></i>
                                        <?= number_format($product_detail['dpi_max']) ?> DPI
                                    </span>
                                    <?php if ($product_detail['polling_rate'] >= 8000): ?>
                                        <span class="spec-item highlight">
                                            <i class="fas fa-bolt"></i>
                                            8KHz対応
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <div class="item-price">
                                <span class="unit-price">¥<?= number_format($product['price']) ?></span>
                                <span class="price-per-unit">/ 個</span>
                            </div>
                        </div>

                        <div class="item-quantity">
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn qty-decrease" data-id="<?= $id ?>">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="qty-input" value="<?= $product['count'] ?>"
                                    min="1" max="10" data-id="<?= $id ?>">
                                <button type="button" class="qty-btn qty-increase" data-id="<?= $id ?>">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <p class="quantity-note">在庫：<?= $product_detail['stock_quantity'] ?? '確認中' ?>個</p>
                        </div>

                        <div class="item-total">
                            <div class="subtotal">¥<?= number_format($subtotal) ?></div>
                            <button type="button" class="remove-btn" data-id="<?= $id ?>">
                                <i class="fas fa-trash-alt"></i>
                                削除
                            </button>
                        </div>
                    </div>

                <?php
                    // taxIDごとに配列作成する
                    if (!isset($tax[$product["tax"]])) {
                        $tax[$product["tax"]] = [$stmt["tax"], $subtotal, round($subtotal * $stmt["tax"])];
                    } else {
                        if (in_array($tax[$product["tax"]], $tax)) {
                            $tax[$product["tax"]][1] = $tax[$product["tax"]][1] + $subtotal;
                            $tax[$product["tax"]][2] = round($tax[$product["tax"]][0] * $tax[$product["tax"]][1]);
                        }
                    }
                }
                ?>
            </div>

            <!-- カート集計部分 -->
            <div class="cart-summary">
                <div class="summary-card">
                    <h3>
                        <i class="fas fa-calculator"></i>
                        ご注文内容
                    </h3>

                    <div class="summary-row">
                        <span class="summary-label">小計</span>
                        <span class="summary-value">¥<?= number_format($total) ?></span>
                    </div>

                    <?php
                    // 消費税等
                    $tax_total = 0;
                    foreach ($tax as $row) {
                        $tax_total += (int)$row[2];
                    }
                    ?>

                    <div class="tax-breakdown">
                        <?php if (isset($tax[1])): ?>
                            <div class="summary-row tax-row">
                                <span class="summary-label">
                                    <i class="fas fa-receipt"></i>
                                    消費税 8%対象 ¥<?= number_format($tax[1][1]) ?>
                                </span>
                                <span class="summary-value">¥<?= number_format($tax[1][2]) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($tax[2])): ?>
                            <div class="summary-row tax-row">
                                <span class="summary-label">
                                    <i class="fas fa-receipt"></i>
                                    消費税 10%対象 ¥<?= number_format($tax[2][1]) ?>
                                </span>
                                <span class="summary-value">¥<?= number_format($tax[2][2]) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="summary-row">
                            <span class="summary-label">消費税等</span>
                            <span class="summary-value">¥<?= number_format($tax_total) ?></span>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['customer'])): ?>
                        <?php
                        // 顧客のポイント情報を取得
                        $customer_points_sql = $pdo->prepare('SELECT point FROM customer WHERE id = ?');
                        $customer_points_sql->bindParam(1, $_SESSION['customer']['id'], PDO::PARAM_INT);
                        $customer_points_sql->execute();
                        $customer_points_data = $customer_points_sql->fetch();
                        $available_points = $customer_points_data ? $customer_points_data['point'] : 0;

                        // 基本ポイント付与率を取得
                        $basic_rate_sql = $pdo->prepare('SELECT campaign_point_rate FROM point_campaign WHERE point_campaign_id = 1');
                        $basic_rate_sql->execute();
                        $basic_rate_data = $basic_rate_sql->fetch();
                        $basic_rate = $basic_rate_data ? $basic_rate_data['campaign_point_rate'] : 0.01;

                        // 獲得予定ポイントの計算
                        $expected_points = 0;
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

                            $expected_points += $product_points;
                        }
                        ?>

                        <div class="points-info-section">
                            <div class="points-separator"></div>

                            <div class="points-header">
                                <h4>
                                    <i class="fas fa-coins"></i>
                                    ポイント情報
                                </h4>
                            </div>

                            <div class="points-details">
                                <div class="summary-row points-row">
                                    <span class="summary-label">
                                        <i class="fas fa-wallet"></i>
                                        現在の保有ポイント
                                    </span>
                                    <span class="summary-value points-value"><?= number_format($available_points) ?> P</span>
                                </div>

                                <div class="summary-row points-row expected-points">
                                    <span class="summary-label">
                                        <i class="fas fa-gift"></i>
                                        今回の獲得予定ポイント
                                    </span>
                                    <span class="summary-value points-value highlight"><?= number_format($expected_points) ?> P</span>
                                </div>

                                <div class="points-note">
                                    <i class="fas fa-info-circle"></i>
                                    <span>ポイントは購入完了後に付与されます（1P = 1円として利用可能）</span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="points-login-prompt">
                            <div class="points-separator"></div>
                            <div class="login-for-points">
                                <i class="fas fa-coins"></i>
                                <div class="login-message">
                                    <p><strong>ログインしてポイントを貯めよう！</strong></p>
                                    <p>会員登録・ログインで購入金額の1%をポイント還元</p>
                                    <div class="login-actions">
                                        <a href="login-input.php?redirect=cart-show.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-sign-in-alt"></i>
                                            ログイン
                                        </a>
                                        <a href="customer-input.php" class="btn btn-outline btn-sm">
                                            <i class="fas fa-user-plus"></i>
                                            会員登録
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="shipping-info">
                        <?php
                        // 送料無料判定
                        $is_free_shipping = ($free_shipping_threshold > 0 && $total >= $free_shipping_threshold);
                        $final_shipping_fee = 0;

                        if (!$is_free_shipping && isset($_SESSION['customer'])) {
                            $final_shipping_fee = $shipping_fee + $remote_island_fee;
                        }
                        ?>

                        <div class="summary-row">
                            <span class="summary-label">
                                <i class="fas fa-truck"></i>
                                配送料
                            </span>
                            <span class="summary-value">
                                <?php if ($is_free_shipping): ?>
                                    <span class="free-shipping">無料</span>
                                <?php elseif (isset($_SESSION['customer'])): ?>
                                    ¥<?= number_format($final_shipping_fee) ?>
                                    <?php if ($remote_island_fee > 0): ?>
                                        <small class="remote-island-note">
                                            <br><i class="fas fa-island-tropical"></i>
                                            離島追加料金込み
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="login-required-shipping">ログイン後に表示</span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php if (!$is_free_shipping && $free_shipping_threshold > 0): ?>
                            <p class="shipping-note">
                                <i class="fas fa-info-circle"></i>
                                あと¥<?= number_format($free_shipping_threshold - $total) ?>で送料無料
                            </p>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['customer']) && $customer_region_id): ?>
                            <p class="region-info">
                                <i class="fas fa-map-marker-alt"></i>
                                お届け地域:
                                <?php
                                // 地域名の取得
                                $region_name_sql = $pdo->prepare('SELECT name FROM master WHERE master_id = ? AND kbn = 11');
                                $region_name_sql->bindParam(1, $customer_region_id, PDO::PARAM_INT);
                                $region_name_sql->execute();
                                $region_name = $region_name_sql->fetch();
                                echo $region_name ? h($region_name['name']) : '地域' . $customer_region_id;
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php
                    $grand_total = $total + $tax_total + $final_shipping_fee;
                    ?>

                    <div class="summary-row total-row">
                        <span class="summary-label">合計</span>
                        <span class="summary-value grand-total">¥<?= number_format($grand_total) ?></span>
                    </div>

                    <div class="cart-actions">
                        <a href="product.php" class="btn btn-outline continue-shopping">
                            <i class="fas fa-arrow-left"></i>
                            買い物を続ける
                        </a>

                        <?php if (isset($_SESSION['customer'])): ?>
                            <a href="purchase-input.php" class="btn btn-primary checkout-btn">
                                <i class="fas fa-credit-card"></i>
                                レジに進む
                            </a>
                        <?php else: ?>
                            <div class="login-prompt">
                                <p><i class="fas fa-user-circle"></i> ご購入にはログインが必要です</p>
                                <a href="login-input.php?redirect=purchase-input.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i>
                                    ログインして購入
                                </a>
                                <a href="customer-input.php" class="btn btn-outline">
                                    <i class="fas fa-user-plus"></i>
                                    新規会員登録
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- セキュリティ・信頼性表示 -->
                    <div class="security-badges">
                        <div class="security-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>SSL暗号化通信</span>
                        </div>
                        <div class="security-item">
                            <i class="fas fa-undo-alt"></i>
                            <span>30日間返品保証</span>
                        </div>
                        <div class="security-item">
                            <i class="fas fa-truck-fast"></i>
                            <span>最短翌日お届け</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- おすすめ商品 -->
        <div class="cart-recommendations">
            <h3>
                <i class="fas fa-lightbulb"></i>
                こちらもおすすめ
            </h3>
            <div class="recommendation-grid">
                <?php
                // カート内商品に関連するおすすめ商品を取得
                $recommend_sql = $pdo->prepare('SELECT * FROM product WHERE recommend = 1 AND id NOT IN (' .
                    implode(',', array_map(function ($item) {
                        return $item['id'];
                    }, $_SESSION['product'])) .
                    ') ORDER BY sales_quantity DESC LIMIT 3');
                $recommend_sql->execute();

                while ($rec_product = $recommend_sql->fetch()) {
                    $rec_images = getImage($rec_product['id'], $pdo);
                ?>
                    <div class="recommendation-item">
                        <div class="rec-image">
                            <img src="images/<?= $rec_images[0] ?>.jpg" alt="<?= h($rec_product['name']) ?>">
                        </div>
                        <div class="rec-info">
                            <h4><?= h($rec_product['name']) ?></h4>
                            <p class="rec-price">¥<?= number_format($rec_product['price']) ?></p>
                            <a href="detail.php?id=<?= $rec_product['id'] ?>" class="btn btn-sm">詳細を見る</a>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        // カート機能のJavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // 数量変更
            document.querySelectorAll('.qty-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const input = document.querySelector(`.qty-input[data-id="${id}"]`);
                    const isIncrease = this.classList.contains('qty-increase');
                    const currentVal = parseInt(input.value);

                    if (isIncrease && currentVal < 10) {
                        input.value = currentVal + 1;
                    } else if (!isIncrease && currentVal > 1) {
                        input.value = currentVal - 1;
                    }

                    updateQuantity(id, input.value);
                });
            });

            // 数量入力
            document.querySelectorAll('.qty-input').forEach(input => {
                input.addEventListener('change', function() {
                    const id = this.dataset.id;
                    let value = parseInt(this.value);

                    if (value < 1) value = 1;
                    if (value > 10) value = 10;
                    this.value = value;

                    updateQuantity(id, value);
                });
            });

            // 削除ボタン
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const itemName = this.closest('.cart-item').querySelector('.item-name a').textContent;

                    if (confirm(`「${itemName}」をカートから削除しますか？`)) {
                        window.location.href = `cart-delete.php?id=${id}`;
                    }
                });
            });

            function updateQuantity(id, quantity) {
                // cart-insert.phpを使用して数量更新
                const cartItem = <?= json_encode($_SESSION['product']) ?>[id];
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'cart-insert.php';

                // 既存の商品情報を使用
                const inputs = {
                    'id': cartItem.id,
                    'name': cartItem.name,
                    'price': cartItem.price,
                    'count': quantity,
                    'tax': cartItem.tax,
                    'col': cartItem.col,
                    'update_mode': '1' // 更新フラグ
                };

                Object.keys(inputs).forEach(key => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = inputs[key];
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }
        });
    </script>

<?php
} else {
?>
    <div class="empty-cart">
        <div class="empty-cart-content">
            <div class="empty-cart-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h2>カートは空です</h2>
            <p>お気に入りの商品をカートに追加しましょう</p>

            <div class="empty-cart-actions">
                <a href="product.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    商品を探す
                </a>

                <?php if (isset($_SESSION['customer'])): ?>
                    <a href="favorite-show.php" class="btn btn-outline">
                        <i class="fas fa-heart"></i>
                        お気に入りを見る
                    </a>
                <?php endif; ?>
            </div>

            <!-- 人気商品の表示 -->
            <div class="popular-products">
                <h3>人気商品</h3>
                <div class="popular-grid">
                    <?php
                    $popular_sql = $pdo->prepare('SELECT * FROM product WHERE recommend = 1 ORDER BY sales_quantity DESC LIMIT 4');
                    $popular_sql->execute();

                    while ($popular = $popular_sql->fetch()) {
                        $pop_images = getImage($popular['id'], $pdo);
                    ?>
                        <div class="popular-item">
                            <a href="detail.php?id=<?= $popular['id'] ?>">
                                <img src="images/<?= $pop_images[0] ?>.jpg" alt="<?= h($popular['name']) ?>">
                                <h4><?= h($popular['name']) ?></h4>
                                <p class="price">¥<?= number_format($popular['price']) ?></p>
                            </a>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php
}
?>
<style>
    /* カート内ポイント情報のスタイル */
    .points-info-section {
        margin: 20px 0;
    }

    .points-separator {
        height: 1px;
        background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
        margin: 20px 0;
    }

    .points-header h4 {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 15px;
        border-bottom: 1px solid #f3f4f6;
        padding-bottom: 10px;
    }

    .points-header i {
        color: #f59e0b;
        font-size: 1.2rem;
    }

    .points-details {
        background: #f8fafc;
        border-radius: 8px;
        padding: 15px;
        border: 1px solid #e5e7eb;
    }

    .points-row {
        border-bottom: 1px solid #e5e7eb !important;
        padding: 10px 0 !important;
    }

    .points-row:last-of-type {
        border-bottom: none !important;
        margin-bottom: 10px;
    }

    .points-value {
        font-weight: 600;
        color: #f59e0b;
    }

    .points-value.highlight {
        color: #10b981;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .expected-points {
        background: rgba(16, 185, 129, 0.05);
        margin: 8px -10px;
        padding: 12px 10px !important;
        border-radius: 6px;
        border: 1px solid rgba(16, 185, 129, 0.2) !important;
    }

    .points-note {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 6px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #0369a1;
        font-size: 0.85rem;
        line-height: 1.4;
    }

    .points-note i {
        color: #0ea5e9;
        flex-shrink: 0;
    }

    /* ログインしていない場合のポイント促進 */
    .points-login-prompt {
        margin: 20px 0;
    }

    .login-for-points {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .login-for-points::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        animation: float 4s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-10px) rotate(180deg);
        }
    }

    .login-for-points>i {
        font-size: 2rem;
        margin-bottom: 10px;
        display: block;
        animation: coinSpin 3s linear infinite;
    }

    @keyframes coinSpin {
        0% {
            transform: rotateY(0deg);
        }

        100% {
            transform: rotateY(360deg);
        }
    }

    .login-message {
        position: relative;
        z-index: 1;
    }

    .login-message p {
        margin-bottom: 8px;
        line-height: 1.4;
    }

    .login-message p:first-child {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .login-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 15px;
    }

    .login-actions .btn {
        padding: 8px 16px;
        font-size: 0.9rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
    }

    .login-actions .btn-primary {
        background: rgba(255, 255, 255, 0.9);
        color: #d97706;
        border: none;
    }

    .login-actions .btn-primary:hover {
        background: white;
        transform: translateY(-1px);
    }

    .login-actions .btn-outline {
        background: transparent;
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.7);
    }

    .login-actions .btn-outline:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: white;
    }

    /* レスポンシブ対応 */
    @media (max-width: 768px) {
        .points-details {
            padding: 12px;
        }

        .login-for-points {
            padding: 15px;
        }

        .login-actions {
            flex-direction: column;
            align-items: center;
        }

        .login-actions .btn {
            width: 100%;
            max-width: 200px;
        }

        .points-header h4 {
            font-size: 1rem;
        }
    }
</style>