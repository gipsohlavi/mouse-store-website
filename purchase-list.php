<?php
session_start();
require 'common.php';
require 'admin-header.php';
require 'admin-menu.php';

// 検索・フィルタ処理
$search_keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';
$date_from = isset($_REQUEST['date_from']) ? $_REQUEST['date_from'] : '';
$date_to = isset($_REQUEST['date_to']) ? $_REQUEST['date_to'] : '';
$amount_filter = isset($_REQUEST['amount']) ? $_REQUEST['amount'] : '';
$sort_order = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'date_desc';

// WHERE条件の構築
$where_conditions = [];
$params = [];

if (!empty($search_keyword)) {
    $where_conditions[] = "c.name LIKE ?";
    $params[] = '%' . $search_keyword . '%';
}

if (!empty($date_from)) {
    $where_conditions[] = "p.purchase_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "p.purchase_date <= ?";
    $params[] = $date_to;
}

if (!empty($amount_filter)) {
    switch ($amount_filter) {
        case 'small':
            $where_conditions[] = "p.grand_total < 10000";
            break;
        case 'medium':
            $where_conditions[] = "p.grand_total >= 10000 AND p.grand_total < 50000";
            break;
        case 'large':
            $where_conditions[] = "p.grand_total >= 50000";
            break;
    }
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// ORDER BY句の構築
$order_clause = 'ORDER BY ';
switch ($sort_order) {
    case 'date_asc':
        $order_clause .= 'p.purchase_date ASC';
        break;
    case 'amount_high':
        $order_clause .= 'p.grand_total DESC';
        break;
    case 'amount_low':
        $order_clause .= 'p.grand_total ASC';
        break;
    case 'customer':
        $order_clause .= 'c.name ASC';
        break;
    default:
        $order_clause .= 'p.purchase_date DESC, p.id DESC';
}

// 購入履歴データ取得
$sql_query = "
    SELECT 
        p.id as purchase_id,
        p.*,
        c.name as customer_name,
        c.id as customer_id
    FROM purchase p
    INNER JOIN customer c ON p.customer_id = c.id
    $where_clause
    $order_clause
";

$sql = $pdo->prepare($sql_query);
$sql->execute($params);
$purchases = $sql->fetchAll();

// 税率情報取得
$tax_sql = $pdo->prepare('SELECT tax_id, tax FROM tax WHERE tax_end_date IS NULL ORDER BY tax_id');
$tax_sql->execute();
$tax_rates = $tax_sql->fetchAll();
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-receipt"></i> 購入履歴管理</h2>
        <p class="page-description">顧客の購入履歴・伝票の管理を行います</p>
    </div>

    <!-- 統計カード -->
    <div class="stats-grid">
        <?php
        $stats_sql = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(grand_total) as total_sales,
                AVG(grand_total) as avg_order,
                COUNT(CASE WHEN purchase_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_orders
            FROM purchase
        ");
        $stats_sql->execute();
        $stats = $stats_sql->fetch();
        ?>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_orders']) ?></div>
                <div class="stat-label">総注文数</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <i class="fas fa-yen-sign"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">¥<?= number_format($stats['total_sales']) ?></div>
                <div class="stat-label">総売上</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">¥<?= number_format($stats['avg_order']) ?></div>
                <div class="stat-label">平均注文額</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['recent_orders']) ?></div>
                <div class="stat-label">30日以内の注文</div>
            </div>
        </div>
    </div>

    <!-- 検索・フィルタ -->
    <div class="admin-card">
        <form action="purchase-list.php" method="post" class="purchase-search-form">
            <div class="search-grid">
                <div class="search-group">
                    <label for="keyword" class="search-label">顧客名検索</label>
                    <input type="text" id="keyword" name="keyword" 
                           value="<?= h($search_keyword) ?>" 
                           placeholder="顧客名で検索..."
                           class="admin-input">
                </div>

                <div class="search-group">
                    <label for="date_from" class="search-label">開始日</label>
                    <input type="date" id="date_from" name="date_from" 
                           value="<?= h($date_from) ?>" class="admin-input">
                </div>

                <div class="search-group">
                    <label for="date_to" class="search-label">終了日</label>
                    <input type="date" id="date_to" name="date_to" 
                           value="<?= h($date_to) ?>" class="admin-input">
                </div>

                <div class="search-group">
                    <label for="amount" class="search-label">金額帯</label>
                    <select id="amount" name="amount" class="admin-input">
                        <option value="">すべて</option>
                        <option value="small" <?= $amount_filter === 'small' ? 'selected' : '' ?>>1万円未満</option>
                        <option value="medium" <?= $amount_filter === 'medium' ? 'selected' : '' ?>>1万円〜5万円</option>
                        <option value="large" <?= $amount_filter === 'large' ? 'selected' : '' ?>>5万円以上</option>
                    </select>
                </div>

                <div class="search-group">
                    <label for="sort" class="search-label">並び順</label>
                    <select id="sort" name="sort" class="admin-input">
                        <option value="date_desc" <?= $sort_order === 'date_desc' ? 'selected' : '' ?>>日付（新しい順）</option>
                        <option value="date_asc" <?= $sort_order === 'date_asc' ? 'selected' : '' ?>>日付（古い順）</option>
                        <option value="amount_high" <?= $sort_order === 'amount_high' ? 'selected' : '' ?>>金額（高い順）</option>
                        <option value="amount_low" <?= $sort_order === 'amount_low' ? 'selected' : '' ?>>金額（安い順）</option>
                        <option value="customer" <?= $sort_order === 'customer' ? 'selected' : '' ?>>顧客名順</option>
                    </select>
                </div>

                <div class="search-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <i class="fas fa-search"></i> 検索
                    </button>
                    <a href="purchase-list.php" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-undo"></i> リセット
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- 購入履歴テーブル -->
    <div class="admin-card">
        <div class="table-header">
            <h3><i class="fas fa-list"></i> 購入履歴一覧 (<?= count($purchases) ?>件)</h3>
            <div class="table-actions">
                <button type="button" class="admin-btn admin-btn-secondary" onclick="exportPurchases()">
                    <i class="fas fa-download"></i> CSVエクスポート
                </button>
                <button type="button" class="admin-btn admin-btn-primary" onclick="printReport()">
                    <i class="fas fa-print"></i> 売上レポート
                </button>
            </div>
        </div>

        <?php if (count($purchases) > 0): ?>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">伝票ID</th>
                            <th style="width: 120px;">購入日</th>
                            <th style="width: 150px;">顧客情報</th>
                            <th style="width: 120px;">合計金額</th>
                            <?php foreach ($tax_rates as $tax): ?>
                                <th style="width: 100px;">税<?= ($tax['tax'] * 100) ?>%</th>
                            <?php endforeach; ?>
                            <th style="width: 80px;">獲得P</th>
                            <th style="width: 80px;">使用P</th>
                            <th style="width: 80px;">送料</th>
                            <th style="width: 120px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchases as $row): ?>
                            <?php
                            // 税額情報を取得
                            $tax_info = [];
                            $tax_sql = $pdo->prepare('SELECT tax_id, tax_amount FROM tax_total WHERE id = ?');
                            $tax_sql->execute([$row['purchase_id']]);
                            foreach ($tax_sql as $tax_data) {
                                $tax_info[$tax_data['tax_id']] = $tax_data['tax_amount'];
                            }
                            ?>
                            <tr>
                                <td class="admin-text-center">
                                    <strong><?= $row['purchase_id'] ?></strong>
                                </td>
                                <td class="date-cell">
                                    <div class="date-display">
                                        <div class="date-main"><?= date('Y/m/d', strtotime($row['purchase_date'])) ?></div>
                                        <div class="date-time"><?= date('H:i', strtotime($row['purchase_date'])) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-name">
                                            <a href="customer-detail.php?id=<?= $row['customer_id'] ?>" class="customer-link">
                                                <?= h($row['customer_name']) ?>
                                            </a>
                                        </div>
                                        <div class="customer-id">ID: <?= $row['customer_id'] ?></div>
                                    </div>
                                </td>
                                <td class="amount-cell">
                                    <div class="amount-display">
                                        <div class="amount-main">¥<?= number_format($row['grand_total']) ?></div>
                                        <?php if ($row['grand_total'] >= 50000): ?>
                                            <div class="amount-badge high">高額</div>
                                        <?php elseif ($row['grand_total'] >= 10000): ?>
                                            <div class="amount-badge medium">中額</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php foreach ($tax_rates as $tax): ?>
                                    <td class="admin-text-right">
                                        ¥<?= number_format($tax_info[$tax['tax_id']] ?? 0) ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="admin-text-center">
                                    <?php if ($row['get_point'] > 0): ?>
                                        <span class="point-badge get">+<?= $row['get_point'] ?>P</span>
                                    <?php else: ?>
                                        <span class="point-zero">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="admin-text-center">
                                    <?php if ($row['use_point'] > 0): ?>
                                        <span class="point-badge use">-<?= $row['use_point'] ?>P</span>
                                    <?php else: ?>
                                        <span class="point-zero">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="admin-text-center">
                                    <?php
                                    // 送料情報を取得（簡易実装）
                                    $sql = $pdo->prepare('SELECT postage_fee_free FROM postage_free 
                                                        WHERE postage_fee_free_id = ?');    
                                    $sql->bindParam(1, $row['postage_free_id']);
                                    $sql->execute();
                                    $postage_free = $sql->fetch();
                                    $postage = $row['grand_total'] > $postage_free ? '有料' : '無料';
                                    ?>
                                    <span class="postage-badge <?= $row['postage_id'] > 0 ? 'paid' : 'free' ?>">
                                        <?= $postage ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" action="purchase-list-detail.php" style="display: inline;">
                                            <input type="hidden" name="id" value="<?= $row['purchase_id'] ?>">
                                            <input type="hidden" name="date" value="<?= $row['purchase_date'] ?>">
                                            <input type="hidden" name="name" value="<?= $row['customer_name'] ?>">
                                            <input type="hidden" name="total" value="<?= $row['grand_total'] ?>">
                                            <button type="submit" class="admin-btn admin-btn-primary admin-btn-xs" title="詳細表示">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </form>
                                        
                                        <button type="button" class="admin-btn admin-btn-secondary admin-btn-xs" 
                                                title="印刷" onclick="printInvoice(<?= $row['purchase_id'] ?>)">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        
                                        <button type="button" class="admin-btn admin-btn-danger admin-btn-xs" 
                                                title="キャンセル" onclick="cancelOrder(<?= $row['purchase_id'] ?>, '<?= h($row['customer_name']) ?>')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-content">
                    <i class="fas fa-receipt"></i>
                    <p>条件に一致する購入履歴が見つかりませんでした</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* 購入履歴管理専用スタイル */
.purchase-search-form {
    background: var(--admin-bg);
    padding: 1.5rem;
    border-radius: 8px;
}

.search-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.search-actions {
    display: flex;
    gap: 0.5rem;
}

.date-cell {
    text-align: center;
}

.date-display {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.date-main {
    font-weight: 600;
    color: var(--admin-text);
    font-size: 0.875rem;
}

.date-time {
    font-size: 0.75rem;
    color: var(--admin-text-light);
}

.customer-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.customer-name {
    font-weight: 600;
}

.customer-link {
    color: var(--admin-text);
    text-decoration: none;
    transition: color 0.2s ease;
}

.customer-link:hover {
    color: var(--admin-primary);
}

.customer-id {
    font-size: 0.75rem;
    color: var(--admin-text-light);
}

.amount-cell {
    text-align: right;
}

.amount-display {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.amount-main {
    font-weight: 700;
    color: var(--admin-text);
    font-size: 1rem;
}

.amount-badge {
    padding: 0.125rem 0.5rem;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 500;
}

.amount-badge.high {
    background: #fee2e2;
    color: #991b1b;
}

.amount-badge.medium {
    background: #fef3c7;
    color: #92400e;
}

.point-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
    min-width: 50px;
    text-align: center;
}

.point-badge.get {
    background: #d1fae5;
    color: #065f46;
}

.point-badge.use {
    background: #dbeafe;
    color: #1e40af;
}

.point-zero {
    color: var(--admin-text-light);
    font-size: 0.875rem;
}

.postage-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    display: inline-block;
}

.postage-badge.free {
    background: #d1fae5;
    color: #065f46;
}

.postage-badge.paid {
    background: #fef3c7;
    color: #92400e;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
    justify-content: center;
}

@media (max-width: 1200px) {
    .search-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .search-actions {
        grid-column: 1 / -1;
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .search-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .table-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .table-actions {
        width: 100%;
        justify-content: stretch;
    }
    
    .table-actions .admin-btn {
        flex: 1;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
// CSVエクスポート
function exportPurchases() {
    const params = new URLSearchParams(window.location.search);
    params.append('export', 'csv');
    window.open('purchase-export.php?' + params.toString(), '_blank');
}

// 売上レポート印刷
function printReport() {
    window.open('purchase-report.php', '_blank');
}

// 伝票印刷
function printInvoice(purchaseId) {
    window.open(`purchase-invoice.php?id=${purchaseId}`, '_blank', 'width=800,height=600');
}

// 注文キャンセル
function cancelOrder(purchaseId, customerName) {
    if (confirm(`${customerName}様の注文（ID: ${purchaseId}）をキャンセルしてもよろしいですか？\n\n※この操作は取り消せません。`)) {
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
                location.reload();
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

// 検索フォームのリアルタイム更新
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.purchase-search-form');
    const inputs = form.querySelectorAll('input[type="date"]');
    
    // 日付の妥当性チェック
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            const fromInput = document.getElementById('date_from');
            const toInput = document.getElementById('date_to');
            
            if (fromInput.value && toInput.value) {
                if (new Date(fromInput.value) > new Date(toInput.value)) {
                    alert('開始日は終了日より前に設定してください。');
                    this.value = '';
                }
            }
        });
    });
});
</script>

<?php require 'admin-footer.php'; ?>