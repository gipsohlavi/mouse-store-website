    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>KELOT</h3>
                    <p>プレミアムマウス専門店として、最高品質のマウスをお届けします。あらゆるニーズにお応えします。</p>
                    <div class="social-links">
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        <a href="#" aria-label="Discord"><i class="fab fa-discord"></i></a>
                    </div>
                </div>

                

                <div class="footer-section">
                    <h3>サポート</h3>
                    <a href="postage.php">送料について</a>
                    <a href="q&a.php">よくある質問</a>
                </div>

                <div class="footer-section">
                    <h3>アカウント</h3>
                    <?php if (isset($_SESSION['customer'])): ?>
                        <a href="history.php">購入履歴</a>
                        <a href="favorite-show.php">お気に入り</a>
                        <a href="cart-show.php">カート</a>
                        <a href="logout-input.php">ログアウト</a>
                    <?php else: ?>
                        <a href="login-input.php">ログイン</a>
                        <a href="customer-input.php">新規登録</a>
                    <?php endif; ?>
                </div>

                <div class="footer-section">
                    <h3>企業情報</h3>
                    <a href="about.php">企業概要</a>
                    <a href="privacy.php">プライバシーポリシー</a>
                    <a href="terms.php">利用規約</a>
                    <a href="law.php">特定商法取引</a>
                </div>

                <div class="footer-section">
                    <h3>ニュースレター</h3>
                    <p>新商品情報やセール情報をお届けします。</p>
                    <form class="newsletter-form" action="newsletter.php" method="post">
                        <input type="email" name="email" placeholder="メールアドレス" required>
                        <button type="submit">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p>&copy; <?php date('Y') ?> KELOT. All rights reserved.</p>
                    <div class="payment^-methods">
                        <span>お支払方法:</span>
                        <i class="fab fa-cc-visa" title="Visa"></i>
                        <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                        <i class="fab fa-cc-jcb" title="JCB"></i>
                        <i class="fab fa-cc-amex" title="American Express"></i>
                        <i class="fas fa-mobile-alt" title="モバイル決済"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
	<script src="./js/script.js"></script>
</body>