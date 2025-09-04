<?php
session_start();
require 'common.php';
require 'admin-header.php';
require 'admin-menu.php';

// 検索・フィルタ処理
$search_keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';
$category_filter = isset($_REQUEST['category']) ? $_REQUEST['category'] : '';
$stock_filter = isset($_REQUEST['stock']) ? $_REQUEST['stock'] : '';
$sort_order = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'id';

// WHERE条件の構築
$where_conditions = [];
$params = [];

if (!empty($search_keyword)) {
    $where_conditions[] = "p.name LIKE ?";
    $params[] = '%' . $search_keyword . '%';
}

if (!empty($stock_filter)) {
    switch ($stock_filter) {
        case 'in_stock':
            $where_conditions[] = "p.stock_quantity > 0";
            break;
        case 'low_stock':
            $where_conditions[] = "p.stock_quantity <= 10 AND p.stock_quantity > 0";
            break;
        case 'out_of_stock':
            $where_conditions[] = "p.stock_quantity = 0";
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
    case 'name':
        $order_clause .= 'p.name ASC';
        break;
    case 'price_low':
        $order_clause .= 'p.price ASC';
        break;
    case 'price_high':
        $order_clause .= 'p.price DESC';
        break;
    case 'stock':
        $order_clause .= 'p.stock_quantity DESC';
        break;
    case 'created':
        $order_clause .= 'p.created_day DESC';
        break;
    default:
        $order_clause .= 'p.id ASC';
}

// 商品データ取得
$sql_query = "
    SELECT 
        p.*,
        m_maker.name as maker_name,
        tax.tax
    FROM product p
    LEFT JOIN product_master_relation pmr_maker ON p.id = pmr_maker.product_id AND pmr_maker.kbn_id = 1
    LEFT JOIN master m_maker ON pmr_maker.master_id = m_maker.master_id AND m_maker.kbn = 1
    LEFT JOIN tax ON p.tax_id = tax.tax_id AND tax.tax_end_date IS NULL
    $where_clause
    $order_clause
";

$sql = $pdo->prepare($sql_query);
$sql->execute($params);
$products = $sql->fetchAll();
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-box"></i> 商品管理</h2>
        <p class="page-description">商品の追加・編集・在庫管理を行います</p>
    </div>

    <!-- 統計カード -->
    <div class="stats-grid">
        <?php
        $stats_sql = $pdo->prepare("
            SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN stock_quantity > 0 THEN 1 ELSE 0 END) as in_stock,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN stock_quantity <= 10 AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock
            FROM product
        ");
        $stats_sql->execute();
        $stats = $stats_sql->fetch();
        ?>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['total_products'] ?></div>
                <div class="stat-label">総商品数</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['in_stock'] ?></div>
                <div class="stat-label">在庫あり</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['low_stock'] ?></div>
                <div class="stat-label">在庫少</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['out_of_stock'] ?></div>
                <div class="stat-label">在庫切れ</div>
            </div>
        </div>
    </div>

    <!-- 検索・フィルタ -->
    <div class="admin-card">
        <form action="product-list.php" method="post" class="product-search-form">
            <div class="search-grid">
                <div class="search-group">
                    <label for="keyword" class="search-label">商品名検索</label>
                    <input type="text" id="keyword" name="keyword"
                        value="<?= h($search_keyword) ?>"
                        placeholder="商品名で検索..."
                        class="admin-input">
                </div>

                <div class="search-group">
                    <label for="stock" class="search-label">在庫状況</label>
                    <select id="stock" name="stock" class="admin-input">
                        <option value="">すべて</option>
                        <option value="in_stock" <?= $stock_filter === 'in_stock' ? 'selected' : '' ?>>在庫あり</option>
                        <option value="low_stock" <?= $stock_filter === 'low_stock' ? 'selected' : '' ?>>在庫少</option>
                        <option value="out_of_stock" <?= $stock_filter === 'out_of_stock' ? 'selected' : '' ?>>在庫切れ</option>
                    </select>
                </div>

                <div class="search-group">
                    <label for="sort" class="search-label">並び順</label>
                    <select id="sort" name="sort" class="admin-input">
                        <option value="id" <?= $sort_order === 'id' ? 'selected' : '' ?>>ID順</option>
                        <option value="name" <?= $sort_order === 'name' ? 'selected' : '' ?>>商品名</option>
                        <option value="price_low" <?= $sort_order === 'price_low' ? 'selected' : '' ?>>価格（安い順）</option>
                        <option value="price_high" <?= $sort_order === 'price_high' ? 'selected' : '' ?>>価格（高い順）</option>
                        <option value="stock" <?= $sort_order === 'stock' ? 'selected' : '' ?>>在庫数</option>
                        <option value="created" <?= $sort_order === 'created' ? 'selected' : '' ?>>登録日（新しい順）</option>
                    </select>
                </div>

                <div class="search-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <i class="fas fa-search"></i> 検索
                    </button>
                    <a href="product-list.php" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-undo"></i> リセット
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- 商品テーブル -->
    <div class="admin-card">
        <div class="table-header">
            <h3><i class="fas fa-list"></i> 商品一覧 (<?= count($products) ?>件)</h3>
            <div class="table-actions">
                <a href="product-list-input.php" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus"></i> 新規追加
                </a>
            </div>
        </div>

        <?php if (count($products) > 0): ?>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 80px;">画像</th>
                            <th style="min-width: 200px;">商品名</th>
                            <th style="width: 120px;">メーカー</th>
                            <th style="width: 100px;">価格</th>
                            <th style="width: 80px;">在庫</th>
                            <th style="width: 80px;">状態</th>
                            <th style="width: 100px;">登録日</th>
                            <th style="width: 120px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $row): ?>
                            <tr>
                                <td class="admin-text-center">
                                    <strong><?= $row['id'] ?></strong>
                                </td>
                                <td class="admin-text-center">
                                    <?php
                                    $image_path = "images/{$row['image_name1']}.jpg";
                                    if (!file_exists($image_path)) {
                                        $image_path = "images/no-image.jpg";
                                    }
                                    ?>
                                    <img src="<?= $image_path ?>" alt="<?= h($row['name']) ?>"
                                        style="width: 60px; height: 60px; object-fit: contain; border-radius: 4px; background: #f9fafb;">
                                </td>
                                <td>
                                    <div class="product-info">
                                        <div class="product-name">
                                            <a href="detail.php?id=<?= $row['id'] ?>" target="_blank" class="product-link">
                                                <?= h($row['name']) ?>
                                            </a>
                                        </div>
                                        <div class="product-meta">
                                            <?php if ($row['recommend']): ?>
                                                <span class="badge recommend">おすすめ</span>
                                            <?php endif; ?>
                                            <?php if ($row['on_sale']): ?>
                                                <span class="badge sale">セール</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= h($row['maker_name'] ?: '未設定') ?></td>
                                <td class="admin-text-right">
                                    <strong>¥<?= number_format($row['price']) ?></strong>
                                    <?php if ($row['tax']): ?>
                                        <div style="font-size: 0.75rem; color: #6b7280;">
                                            税込 ¥<?= number_format($row['price'] * (1 + $row['tax'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="admin-text-center">
                                    <?php if ($row['stock_quantity'] > 10): ?>
                                        <span class="stock-good"><?= $row['stock_quantity'] ?></span>
                                    <?php elseif ($row['stock_quantity'] > 0): ?>
                                        <span class="stock-low"><?= $row['stock_quantity'] ?></span>
                                    <?php else: ?>
                                        <span class="stock-out">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="admin-text-center">
                                    <?php if ($row['stock_quantity'] > 0): ?>
                                        <span class="admin-status admin-status-active">
                                            <i class="fas fa-check"></i> 販売中
                                        </span>
                                    <?php else: ?>
                                        <span class="admin-status admin-status-inactive">
                                            <i class="fas fa-ban"></i> 在庫切れ
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.8125rem; color: #6b7280;">
                                    <?= date('Y/m/d', strtotime($row['created_day'])) ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="product-list-edit.php?id=<?= $row['id'] ?>"
                                            class="admin-btn admin-btn-secondary admin-btn-xs"
                                            title="編集">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="product-stock.php?id=<?= $row['id'] ?>"
                                            class="admin-btn admin-btn-primary admin-btn-xs"
                                            title="在庫管理">
                                            <i class="fas fa-cubes"></i>
                                        </a>
                                        <button type="button"
                                            class="admin-btn admin-btn-danger admin-btn-xs"
                                            title="削除"
                                            onclick="deleteProduct(<?= $row['id'] ?>, '<?= h($row['name']) ?>')">
                                            <i class="fas fa-trash"></i>
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
                    <i class="fas fa-box-open"></i>
                    <p>条件に一致する商品が見つかりませんでした</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>



<script>
    function deleteProduct(id, name) {
        if (confirm(`商品「${name}」を削除してもよろしいですか？\n\n※この操作は取り消せません。`)) {
            // 削除処理をAjaxで実行
            fetch('product-delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('商品を削除しました。');
                        location.reload();
                    } else {
                        alert('削除に失敗しました: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('削除中にエラーが発生しました。');
                });
        }
    }

    // 検索フォームのリアルタイム更新
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.product-search-form');
        const inputs = form.querySelectorAll('input, select');

        inputs.forEach(input => {
            input.addEventListener('change', function() {
                // 自動送信を行う場合はここでform.submit()
            });
        });
    });
</script>

<?php require 'admin-footer.php'; ?>