<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KELOT - こだわりマウス専門店</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+JP:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/ui-lightness/jquery-ui.css">
    <!-- bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">

    <script>
        $(function() {
            $('#start').datepicker({
                dateFormat: 'yy-mm-dd'
            });

            // ハンバーガーメニュー
            $('.mobile-menu-toggle').click(function() {
                $('.nav-menu').toggleClass('active');
                $(this).toggleClass('active');
            });

            // 検索フォームの切り替え
            $('.search-toggle').click(function() {
                $('.search-form').toggleClass('active');
                $(this).toggleClass('active');
            });

            // 検索フォーム外をクリックしたら閉じる
            $(document).click(function(e) {
                if (!$(e.target).closest('.search-toggle, .search-form').length) {
                    $('.search-form').removeClass('active');
                    $('.search-toggle').removeClass('active');
                }
            });

            // スムーススクロール
            $('a[href^="#"]').click(function() {
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 80
                    }, 800);
                    return false;
                }
            });
        });
    </script>
</head>

<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php">
                        <h1>KELOT</h1>
                        <span class="tagline">Premium Mouse</span>
                    </a>
                </div>


                <nav class="nav-menu">
                    <a href="product.php" class="nav-item">
                        <i class="fas fa-mouse"></i>
                        <span>商品一覧</span>
                    </a>
                    <a href="product.php?featured=1" class="nav-item">
                        <i class="fas fa-star"></i>
                        <span>おすすめ</span>
                    </a>
                    <a href="product.php?sort=new" class="nav-item">
                        <i class="fas fa-plus-circle"></i>
                        <span>新着</span>
                    </a>
                    <!-- お気に入りボタンを追加 -->
                    <a href="favorite-show.php" class="nav-item favorite-link">
                        <i class="fas fa-heart"></i>
                        <span>お気に入り</span>
                        <!-- ログイン済みの場合のみお気に入り数を表示 -->
                        <?php if (isset($_SESSION['customer'])): ?>
                            <?php
                            $favorite_count_sql = $pdo->prepare('SELECT COUNT(*) as count FROM favorite WHERE customer_id = ?');
                            $favorite_count_sql->bindParam(1, $_SESSION['customer']['id']);
                            $favorite_count_sql->execute();
                            $favorite_count = $favorite_count_sql->fetch()['count'];
                            if ($favorite_count > 0):
                            ?>
                                <span class="favorite-count show"><?= $favorite_count > 99 ? '99+' : $favorite_count ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </a>
                    <a href="cart-show.php" class="nav-item cart-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>カート</span>
                        <?php
                        $cart_count = isset($_SESSION['product']) ? count($_SESSION['product']) : 0;
                        if ($cart_count > 0):
                        ?>
                            <span class="cart-count show"><?= $cart_count > 99 ? '99+' : $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                </nav>

                <div class="header-actions">
                    <button class="search-toggle" aria-label="検索">
                        <i class="fas fa-search"></i>
                    </button>

                    <div class="user-menu">
                        <?php if (isset($_SESSION['customer'])): ?>
                            <div class="user-info">
                                <i class="fas fa-user-circle"></i>
                                <span><?= h($_SESSION['customer']['name']) ?></span>
                                <div class="dropdown">
                                    <a href="favorite-show.php">
                                        <i class="fas fa-heart"></i>
                                        お気に入り
                                    </a>
                                    <a href="history.php">
                                        <i class="fas fa-history"></i>
                                        購入履歴
                                    </a>
                                    <a href="purchase-input.php">
                                        <i class="fas fa-credit-card"></i>
                                        購入手続き
                                    </a>
                                    <a href="shipping-address-list.php" class="menu-item">
                                        <i class="fas fa-shipping-fast"></i>
                                        配送先管理
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a href="logout-input.php" class="logout-link">
                                        <i class="fas fa-sign-out-alt"></i>
                                        ログアウト
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="login-input.php" class="login-btn">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>ログイン</span>
                            </a>
                            <a href="customer-input.php" class="register-btn">
                                <i class="fas fa-user-plus"></i>
                                <span>新規登録</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <button class="mobile-menu-toggle" aria-label="メニュー">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>

            <!-- 検索フォーム（ヘッダーコンテンツの下に配置） -->
            <div class="search-form">
                <form action="product.php" method="post" class="search-container">
                    <input type="text" name="keyword" placeholder="マウス名、ブランド、特徴で検索・・・" class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- 追加CSSでお気に入りカウントのスタイルを定義 -->
        <style>
            .favorite-link {
                position: relative;
            }

            .favorite-count {
                position: absolute;
                top: -10px;
                right: -10px;
                background: #e91e63;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.7rem;
                font-weight: 600;
                border: 2px solid white;
                box-shadow: 0 2px 4px rgba(233, 30, 99, 0.4);
                opacity: 0;
                transform: scale(0);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .favorite-count.show {
                opacity: 1;
                transform: scale(1);
            }

            .favorite-count.pulse {
                animation: favoriteCountPulse 0.6s ease-out;
            }

            /* デスクトップ用のドロップダウン設定 */
            .dropdown {
                position: absolute;
                top: calc(100% + 12px);
                right: 0;
                background: white;
                border: 1px solid var(--border-color);
                border-radius: var(--radius);
                box-shadow: var(--shadow-lg);
                min-width: 200px;
                opacity: 0;
                visibility: hidden;
                transform: translateY(-10px);
                transition: var(--transition);
                z-index: 1100;
            }

            .dropdown::before {
                content: '';
                position: absolute;
                top: -6px;
                right: 20px;
                width: 12px;
                height: 12px;
                background: white;
                border: 1px solid var(--border-color);
                border-bottom: none;
                border-right: none;
                transform: rotate(45deg);
            }

            .user-menu-dropdown .menu-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 12px 16px;
                color: #374151;
                text-decoration: none;
                transition: background-color 0.2s;
            }

            .user-menu-dropdown .menu-item:hover {
                background-color: #f3f4f6;
                color: #1f2937;
            }

            .user-menu-dropdown .menu-item i {
                width: 16px;
                color: #6b7280;
            }

            .menu-divider {
                height: 1px;
                background-color: #e5e7eb;
                margin: 8px 0;
            }

            @keyframes favoriteCountPulse {
                0% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.3);
                    background: #f06292;
                }

                100% {
                    transform: scale(1);
                }
            }

            .favorite-link:hover {
                background: var(--background-secondary);
                color: #e91e63;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(233, 30, 99, 0.15);
            }

            .favorite-link:hover i {
                color: #e91e63;
                transform: scale(1.1);
            }

            /* モバイル対応（768px以下でのみ適用） */
            @media (max-width: 768px) {
                .favorite-link {
                    padding: 1.25rem 1.5rem;
                    border-bottom: 1px solid var(--border-color);
                    justify-content: flex-start;
                    border-radius: 0;
                    font-size: 1.1rem;
                }

                .favorite-link:hover {
                    background: var(--background-secondary);
                    transform: none;
                    box-shadow: none;
                    color: #e91e63;
                }
            }
        </style>
    </main>
</body>

</html>