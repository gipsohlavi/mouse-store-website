<?php require 'common.php'; ?>
<?php require 'admin-header.php'; ?>
<?php require 'admin-menu.php'; ?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-users"></i> 顧客管理</h2>
        <p class="page-description">登録顧客の情報を管理します</p>
    </div>

    <!-- 検索・フィルター機能 -->
    <div class="admin-card">
        <h3><i class="fas fa-search"></i> 顧客検索</h3>
        <form method="get" class="customer-search-form">
            <div class="search-grid">
                <div class="search-group">
                    <label class="search-label">氏名・ログインID</label>
                    <input type="text" name="search" class="admin-input"
                        placeholder="検索キーワードを入力"
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="search-group">
                    <label class="search-label">地域</label>
                    <select name="region" class="admin-input">
                        <option value="">すべての地域</option>
                        <?php
                        $regions = [
                            1 => '北海道',
                            2 => '東北',
                            3 => '関東・中部',
                            4 => '近畿',
                            5 => '中国・四国',
                            6 => '九州',
                            7 => '沖縄'
                        ];
                        foreach ($regions as $id => $name) {
                            $selected = ($_GET['region'] ?? '') == $id ? 'selected' : '';
                            echo "<option value=\"$id\" $selected>$name</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="search-group">
                    <label class="search-label">ポイント</label>
                    <select name="point_range" class="admin-input">
                        <option value="">すべて</option>
                        <option value="0" <?= ($_GET['point_range'] ?? '') === '0' ? 'selected' : '' ?>>0ポイント</option>
                        <option value="1-100" <?= ($_GET['point_range'] ?? '') === '1-100' ? 'selected' : '' ?>>1-100ポイント</option>
                        <option value="101-500" <?= ($_GET['point_range'] ?? '') === '101-500' ? 'selected' : '' ?>>101-500ポイント</option>
                        <option value="501+" <?= ($_GET['point_range'] ?? '') === '501+' ? 'selected' : '' ?>>501ポイント以上</option>
                    </select>
                </div>
                <div class="search-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <i class="fas fa-search"></i> 検索
                    </button>
                    <a href="customer-list.php" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-times"></i> クリア
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- 統計情報 -->
    <?php
    $stats_sql = $pdo->prepare('SELECT 
        COUNT(*) as total_customers,
        AVG(point) as avg_points,
        SUM(point) as total_points,
        COUNT(CASE WHEN point > 0 THEN 1 END) as customers_with_points
        FROM customer');
    $stats_sql->execute();
    $stats = $stats_sql->fetch();
    ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_customers']) ?></div>
                <div class="stat-label">総顧客数</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-coins"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_points']) ?></div>
                <div class="stat-label">総保有ポイント</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['avg_points'], 0) ?></div>
                <div class="stat-label">平均保有ポイント</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['customers_with_points']) ?></div>
                <div class="stat-label">ポイント保有者</div>
            </div>
        </div>
    </div>

    <!-- 顧客一覧 -->
    <div class="admin-card">
        <div class="table-header">
            <h3><i class="fas fa-list"></i> 顧客一覧</h3>
            <div class="table-actions">
                <a href="customer-list-add.php" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus"></i> 新規顧客追加
                </a>
            </div>
        </div>

        <?php
        // 検索条件の構築
        $where_conditions = [];
        $params = [];

        if (!empty($_GET['search'])) {
            $where_conditions[] = "(c.name LIKE ? OR c.login LIKE ?)";
            $search_term = '%' . $_GET['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        if (!empty($_GET['region'])) {
            $where_conditions[] = "sa.region_id = ?";
            $params[] = $_GET['region'];
        }

        if (!empty($_GET['point_range'])) {
            switch ($_GET['point_range']) {
                case '0':
                    $where_conditions[] = "c.point = 0";
                    break;
                case '1-100':
                    $where_conditions[] = "c.point BETWEEN 1 AND 100";
                    break;
                case '101-500':
                    $where_conditions[] = "c.point BETWEEN 101 AND 500";
                    break;
                case '501+':
                    $where_conditions[] = "c.point >= 501";
                    break;
            }
        }

        $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

        $sql = $pdo->prepare("
            SELECT DISTINCT c.*, 
                sa.postal_code, sa.prefecture, sa.city, sa.address_line1, sa.address_line2,
                sa.region_id, sa.remote_island_check
            FROM customer c
            LEFT JOIN shipping_addresses sa ON c.id = sa.customer_id AND sa.is_default = 1
            $where_clause 
            ORDER BY c.id DESC
        ");
        $sql->execute($params);
        $customers = $sql->fetchAll();
        ?>

        <div class="table-container">
            <table class="admin-table customer-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>顧客情報</th>
                        <th>デフォルト住所</th>
                        <th>ログイン情報</th>
                        <th>ポイント</th>
                        <th>地域</th>
                        <th>離島</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($customers) > 0): ?>
                        <?php foreach ($customers as $row): ?>
                            <tr class="customer-row" data-customer-id="<?= $row['id'] ?>">
                                <td class="id-cell">
                                    <span class="customer-id">#<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                </td>
                                <td class="customer-info-cell">
                                    <div class="customer-info">
                                        <div class="customer-name"><?= htmlspecialchars($row['name']) ?></div>
                                        <div class="customer-meta">
                                            <span class="postal-code">
                                                <i class="fas fa-map-pin"></i>
                                                <?= $row['postal_code'] ? '〒' . htmlspecialchars($row['postal_code']) : '未設定' ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="address-cell">
                                    <?php if ($row['prefecture']): ?>
                                        <div class="address-display" title="<?= htmlspecialchars($row['prefecture'] . $row['city'] . $row['address_line1']) ?>">
                                            <?= htmlspecialchars(mb_strimwidth($row['prefecture'] . $row['city'] . $row['address_line1'], 0, 30, '...')) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-address">住所未登録</span>
                                    <?php endif; ?>
                                </td>
                                <td class="login-info-cell">
                                    <div class="login-info">
                                        <div class="login-id">
                                            <i class="fas fa-user"></i>
                                            <?= htmlspecialchars($row['login']) ?>
                                        </div>
                                        <div class="password-display">
                                            <i class="fas fa-lock"></i>
                                            <span class="password-masked">••••••••</span>
                                            <button class="password-toggle" onclick="togglePassword(this, '<?= htmlspecialchars($row['password']) ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="points-cell">
                                    <div class="points-display">
                                        <span class="points-number"><?= number_format($row['point']) ?></span>
                                        <span class="points-unit">pt</span>
                                        <?php if ($row['point'] >= 1000): ?>
                                            <span class="points-badge high">優良</span>
                                        <?php elseif ($row['point'] >= 100): ?>
                                            <span class="points-badge medium">標準</span>
                                        <?php elseif ($row['point'] > 0): ?>
                                            <span class="points-badge low">新規</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="region-cell">
                                    <?php
                                    $region_name = isset($row['region_id']) && $row['region_id'] ? ($regions[$row['region_id']] ?? '未設定') : '未設定';
                                    echo '<span class="region-badge">' . $region_name . '</span>';
                                    ?>
                                </td>
                                <td class="island-cell">
                                    <?php if ($row['remote_island_check']): ?>
                                        <span class="island-badge active">
                                            <i class="fas fa-island-tropical"></i> 離島
                                        </span>
                                    <?php else: ?>
                                        <span class="island-badge inactive">
                                            <i class="fas fa-times"></i> 一般
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <a href="customer-list-detail.php?id=<?= $row['id'] ?>"
                                            class="admin-btn admin-btn-secondary admin-btn-sm"
                                            title="詳細を表示">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="customer-list-edit.php?id=<?= $row['id'] ?>"
                                            class="admin-btn admin-btn-primary admin-btn-sm"
                                            title="編集">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteCustomer(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>')"
                                            class="admin-btn admin-btn-danger admin-btn-sm"
                                            title="削除">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <div class="empty-content">
                                    <i class="fas fa-users-slash"></i>
                                    <p>条件に一致する顧客が見つかりませんでした。</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (count($customers) > 0): ?>
            <div class="table-footer">
                <div class="result-info">
                    <span class="result-count"><?= count($customers) ?> 件の顧客が見つかりました</span>
                </div>
                <div class="pagination-info">
                    <!-- ページネーションは必要に応じて実装 -->
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .no-address {
        color: var(--admin-text-light);
        font-style: italic;
        font-size: 0.875rem;
    }

    .address-display {
        font-size: 0.875rem;
        line-height: 1.4;
    }
</style>

<script>
    // パスワード表示/非表示の切り替え
    function togglePassword(button, password) {
        const maskedSpan = button.previousElementSibling;
        const icon = button.querySelector('i');

        if (maskedSpan.textContent === '••••••••') {
            maskedSpan.textContent = password;
            icon.className = 'fas fa-eye-slash';
            button.title = 'パスワードを隠す';
        } else {
            maskedSpan.textContent = '••••••••';
            icon.className = 'fas fa-eye';
            button.title = 'パスワードを表示';
        }
    }

    // 顧客削除の確認
    function deleteCustomer(id, name) {
        if (confirm(`顧客「${name}」を削除してもよろしいですか？\n\nこの操作は取り消せません。`)) {
            // 削除処理のリダイレクト
            window.location.href = `customer-list-delete.php?id=${id}`;
        }
    }

    // CSVエクスポート機能
    function exportCustomers() {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('export', 'csv');
        window.location.href = currentUrl.toString();
    }

    // テーブル行のハイライト
    document.addEventListener('DOMContentLoaded', function() {
        const tableRows = document.querySelectorAll('.customer-row');

        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'var(--admin-bg)';
            });

            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    });

    // 検索フォームの自動送信（入力後少し待つ）
    let searchTimeout;
    const searchInput = document.querySelector('[name="search"]');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // この部分は必要に応じてAjax検索に変更可能
            }, 500);
        });
    }
</script>

<?php require 'admin-footer.php'; ?>