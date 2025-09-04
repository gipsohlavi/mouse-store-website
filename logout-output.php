<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php
$logout = 0;
if (isset($_SESSION['customer'])) {
    unset($_SESSION['customer']);
    unset($_SESSION['product']);
    $logout = 1;
}
?>
<?php require 'header.php'; ?>

<div class="auth-result-page">
    <div class="auth-container">
        <?php if ($logout === 1): ?>
            <!-- ログアウト成功 -->
            <div class="auth-header success">
                <div class="brand-logo">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <div class="brand-title">ログアウト完了</div>
                        <div class="brand-tagline">Successfully Logged Out</div>
                    </div>
                </div>
            </div>

            <div class="auth-form">
                <div class="success-content">
                    <div class="success-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <h2 class="success-title">ログアウトしました</h2>
                    <p class="success-message">セッションが正常に終了されました</p>
                    
                    <div class="logout-success-info">
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="info-content">
                                <h4>セキュリティ</h4>
                                <p>アカウントは安全に保護されています</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="info-content">
                                <h4>カート破棄</h4>
                                <p>カートの内容は破棄されました</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="success-actions">
                    <a href="index.php" class="auth-btn">
                        <i class="fas fa-home"></i>
                        トップページへ
                    </a>
                    
                    <div class="quick-links">
                        <a href="login-input.php" class="quick-link primary">
                            <i class="fas fa-sign-in-alt"></i>
                            再ログイン
                        </a>
                        <a href="product.php" class="quick-link">
                            <i class="fas fa-mouse"></i>
                            商品を見る
                        </a>
                        <a href="customer-input.php" class="quick-link">
                            <i class="fas fa-user-plus"></i>
                            新規登録
                        </a>
                    </div>
                </div>
            </div>

            <div class="auth-footer">
                <p>ご利用ありがとうございました。またのお越しをお待ちしております。</p>
            </div>
        <?php else: ?>
            <!-- 既にログアウト済み -->
            <div class="auth-header info">
                <div class="brand-logo">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <div class="brand-title">ログアウト済み</div>
                        <div class="brand-tagline">Already Logged Out</div>
                    </div>
                </div>
            </div>

            <div class="auth-form">
                <div class="info-content">
                    <div class="info-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h2 class="info-title">すでにログアウトしています</h2>
                    <p class="info-message">現在ログインしていない状態です。</p>
                    
                    <div class="info-note">
                        <p>ログインしていない状態でも、商品の閲覧や購入は可能です。<br>アカウント機能を利用する場合はログインしてください。</p>
                    </div>
                </div>

                <div class="info-actions">
                    <a href="login-input.php" class="auth-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        ログイン
                    </a>
                    
                    <div class="quick-links">
                        <a href="index.php" class="quick-link">
                            <i class="fas fa-home"></i>
                            トップページ
                        </a>
                        <a href="product.php" class="quick-link">
                            <i class="fas fa-mouse"></i>
                            商品を見る
                        </a>
                        <a href="customer-input.php" class="quick-link">
                            <i class="fas fa-user-plus"></i>
                            新規登録
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.auth-result-page {
    min-height: calc(100vh - 160px);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.auth-container {
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    width: 100%;
    max-width: 500px;
}

.auth-header {
    color: white;
    padding: 2rem;
    text-align: center;
}

.auth-header.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.auth-header.info {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.brand-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.brand-logo i {
    font-size: 2rem;
}

.brand-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.brand-tagline {
    font-size: 0.875rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.auth-form {
    padding: 2rem;
}

.auth-footer {
    padding: 1.5rem 2rem;
    background: var(--background-secondary);
    text-align: center;
    border-top: 1px solid var(--border-color);
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.success-content, .info-content {
    text-align: center;
    margin-bottom: 2rem;
}

.success-icon, .info-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2rem;
}

.success-icon {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #10b981;
}

.info-icon {
    background: linear-gradient(135deg, #dbeafe, #93c5fd);
    color: #3b82f6;
}

.success-title, .info-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.success-message, .info-message {
    color: var(--text-secondary);
    margin-bottom: 2rem;
}

.logout-success-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
}

.info-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--background-secondary);
    border-radius: var(--radius);
    text-align: left;
}

.info-card .info-icon {
    width: 40px;
    height: 40px;
    font-size: 1.25rem;
    margin: 0;
    flex-shrink: 0;
}

.info-card .info-content h4 {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.info-card .info-content p {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin: 0;
}

.info-note {
    background: #eff6ff;
    padding: 1.5rem;
    border-radius: var(--radius);
    margin-bottom: 2rem;
    border-left: 4px solid #3b82f6;
    text-align: left;
}

.info-note p {
    color: var(--text-secondary);
    margin: 0;
    line-height: 1.6;
}

.auth-btn {
    width: 100%;
    padding: 0.875rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: var(--radius);
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-decoration: none;
    margin-bottom: 1.5rem;
}

.auth-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

.quick-links {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.quick-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 0.75rem;
    background: var(--background-secondary);
    border-radius: var(--radius);
    text-decoration: none;
    color: var(--text-secondary);
    transition: var(--transition);
    font-size: 0.875rem;
}

.quick-link:hover {
    background: white;
    color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.quick-link.primary {
    background: linear-gradient(135deg, #3b82f615, #1d4ed815);
    color: var(--primary-color);
    border: 1px solid #3b82f630;
}

.quick-link.primary:hover {
    background: var(--primary-color);
    color: white;
}

.quick-link i {
    font-size: 1.25rem;
}

@media (max-width: 480px) {
    .auth-result-page {
        padding: 20px 10px;
    }
    
    .auth-header {
        padding: 1.5rem;
    }
    
    .auth-form {
        padding: 1.5rem;
    }
    
    .logout-success-info {
        grid-template-columns: 1fr;
    }
    
    .quick-links {
        grid-template-columns: 1fr;
    }
    
    .info-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<?php require 'footer.php'; ?>