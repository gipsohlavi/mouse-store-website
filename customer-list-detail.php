<?php require 'common.php'; ?>
<?php require 'admin-header.php'; ?>
<?php require 'admin-menu.php'; ?>

<?php
$customer_id = $_GET['id'] ?? 0;

// 顧客情報の取得
$sql = $pdo->prepare('SELECT * FROM customer WHERE id = ?');
$sql->bindParam(1, $customer_id);
$sql->execute();
$customer = $sql->fetch();

if (!$customer) {
    echo '<script>alert("指定された顧客が見つかりません。"); history.back();</script>';
    exit;
}

// デフォルト配送先住所の取得
$address_sql = $pdo->prepare('SELECT * FROM shipping_addresses WHERE customer_id = ? AND is_default = 1 LIMIT 1');
$address_sql->bindParam(1, $customer_id);
$address_sql->execute();
$default_address = $address_sql->fetch();

// 全配送先住所の取得
$all_addresses_sql = $pdo->prepare('SELECT * FROM shipping_addresses WHERE customer_id = ? ORDER BY is_default DESC, id ASC');
$all_addresses_sql->bindParam(1, $customer_id);
$all_addresses_sql->execute();
$all_addresses = $all_addresses_sql->fetchAll();

// 購入履歴の取得
$purchase_sql = $pdo->prepare('
    SELECT p.*, 
        DATE_FORMAT(p.purchase_date, "%Y年%m月%d日") as formatted_date,
        COUNT(pd.product_id) as item_count
    FROM purchase p
    LEFT JOIN purchase_detail pd ON p.id = pd.purchase_id
    WHERE p.customer_id = ?
    GROUP BY p.id
    ORDER BY p.purchase_date DESC
    LIMIT 10
');
$purchase_sql->bindParam(1, $customer_id);
$purchase_sql->execute();
$purchases = $purchase_sql->fetchAll();

// お気に入り商品の取得
$favorite_sql = $pdo->prepare('
    SELECT f.*, p.name as product_name, p.price, p.image_name1
    FROM favorite f
    INNER JOIN product p ON f.product_id = p.id
    WHERE f.customer_id = ?
    ORDER BY f.favorite_date DESC
    LIMIT 10
');
$favorite_sql->bindParam(1, $customer_id);
$favorite_sql->execute();
$favorites = $favorite_sql->fetchAll();

// レビューの取得
$review_sql = $pdo->prepare('
    SELECT r.*, p.name as product_name
    FROM review r
    INNER JOIN product p ON r.product_id = p.id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
');
$review_sql->bindParam(1, $customer_id);
$review_sql->execute();
$reviews = $review_sql->fetchAll();

// 統計情報
$stats_sql = $pdo->prepare('
    SELECT 
        COUNT(DISTINCT p.id) as total_orders,
        COALESCE(SUM(p.grand_total), 0) as total_spent,
        COALESCE(AVG(p.grand_total), 0) as avg_order_value,
        MAX(p.purchase_date) as last_order_date,
        MIN(p.purchase_date) as first_order_date,
        COUNT(DISTINCT f.product_id) as favorite_count,
        COUNT(DISTINCT r.product_id) as review_count
    FROM purchase p
    LEFT JOIN favorite f ON f.customer_id = p.customer_id
    LEFT JOIN review r ON r.customer_id = p.customer_id
    WHERE p.customer_id = ?
');
$stats_sql->bindParam(1, $customer_id);
$stats_sql->execute();
$stats = $stats_sql->fetch();

// 地域情報
$regions = [
    0 => '未設定',
    1 => '北海道',
    2 => '東北',
    3 => '関東・中部',
    4 => '近畿',
    5 => '中国・四国',
    6 => '九州',
    7 => '沖縄'
];
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-user-circle"></i> 顧客詳細情報</h2>
        <p class="page-description">顧客ID: #<?= str_pad($customer['id'], 4, '0', STR_PAD_LEFT) ?> の詳細情報</p>
    </div>

    <div class="detail-layout">
        <!-- 左側：詳細情報 -->
        <div class="main-content">
            <!-- 顧客基本情報カード -->
            <div class="admin-card customer-profile-card">
                <div class="card-header">
                    <h3><i class="fas fa-user"></i> 基本情報</h3>
                    <div class="header-actions">
                        <a href="customer-list-edit.php?id=<?= $customer['id'] ?>"
                            class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-edit"></i> 編集
                        </a>
                    </div>
                </div>

                <div class="profile-content">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                        <?php if ($customer['point'] >= 1000): ?>
                            <span class="vip-badge">VIP</span>
                        <?php endif; ?>
                    </div>

                    <div class="profile-details">
                        <h4 class="customer-name"><?= htmlspecialchars($customer['name']) ?></h4>
                        <p class="customer-id">ID: #<?= str_pad($customer['id'], 4, '0', STR_PAD_LEFT) ?></p>

                        <div class="profile-info-grid">
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <span class="info-label">ログインID</span>
                                    <span class="info-value"><?= htmlspecialchars($customer['login']) ?></span>
                                </div>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-coins"></i>
                                <div>
                                    <span class="info-label">保有ポイント</span>
                                    <span class="info-value points-highlight">
                                        <?= number_format($customer['point']) ?> pt
                                    </span>
                                </div>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-calendar-alt"></i>
                                <div>
                                    <span class="info-label">登録日</span>
                                    <span class="info-value">
                                        <?= date('Y年m月d日', strtotime($customer['created_at'])) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <span class="info-label">最終更新</span>
                                    <span class="info-value">
                                        <?= date('Y年m月d日', strtotime($customer['updated_at'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="customer-list.php" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i> 一覧に戻る
                    </a>
                </div>
            </div>

            <!-- 配送先住所カード -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-map-marker-alt"></i> 配送先住所</h3>
                    <div class="header-actions">
                        <a href="shipping-address-add.php?customer_id=<?= $customer['id'] ?>"
                            class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-plus"></i> 住所追加
                        </a>
                    </div>
                </div>

                <?php if (count($all_addresses) > 0): ?>
                    <div class="address-list">
                        <?php foreach ($all_addresses as $address): ?>
                            <div class="address-item <?= $address['is_default'] ? 'default-address' : '' ?>">
                                <div class="address-header">
                                    <div class="address-name">
                                        <?= htmlspecialchars($address['address_name']) ?>
                                        <?php if ($address['is_default']): ?>
                                            <span class="default-badge">デフォルト</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="address-actions">
                                        <a href="shipping-address-edit.php?id=<?= $address['id'] ?>"
                                            class="admin-btn admin-btn-outline admin-btn-xs">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="address-details">
                                    <div class="recipient-name"><?= htmlspecialchars($address['recipient_name']) ?></div>
                                    <div class="address-full">
                                        <?php if ($address['postal_code']): ?>
                                            〒<?= htmlspecialchars($address['postal_code']) ?><br>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($address['prefecture']) ?>
                                        <?= htmlspecialchars($address['city']) ?>
                                        <?= htmlspecialchars($address['address_line1']) ?>
                                        <?php if ($address['address_line2']): ?>
                                            <br><?= htmlspecialchars($address['address_line2']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="address-meta">
                                        <?php if ($address['region_id']): ?>
                                            <span class="region-badge">
                                                <?= $regions[$address['region_id']] ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($address['remote_island_check']): ?>
                                            <span class="island-indicator">
                                                <i class="fas fa-island-tropical"></i> 離島
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($address['phone']): ?>
                                            <span class="phone-info">
                                                <i class="fas fa-phone"></i> <?= htmlspecialchars($address['phone']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-map-marker-alt"></i>
                        <p>配送先住所が登録されていません</p>
                        <a href="shipping-address-add.php?customer_id=<?= $customer['id'] ?>"
                            class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-plus"></i> 住所を追加
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 統計情報カード -->
            <div class="admin-card stats-card">
                <h3><i class="fas fa-chart-pie"></i> 統計情報</h3>

                <div class="stats-overview">
                    <div class="stat-item">
                        <div class="stat-icon orders">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= number_format($stats['total_orders']) ?></div>
                            <div class="stat-label">総注文数</div>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-icon revenue">
                            <i class="fas fa-yen-sign"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">¥<?= number_format($stats['total_spent']) ?></div>
                            <div class="stat-label">総購入金額</div>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-icon average">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">¥<?= number_format($stats['avg_order_value']) ?></div>
                            <div class="stat-label">平均注文額</div>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-icon activity">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= number_format($stats['favorite_count']) ?></div>
                            <div class="stat-label">お気に入り</div>
                        </div>
                    </div>
                </div>

                <?php if ($stats['first_order_date']): ?>
                    <div class="activity-timeline">
                        <div class="timeline-item">
                            <i class="fas fa-user-plus"></i>
                            <div>
                                <span class="timeline-label">初回注文</span>
                                <span class="timeline-date"><?= date('Y年m月d日', strtotime($stats['first_order_date'])) ?></span>
                            </div>
                        </div>

                        <?php if ($stats['last_order_date']): ?>
                            <div class="timeline-item">
                                <i class="fas fa-shopping-bag"></i>
                                <div>
                                    <span class="timeline-label">最終注文</span>
                                    <span class="timeline-date"><?= date('Y年m月d日', strtotime($stats['last_order_date'])) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 購入履歴カード -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> 最近の購入履歴</h3>
                    <div class="header-actions">
                        <a href="purchase-history.php?customer_id=<?= $customer['id'] ?>"
                            class="admin-btn admin-btn-outline admin-btn-sm">
                            <i class="fas fa-external-link-alt"></i> 全て見る
                        </a>
                    </div>
                </div>

                <?php if (count($purchases) > 0): ?>
                    <div class="purchase-list">
                        <?php foreach ($purchases as $purchase): ?>
                            <div class="purchase-item">
                                <div class="purchase-info">
                                    <div class="purchase-id">#<?= str_pad($purchase['id'], 6, '0', STR_PAD_LEFT) ?></div>
                                    <div class="purchase-date"><?= $purchase['formatted_date'] ?></div>
                                </div>
                                <div class="purchase-details">
                                    <div class="item-count"><?= $purchase['item_count'] ?>商品</div>
                                    <div class="purchase-amount">¥<?= number_format($purchase['grand_total']) ?></div>
                                </div>
                                <div class="purchase-actions">
                                    <a href="order-detail.php?id=<?= $purchase['id'] ?>"
                                        class="admin-btn admin-btn-outline admin-btn-xs">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <p>購入履歴がありません</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 右側：サイドバー -->
        <div class="sidebar-content">
            <!-- クイックアクション -->
            <div class="admin-card">
                <h3><i class="fas fa-bolt"></i> クイックアクション</h3>

                <div class="quick-actions">
                    <button class="action-btn" onclick="adjustPoints()">
                        <i class="fas fa-coins"></i>
                        <span>ポイント調整</span>
                    </button>

                    <button class="action-btn" onclick="sendEmail()">
                        <i class="fas fa-envelope"></i>
                        <span>メール送信</span>
                    </button>

                    <a href="customer-list-edit.php?id=<?= $customer['id'] ?>" class="action-btn">
                        <i class="fas fa-edit"></i>
                        <span>情報編集</span>
                    </a>

                    <a href="shipping-address-add.php?customer_id=<?= $customer['id'] ?>" class="action-btn">
                        <i class="fas fa-map-pin"></i>
                        <span>住所追加</span>
                    </a>

                    <button class="action-btn danger" onclick="deleteCustomer()">
                        <i class="fas fa-user-times"></i>
                        <span>顧客削除</span>
                    </button>
                </div>
            </div>

            <!-- お気に入り商品 -->
            <div class="admin-card">
                <h3><i class="fas fa-heart"></i> お気に入り商品</h3>

                <?php if (count($favorites) > 0): ?>
                    <div class="favorite-list">
                        <?php foreach (array_slice($favorites, 0, 5) as $favorite): ?>
                            <div class="favorite-item">
                                <div class="product-image">
                                    <img src="images/<?= htmlspecialchars($favorite['image_name1']) ?>"
                                        alt="<?= htmlspecialchars($favorite['product_name']) ?>"
                                        loading="lazy">
                                </div>
                                <div class="product-info">
                                    <div class="product-name"><?= htmlspecialchars(mb_strimwidth($favorite['product_name'], 0, 30, '...')) ?></div>
                                    <div class="product-price">¥<?= number_format($favorite['price']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($favorites) > 5): ?>
                        <div class="show-more">
                            <a href="favorites.php?customer_id=<?= $customer['id'] ?>" class="admin-btn admin-btn-outline admin-btn-sm">
                                <i class="fas fa-plus"></i> あと<?= count($favorites) - 5 ?>件
                            </a>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-heart-broken"></i>
                        <p>お気に入り商品がありません</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 最近のレビュー -->
            <div class="admin-card">
                <h3><i class="fas fa-star"></i> 最近のレビュー</h3>

                <?php if (count($reviews) > 0): ?>
                    <div class="review-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="product-name"><?= htmlspecialchars(mb_strimwidth($review['product_name'], 0, 25, '...')) ?></div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-o' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-comment">
                                    <?= htmlspecialchars(mb_strimwidth($review['comment'], 0, 80, '...')) ?>
                                </div>
                                <div class="review-date">
                                    <?= date('Y/m/d', strtotime($review['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-comment-slash"></i>
                        <p>レビューがありません</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* 住所リストのスタイル */
    .address-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .address-item {
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        padding: 1rem;
        background: white;
        transition: all 0.2s ease;
    }

    .address-item:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .address-item.default-address {
        border-color: var(--admin-primary);
        background: linear-gradient(135deg, #fef7ff 0%, #f3f4f6 100%);
    }

    .address-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .address-name {
        font-weight: 600;
        color: var(--admin-text);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .default-badge {
        background: var(--admin-primary);
        color: white;
        padding: 0.125rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .address-actions {
        display: flex;
        gap: 0.5rem;
    }

    .address-details {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .recipient-name {
        font-weight: 500;
        color: var(--admin-text);
    }

    .address-full {
        color: var(--admin-text-light);
        line-height: 1.5;
    }

    .address-meta {
        display: flex;
        gap: 1rem;
        margin-top: 0.5rem;
        flex-wrap: wrap;
    }

    .region-badge {
        background: var(--admin-bg);
        color: var(--admin-text);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        border: 1px solid var(--admin-border);
    }

    .island-indicator {
        background: #fef3c7;
        color: #92400e;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        border: 1px solid #f59e0b;
    }

    .phone-info {
        color: var(--admin-text-light);
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
</style>

<script>
    function adjustPoints() {
        const currentPoints = <?= $customer['point'] ?>;
        const newPoints = prompt(`現在のポイント: ${currentPoints.toLocaleString()} pt\n\n新しいポイント数を入力してください:`, currentPoints);

        if (newPoints !== null && !isNaN(newPoints) && newPoints >= 0) {
            if (confirm(`ポイントを ${parseInt(newPoints).toLocaleString()} pt に変更しますか？`)) {
                // ポイント調整処理へリダイレクト
                window.location.href = `point-adjust.php?customer_id=<?= $customer['id'] ?>&points=${newPoints}`;
            }
        }
    }

    function sendEmail() {
        const customerName = '<?= htmlspecialchars($customer['name'], ENT_QUOTES) ?>';
        const loginId = '<?= htmlspecialchars($customer['login'], ENT_QUOTES) ?>';

        if (confirm(`${customerName} 様にメールを送信しますか？`)) {
            // メール送信画面へリダイレクト
            window.location.href = `customer-email.php?customer_id=<?= $customer['id'] ?>`;
        }
    }

    function deleteCustomer() {
        const customerName = '<?= htmlspecialchars($customer['name'], ENT_QUOTES) ?>';

        if (confirm(`顧客「${customerName}」を削除しますか？\n\nこの操作は取り消せません。`)) {
            window.location.href = `customer-list-delete.php?id=<?= $customer['id'] ?>`;
        }
    }
</script>

<?php require 'admin-footer.php'; ?>