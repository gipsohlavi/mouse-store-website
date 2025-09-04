<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>

<div class="auth-page">
    <div class="auth-container">
        <?php if (isset($_SESSION['customer'])): ?>
            <div class="logout-container">
                <div class="logout-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                
                <h2 class="logout-title">ログアウトしますか？</h2>
                <p class="logout-message">
                    <strong><?= h($_SESSION['customer']['name']) ?></strong>さん<br>
                    現在のセッションを終了します。<br>
                    保存されていない変更は失われる場合があります。
                </p>

                <div class="logout-actions">
                    <a href="index.php" class="btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        キャンセル
                    </a>
                    <a href="logout-output.php" class="btn-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        ログアウト
                    </a>
                </div>

                <div class="logout-info">
                    <div class="info-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>セキュリティのため、共用PCでは必ずログアウトしてください</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span>カートの商品は破棄されます</span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- 既にログアウト済みの場合 -->
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
                    <h2 class="info-title">既にログアウトしています</h2>
                    <p class="info-message">現在ログインしていません。</p>
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
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.auth-page {
    min-height: calc(100vh - 160px);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.auth-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    width: 100%;
    max-width: 500px;
}

.auth-header.info {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    padding: 2rem;
    text-align: center;
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

.logout-container {
    text-align: center;
    padding: 2rem;
}

.logout-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #fee2e2, #fca5a5);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    color: #dc2626;
    font-size: 2rem;
}

.logout-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1rem;
}

.logout-message {
    color: #6b7280;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.logout-message strong {
    color: #2563eb;
}

.logout-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 2rem;
}

.btn-outline {
    padding: 0.75rem 1.5rem;
    background: white;
    color: #1f2937;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #2563eb;
    color: #2563eb;
}

.btn-danger {
    padding: 0.75rem 1.5rem;
    background: #dc2626;
    color: white;
    border: 1px solid #dc2626;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-danger:hover {
    background: #b91c1c;
    transform: translateY(-2px);
}

.logout-info {
    background: #f9fafb;
    border-radius: 8px;
    padding: 1.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: #6b7280;
}

.info-item:last-child {
    margin-bottom: 0;
}

.info-item i {
    color: #2563eb;
    width: 16px;
    text-align: center;
}

.info-content {
    text-align: center;
    margin-bottom: 2rem;
}

.info-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #dbeafe, #93c5fd);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    color: #3b82f6;
    font-size: 2rem;
}

.info-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.info-message {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.auth-btn {
    width: 100%;
    padding: 0.875rem;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-decoration: none;
    margin-bottom: 1.5rem;
}

.auth-btn:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
}

.quick-links {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.quick-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 0.75rem;
    background: #f9fafb;
    border-radius: 8px;
    text-decoration: none;
    color: #6b7280;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.875rem;
}

.quick-link:hover {
    background: white;
    color: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.quick-link i {
    font-size: 1.25rem;
}

@media (max-width: 480px) {
    .auth-page {
        padding: 20px 10px;
    }
    
    .logout-container {
        padding: 1.5rem;
    }
    
    .auth-form {
        padding: 1.5rem;
    }
    
    .logout-actions {
        flex-direction: column;
    }
    
    .quick-links {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require 'footer.php'; ?>