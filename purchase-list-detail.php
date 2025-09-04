<?php
session_start();
require 'common.php';
require 'admin-header.php';
require 'admin-menu.php';

// パラメータ取得
$purchase_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if (!$purchase_id) {
    header('Location: purchase-list.php');
    exit;
}

// 購入データ取得
$purchase_sql = $pdo->prepare('
    SELECT p.*, sa.postal_code, sa.prefecture, sa.city, 
    sa.address_line1, sa.address_line2, sa.region_id, p.remort_island_fee_id 
    FROM purchase p 
    INNER JOIN shipping_addresses sa ON p.address_id = sa.id WHERE p.id = ?
');
$purchase_sql->execute([$purchase_id]);
$purchase = $purchase_sql->fetch();

$postal_code = h($purchase['postal_code']);
// 郵便番号をxxx-xxxxの形式に整形
if (strlen($postal_code) === 7) {
    $formatted_postal_code = substr($postal_code, 0, 3) . '-' . substr($postal_code, 3, 4);
} else {
    // 7桁でない場合はそのまま表示
    $formatted_postal_code = $postal_code;
}

if (!$purchase) {
    header('Location: purchase-list.php');
    exit;
}

//顧客情報取得
$customer_sql = $pdo->prepare('
    SELECT c.name AS customer_name, c.login AS customer_login, 
    c.point AS customer_point, sa.postal_code, sa.prefecture, sa.city, 
    sa.address_line1, sa.address_line2, sa.region_id, sa.remote_island_check
    FROM customer c INNER JOIN shipping_addresses sa ON c.id = sa.customer_id
    WHERE sa.is_default = 1 AND c.id = ?
');
$customer_sql->execute([$purchase['customer_id']]);
$customer = $customer_sql->fetch();

$customer_postal_code = h($customer['postal_code']);
if (strlen($customer_postal_code) === 7) {
    $formatted_customer_postal_code = substr($customer_postal_code, 0, 3) . '-' . substr($customer_postal_code, 3, 4);
} else {
    // 7桁でない場合はそのまま表示
    $formatted_customer_postal_code = $customer_postal_code;
}

// 購入詳細データ取得
$detail_sql = $pdo->prepare('
    SELECT pd.*, pr.name as product_name, pr.price as product_price, pr.image_name1
    FROM purchase_detail pd
    INNER JOIN product pr ON pd.product_id = pr.id
    WHERE pd.purchase_id = ?
    ORDER BY pd.purchase_detail_id
');
$detail_sql->execute([$purchase_id]);
$purchase_details = $detail_sql->fetchAll();

// 税額詳細取得
$tax_sql = $pdo->prepare('
    SELECT tt.*, t.tax
    FROM tax_total tt
    INNER JOIN tax t ON tt.tax_id = t.tax_id
    WHERE tt.id = ?
');
$tax_sql->execute([$purchase_id]);
$tax_details = $tax_sql->fetchAll();

// 送料情報取得
$postage_total = 0;
$postage_info = null;
if ($purchase['postage_id'] > 0) {
    $postage_sql = $pdo->prepare('
        SELECT p.postage_fee, r.region_id
        FROM postage p
        INNER JOIN region r ON p.region_id = r.region_id
        WHERE p.postage_id = ?
    ');
    $postage_sql->execute([$purchase['postage_id']]);
    $postage_info = $postage_sql->fetch();
}

$remote_postage_info = null;
if ($purchase['remort_island_fee_id'] > 0) {
    $remote_postage_sql = $pdo->prepare('
        SELECT remote_island_fee
        FROM postage_remote_island
    ');
    $remote_postage_sql->execute();
    $remote_postage_info = $remote_postage_sql->fetch();
}

// 地域名取得
$region_names = [
    1 => '北海道', 2 => '東北', 3 => '関東・中部', 4 => '近畿', 5 => '中国・四国', 6 => '九州', 7 => '沖縄'
];

$subtotal = 0;
foreach ($purchase_details as $detail) {
    $subtotal += $detail['total'];
}

$total_tax = 0;
foreach ($tax_details as $tax) {
    $total_tax += $tax['tax_amount'];
}
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-receipt"></i> 購入詳細 - 伝票ID: <?= str_pad($purchase_id, 6, '0', STR_PAD_LEFT) ?></h2>
        <p class="page-description"><?= date('Y年m月d日 H:i', strtotime($purchase['purchase_date'])) ?> の注文詳細</p>
    </div>

    <div class="detail-layout">
        <!-- メインコンテンツ -->
        <div class="main-content">
            <!-- 注文サマリー -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> 注文サマリー</h3>
                    <div class="header-actions">
                        <?php if ($purchase['grand_total'] >= 50000): ?>
                            <span class="badge recommend">高額注文</span>
                        <?php endif; ?>
                        <span class="status-badge active">
                            <i class="fas fa-check-circle"></i>
                            完了
                        </span>
                    </div>
                </div>

                <div class="purchase-summary">
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-label">
                                <i class="fas fa-calendar"></i>
                                注文日時
                            </div>
                            <div class="summary-value">
                                <?= date('Y年m月d日 H:i', strtotime($purchase['purchase_date'])) ?>
                                <span class="weekday">(<?= ['日', '月', '火', '水', '木', '金', '土'][date('w', strtotime($purchase['purchase_date']))] ?>)</span>
                            </div>
                        </div>

                        <div class="summary-item">
                            <div class="summary-label">
                                <i class="fas fa-yen-sign"></i>
                                合計金額
                            </div>
                            <div class="summary-value highlight">
                                ¥<?= number_format($purchase['grand_total']) ?>
                            </div>
                        </div>

                        <div class="summary-item">
                            <div class="summary-label">
                                <i class="fas fa-coins"></i>
                                ポイント
                            </div>
                            <div class="summary-value">
                                <?php if ($purchase['get_point'] > 0 || $purchase['use_point'] > 0): ?>
                                    <?php if ($purchase['use_point'] > 0): ?>
                                        <span class="point-used">-<?= $purchase['use_point'] ?>P 使用</span>
                                    <?php endif; ?>
                                    <?php if ($purchase['get_point'] > 0): ?>
                                        <span class="point-earned">+<?= $purchase['get_point'] ?>P 獲得</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="point-none">ポイント利用なし</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="summary-item">
                            <div class="summary-label">
                                <i class="fas fa-truck"></i>
                                配送料
                            </div>
                            <div class="summary-value">
                                <?php if ($purchase['postage_id'] > 0 && $postage_info): ?>
                                    ¥<?= number_format($postage_info['postage_fee']) ?>
                                    <?php if ($purchase['remort_island_fee_id']): ?>
                                        <span class="island-fee">+離島料金</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="free-shipping">送料無料</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="summary-item">
                        <div class="summary-label">
                                <i class="fas fa-map-marker-alt"></i>
                                配送先
                            </div>
                            <div class="summary-value">
                                〒<?= h($formatted_postal_code) ?><br>
                                <?= h($purchase['prefecture']) ?><?= h($purchase['city']) ?>
                                <?= h($purchase['address_line1']) ?><?= h($purchase['address_line2']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 注文商品 -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-box"></i> 注文商品 (<?= count($purchase_details) ?>点)</h3>
                </div>

                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">商品画像</th>
                                <th>商品名</th>
                                <th style="width: 80px;">単価</th>
                                <th style="width: 80px;">数量</th>
                                <th style="width: 100px;">小計</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchase_details as $detail): ?>
                                <tr>
                                    <td class="product-image-cell">
                                        <div class="product-image">
                                            <img src="images/<?= h($detail['image_name1']) ?>.jpg" 
                                                 alt="<?= h($detail['product_name']) ?>"
                                                 onerror="this.src='image/no-image.jpg'">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-name">
                                                <a href="product-detail.php?id=<?= $detail['product_id'] ?>" class="product-link" target="_blank">
                                                    <?= h($detail['product_name']) ?>
                                                </a>
                                            </div>
                                            <div class="product-meta">
                                                商品ID: <?= $detail['product_id'] ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="admin-text-right">
                                        ¥<?= number_format($detail['unit_price']) ?>
                                    </td>
                                    <td class="admin-text-center">
                                        <span class="quantity"><?= $detail['count'] ?></span>
                                    </td>
                                    <td class="admin-text-right">
                                        <strong>¥<?= number_format($detail['total']) ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="subtotal-row">
                                <td colspan="4" class="admin-text-right"><strong>小計:</strong></td>
                                <td class="admin-text-right"><strong>¥<?= number_format($subtotal) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- 金額詳細 -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-calculator"></i> 金額詳細</h3>
                </div>

                <div class="amount-breakdown">
                    <div class="breakdown-item">
                        <span class="breakdown-label">商品小計</span>
                        <span class="breakdown-value">¥<?= number_format($subtotal) ?></span>
                    </div>

                    <?php foreach ($tax_details as $tax): ?>
                        <div class="breakdown-item">
                            <span class="breakdown-label">消費税 (<?= ($tax['tax'] * 100) ?>%)</span>
                            <span class="breakdown-value">¥<?= number_format($tax['tax_amount']) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($purchase['use_point'] > 0): ?>
                        <div class="breakdown-item discount">
                            <span class="breakdown-label">ポイント利用</span>
                            <span class="breakdown-value">-¥<?= number_format($purchase['use_point']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($purchase['postage_id'] > 0 && $postage_info): ?>
                        <div class="breakdown-item">
                            <span class="breakdown-label">配送料</span>
                            <span class="breakdown-value">¥<?= number_format($postage_info['postage_fee']) ?></span>
                        </div>
                        <?php if ($purchase['remort_island_fee_id']): ?>
                            <div class="breakdown-item">
                                <span class="breakdown-label">離島料金</span>
                                <span class="breakdown-value">¥<?= number_format($remote_postage_info['remote_island_fee']) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="breakdown-total">
                        <span class="breakdown-label">合計金額</span>
                        <?php 
                        if (isset($postage_info['postage_fee'])) {
                            $postage_total += $postage_info['postage_fee'];
                        }
                        if (isset($remote_postage_info['remote_island_fee'])) {
                            $postage_total += $remote_postage_info['remote_island_fee'];
                        } ?>
                        <span class="breakdown-value">¥<?= number_format($purchase['grand_total'] + $postage_total) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- サイドバー -->
        <div class="sidebar-content">
            <!-- 顧客情報 -->
            <div class="admin-card customer-profile-card">
                <div class="card-header">
                    <h3><i class="fas fa-user"></i> 顧客情報</h3>
                    <div class="header-actions">
                        <a href="customer-detail.php?id=<?= $purchase['customer_id'] ?>" class="admin-btn admin-btn-xs admin-btn-secondary">
                            詳細
                        </a>
                    </div>
                </div>

                <div class="customer-summary">
                    <div class="customer-name-section">
                        <div class="customer-name"><?= h($customer['customer_name']) ?></div>
                        <div class="customer-id">ID: <?= $purchase['customer_id'] ?></div>
                    </div>

                    <div class="customer-details">
                        <div class="detail-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <div class="info-label">ログインID</div>
                                <div class="info-value"><?= h($customer['customer_login']) ?></div>
                            </div>
                        </div>

                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <div class="info-label">住所</div>
                                <div class="info-value">
                                    〒<?= $formatted_customer_postal_code ?><br>
                                    <?= h($customer['prefecture']) ?><?= h($customer['city']) ?><br>
                                    <?= h($customer['address_line1']) ?><?= h($customer['address_line2']) ?><br>
                                    <span class="region-name"><?= $region_names[$customer['region_id']] ?? '不明' ?></span>
                                    <?php if ($customer['remote_island_check'] === 1): ?>
                                        <span class="island-indicator">
                                            <i class="fas fa-island-tropical"></i> 離島
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="detail-item">
                            <i class="fas fa-coins"></i>
                            <div>
                                <div class="info-label">保有ポイント</div>
                                <div class="info-value points-highlight"><?= number_format($customer['customer_point']) ?>P</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- アクション -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-tools"></i> アクション</h3>
                </div>

                <div class="action-menu">
                    <button type="button" class="action-item" onclick="printInvoice(<?= $purchase_id ?>)">
                        <i class="fas fa-print"></i>
                        伝票印刷
                    </button>
                    
                    <button type="button" class="action-item" onclick="sendEmail(<?= $purchase_id ?>)">
                        <i class="fas fa-envelope"></i>
                        メール送信
                    </button>
                    
                    <a href="purchase-edit.php?id=<?= $purchase_id ?>" class="action-item">
                        <i class="fas fa-edit"></i>
                        注文編集
                    </a>
                    
                    <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 0.5rem 0;">
                    
                    <button type="button" class="action-item danger" onclick="cancelOrder(<?= $purchase_id ?>, '<?= h($custoemr['customer_name']) ?>')">
                        <i class="fas fa-times"></i>
                        注文キャンセル
                    </button>
                </div>
            </div>

            <!-- 履歴・メモ -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> 注文履歴</h3>
                </div>

                <div class="order-timeline">
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title">注文受付</div>
                            <div class="timeline-date"><?= date('Y/m/d H:i', strtotime($purchase['purchase_date'])) ?></div>
                        </div>
                    </div>

                    <?php if ($purchase['get_point'] > 0): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon success">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">ポイント付与</div>
                                <div class="timeline-date"><?= $purchase['get_point'] ?>ポイント付与</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ナビゲーション -->
    <div class="navigation-actions">
        <a href="purchase-list.php" class="admin-btn admin-btn-outline">
            <i class="fas fa-arrow-left"></i> 一覧に戻る
        </a>
        
        <?php
        // 前後の注文ID取得
        $prev_sql = $pdo->prepare('SELECT id FROM purchase WHERE id < ? ORDER BY id DESC LIMIT 1');
        $prev_sql->execute([$purchase_id]);
        $prev_purchase = $prev_sql->fetch();

        $next_sql = $pdo->prepare('SELECT id FROM purchase WHERE id > ? ORDER BY id ASC LIMIT 1');
        $next_sql->execute([$purchase_id]);
        $next_purchase = $next_sql->fetch();
        ?>

        <div class="navigation-controls">
            <?php if ($prev_purchase): ?>
                <a href="purchase-detail.php?id=<?= $prev_purchase['id'] ?>" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-chevron-left"></i> 前の注文
                </a>
            <?php endif; ?>
            
            <?php if ($next_purchase): ?>
                <a href="purchase-detail.php?id=<?= $next_purchase['id'] ?>" class="admin-btn admin-btn-secondary">
                    次の注文 <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// 伝票印刷
function printInvoice(purchaseId) {
    window.open(`purchase-invoice.php?id=${purchaseId}`, '_blank', 'width=800,height=600');
}

// メール送信
function sendEmail(purchaseId) {
    if (confirm('顧客にメールを送信しますか？')) {
        // メール送信処理
        fetch('purchase-email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${purchaseId}&action=send_receipt`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('メールを送信しました。');
            } else {
                alert('メール送信に失敗しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('メール送信中にエラーが発生しました。');
        });
    }
}

// 注文キャンセル
function cancelOrder(purchaseId, customerName) {
    if (confirm(`${customerName}様の注文をキャンセルしてもよろしいですか？\n\n※この操作は取り消せません。`)) {
        fetch('purchase-cancel.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${purchaseId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('注文をキャンセルしました。');
                window.location.href = 'purchase-list.php';
            } else {
                alert('キャンセル処理に失敗しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('キャンセル処理中にエラーが発生しました。');
        });
    }
}
</script>

<style>
/* 購入詳細ページ専用スタイル */
.purchase-summary {
    margin-top: 1rem;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.summary-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    background: var(--admin-bg);
    border-radius: 8px;
}

.summary-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--admin-text-light);
}

.summary-value {
    font-weight: 600;
    color: var(--admin-text);
}

.summary-value.highlight {
    font-size: 1.25rem;
    color: var(--admin-primary);
}

.weekday {
    font-size: 0.875rem;
    color: var(--admin-text-light);
}

.point-used {
    color: var(--admin-danger);
    margin-right: 0.5rem;
}

.point-earned {
    color: var(--admin-success);
}

.point-none {
    color: var(--admin-text-light);
}

.free-shipping {
    color: var(--admin-success);
    font-weight: 600;
}

.island-fee {
    font-size: 0.75rem;
    color: var(--admin-warning);
    display: block;
}

.product-image-cell {
    text-align: center;
}

.product-image {
    width: 60px;
    height: 60px;
    margin: 0 auto;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 4px;
}

.product-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.product-name {
    font-weight: 600;
}

.product-link {
    color: var(--admin-text);
    text-decoration: none;
    transition: color 0.2s ease;
}

.product-link:hover {
    color: var(--admin-primary);
}

.product-meta {
    font-size: 0.75rem;
    color: var(--admin-text-light);
}

.quantity {
    font-weight: 600;
    color: var(--admin-primary);
    background: var(--admin-bg);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.subtotal-row {
    background: var(--admin-bg);
    border-top: 2px solid var(--admin-border);
}

.amount-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--admin-border);
}

.breakdown-item:last-child {
    border-bottom: none;
}

.breakdown-item.discount .breakdown-label {
    color: var(--admin-danger);
}

.breakdown-item.discount .breakdown-value {
    color: var(--admin-danger);
}

.breakdown-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0 0;
    border-top: 2px solid var(--admin-border);
    font-size: 1.125rem;
    font-weight: 700;
}

.breakdown-total .breakdown-value {
    color: var(--admin-primary);
}

.customer-summary {
    margin-top: 1rem;
}

.customer-name-section {
    text-align: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--admin-border);
}

.customer-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--admin-text);
    margin-bottom: 0.25rem;
}

