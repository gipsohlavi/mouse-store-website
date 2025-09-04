<nav class="main-nav">
    <div class="nav-content">
        <div class="nav-links">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i>
                <span>ホーム</span>
            </a>
            <a href="product.php" class="nav-link">
                <i class="fas fa-mouse"></i>
                <span>全商品</span>
            </a>
            <div class="nav-dropdown">
                <a href="#" class="nav-link dropdown-trigger">
                    <i class="fas fa-filter"></i>
                    <span>カテゴリ</span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="product.php?category=gaming">ゲーミングマウス</a>
                    <a href="product.php?category=wireless">ワイヤレスマウス</a>
                    <a href="product.php?category=ergonomic">エルゴノミクス</a>
                    <a href="product.php?category=lightweight">軽量マウス</a>
                    <a href="product.php?category=office">オフィス用</a>
                </div>
            </div>
            <div class="nav-dropdown">
                <a href="#" class="nav-link dropdown-trigger">
                    <i class="fas fa-tags"></i>
                    <span>ブランド</span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="product.php?brand=logicool">logicool</a>
                    <a href="product.php?brand=razer">Razer</a>
                    <a href="product.php?brand=steelseries">SteelSeries</a>
                    <a href="product.php?brand=roccat">ROCCAT</a>
                    <a href="product.php?brand=corsair">Corsair</a>
                </div>
            </div>
            <a href="product.php?sort=price_low" class="nav-link">
                <i class="fas fa-yen-sign"></i>
                <span>セール中</span>
            </a>
            <a href="product.php?featured=1" class="nav-link">
                <i class="fas fa-star"></i>
                <span>おすすめ</span>
            </a>
        </div>

        <div class="nav-user-status">
            <?php if (isset($_SESSION['customer'])): ?>
                <span class="user-greeting">
                    <i class="fas fa-user-circle"></i>
                    ようこそ、<?= h($_SESSION['customer']['name']) ?>さん
                </span>
            <?php else: ?>
                <span class="guest-status">
                    <i class="fas fa-info-circle"></i>
                    ログインして特別価格をチェック
                </span>
            <?php endif; ?>
        </div>
    </div>
    </div>
</nav>