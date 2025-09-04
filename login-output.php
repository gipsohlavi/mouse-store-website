<?php
session_start();
require 'common.php';
if (isset($_POST['login']) && isset($_POST['password'])) {
    $login = $_POST['login'];
    $password = $_POST['password'];
    
    try {
        // 実際に存在するカラムのみを選択
        $sql = $pdo->prepare('SELECT id, name, login, point FROM customer WHERE login = ? AND password = ?');
        $sql->execute([$login, $password]);
        $customer = $sql->fetch();
        
        if ($customer) {
            $_SESSION['customer'] = [
                'id' => $customer['id'],
                'name' => $customer['name'],
                'login' => $customer['login'],
                'point' => $customer['point'],
                'address' => '',  // デフォルト値
                'region_id' => 0, // デフォルト値
                'remote_island_check' => 0 // デフォルト値
            ];
        }
    } catch (PDOException $e) {
        error_log("ログインエラー: " . $e->getMessage());
    }
}

require 'header.php';
?>

<div class="auth-result-page">
    <div class="auth-container">
        <?php if (isset($_SESSION['customer'])): ?>
            <!-- ログイン成功 -->
            <div class="auth-header success">
                <div class="brand-logo">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <div class="brand-title">ログイン成功</div>
                        <div class="brand-tagline">Welcome Back</div>
                    </div>
                </div>
            </div>

            <div class="auth-form">
                <div class="success-content">
                    <div class="success-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h2 class="success-title">いらっしゃいませ</h2>
                    <p class="success-message"><?= h($_SESSION['customer']['name']) ?>さん</p>
                    
                    <div class="welcome-message">
                        <p>ログインが完了しました。<br>お買い物をお楽しみください。</p>
                        <small>ユーザーID: <?= $_SESSION['customer']['id'] ?> | ポイント: <?= $_SESSION['customer']['point'] ?>pt</small>
                    </div>
                </div>

                <div class="success-actions">
                    <a href="index.php" class="auth-btn">
                        <i class="fas fa-home"></i>
                        トップページへ
                    </a>
                    
                    <div class="quick-links">
                        <a href="product.php" class="quick-link">
                            <i class="fas fa-mouse"></i>
                            商品を見る
                        </a>
                        <a href="cart-show.php" class="quick-link">
                            <i class="fas fa-shopping-cart"></i>
                            カート
                        </a>
                        <a href="favorite-show.php" class="quick-link">
                            <i class="fas fa-heart"></i>
                            お気に入り
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- ログイン失敗 -->
            <div class="auth-header error">
                <div class="brand-logo">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <div class="brand-title">ログイン失敗</div>
                        <div class="brand-tagline">Authentication Failed</div>
                    </div>
                </div>
            </div>

            <div class="auth-form">
                <div class="error-content">
                    <div class="error-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h2 class="error-title">ログインに失敗しました</h2>
                    <p class="error-message">ログイン名またはパスワードが正しくありません。</p>
                    
                    <div class="error-help">
                        <p>以下をご確認ください：</p>
                        <ul>
                            <li>ログイン名が正しく入力されているか</li>
                            <li>パスワードが正しく入力されているか</li>
                            <li>大文字・小文字の違いがないか</li>
                        </ul>
                    </div>
                </div>

                <div class="error-actions">
                    <a href="login-input.php?login=<?= isset($login) ? urlencode($login) : '' ?>" class="auth-btn retry">
                        <i class="fas fa-redo"></i>
                        再度ログイン
                    </a>
                    
                    <div class="help-links">
                        <a href="customer-input.php" class="help-link">
                            <i class="fas fa-user-plus"></i>
                            新規登録
                        </a>
                        <a href="index.php" class="help-link">
                            <i class="fas fa-home"></i>
                            トップページ
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
    max-width: 450px;
}

.auth-header {
    color: white;
    padding: 2rem;
    text-align: center;
}

.auth-header.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.auth-header.error {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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

.success-content, .error-content {
    text-align: center;
    margin-bottom: 2rem;
}

.success-icon, .error-icon {
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

.error-icon {
    background: linear-gradient(135deg, #fee2e2, #fca5a5);
    color: #ef4444;
}

.success-title, .error-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.success-message {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.error-message {
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
}

.welcome-message {
    background: var(--background-secondary);
    padding: 1.5rem;
    border-radius: var(--radius);
    margin-bottom: 2rem;
    border-left: 4px solid #10b981;
}

.error-help {
    background: #fef2f2;
    padding: 1.5rem;
    border-radius: var(--radius);
    margin-bottom: 2rem;
    border-left: 4px solid #ef4444;
    text-align: left;
}

.error-help ul {
    margin: 1rem 0 0 1.5rem;
    color: var(--text-secondary);
}

.error-help li {
    margin-bottom: 0.5rem;
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

.auth-btn.retry {
    background: #ef4444;
}

.auth-btn.retry:hover {
    background: #dc2626;
}

.quick-links, .help-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.75rem;
}

.quick-link, .help-link {
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

.quick-link:hover, .help-link:hover {
    background: white;
    color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.quick-link i, .help-link i {
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
    
    .quick-links, .help-links {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require 'footer.php'; ?>