.customer-id {
    font-size: 0.875rem;
    color: var(--admin-text-light);
}

.customer-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.detail-item i {
    color: var(--admin-primary);
    width: 16px;
    margin-top: 0.25rem;
}

.info-label {
    font-size: 0.875rem;
    color: var(--admin-text-light);
    margin-bottom: 0.25rem;
}

.info-value {
    font-weight: 500;
    color: var(--admin-text);
    line-height: 1.4;
}

.region-name {
    font-size: 0.875rem;
    color: var(--admin-primary);
    font-weight: 500;
}

.island-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--admin-warning);
    margin-left: 0.5rem;
}

.order-timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.timeline-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.timeline-icon {
    width: 36px;
    height: 36px;
    background: var(--admin-primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.timeline-icon.success {
    background: var(--admin-success);
}

.timeline-content {
    flex: 1;
}

.timeline-title {
    font-weight: 600;
    color: var(--admin-text);
    margin-bottom: 0.25rem;
}

.timeline-date {
    font-size: 0.875rem;
    color: var(--admin-text-light);
}

.navigation-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--admin-border);
}

.navigation-controls {
    display: flex;
    gap: 0.5rem;
}

@media (max-width: 1200px) {
    .detail-layout {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

@media (max-width: 768px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }
    
    .navigation-actions {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .navigation-controls {
        justify-content: space-between;
    }
}
</style>

<?php require 'admin-footer.php'; ?>