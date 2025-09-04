<div class="admin-menu">
    <nav class="admin-nav">
        <a href="admin-index.php">
            <i class="fas fa-tachometer-alt"></i>
            ダッシュボード
        </a>

        <a href="product-list.php">
            <i class="fas fa-box"></i>
            商品管理
        </a>

        <a href="customer-list.php">
            <i class="fas fa-users"></i>
            顧客管理
        </a>

        <a href="purchase-list.php">
            <i class="fas fa-shopping-cart"></i>
            注文管理
        </a>

        <a href="point-edit.php">
            <i class="fas fa-coins"></i>
            ポイント設定
        </a>

        <a href="postage-edit.php">
            <i class="fas fa-truck"></i>
            配送料設定
        </a>

        <a href="tax-edit.php">
            <i class="fa-solid fa-money-check-dollar"></i>
            税率設定
        </a>


        <a href="admin-logout-input.php" class="logout">
            <i class="fas fa-sign-out-alt"></i>
            ログアウト
        </a>
    </nav>
</div>

<script>
    // 現在のページをアクティブ表示
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.admin-nav a');

        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && (currentPath.includes(href) ||
                    (href.includes('customer') && currentPath.includes('customer')))) {
                link.classList.add('active');
            }
        });
    });
</script>