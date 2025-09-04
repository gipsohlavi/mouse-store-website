<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-header">
            <div class="brand-logo">
                <i class="fas fa-mouse"></i>
                <div>
                    <div class="brand-title">KELOT</div>
                    <div class="brand-tagline">Premium Mouse</div>
                </div>
            </div>
        </div>

        <div class="auth-form">
            <h2 class="form-title">ログイン</h2>
            <p class="form-subtitle">アカウントにサインインしてください</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    ログイン名またはパスワードが違います。
                </div>
            <?php endif; ?>

            <form action="login-output.php" method="post">
                <div class="form-group">
                    <label for="login" class="form-label">ログイン名</label>
                    <input type="text" id="login" name="login" class="form-input" required 
                           value="<?= isset($_GET['login']) ? h($_GET['login']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">パスワード</label>
                    <div class="password-group">
                        <input type="password" id="password" name="password" class="form-input" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    ログイン
                </button>
            </form>

            <div class="divider">
                <span class="divider-text">または</span>
            </div>

            <a href="index.php" class="guest-link">
                <i class="fas fa-user"></i>
                ゲストとして続行
            </a>
        </div>

        <div class="auth-footer">
            <p>アカウントをお持ちでない方は <a href="customer-input.php" class="auth-link">新規登録</a></p>
        </div>
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
    border-radius: var(--radius-lg);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    width: 100%;
    max-width: 400px;
}

.auth-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
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

.form-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.form-subtitle {
    color: var(--text-secondary);
    margin-bottom: 2rem;
    font-size: 0.9rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.form-input {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    font-size: 1rem;
    transition: var(--transition);
    background: var(--background);
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    transform: translateY(-1px);
}

.password-group {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: var(--radius);
    transition: var(--transition);
}

.password-toggle:hover {
    background: var(--background-secondary);
    color: var(--primary-color);
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
}

.auth-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

.auth-footer {
    padding: 1.5rem 2rem;
    background: var(--background-secondary);
    text-align: center;
    border-top: 1px solid var(--border-color);
}

.auth-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
}

.auth-link:hover {
    color: var(--primary-dark);
}

.divider {
    margin: 1.5rem 0;
    text-align: center;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: var(--border-color);
}

.divider-text {
    background: white;
    padding: 0 1rem;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.guest-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.9rem;
    transition: var(--transition);
}

.guest-link:hover {
    color: var(--primary-color);
}

.alert {
    padding: 1rem;
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
}

.alert-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

@media (max-width: 480px) {
    .auth-page {
        padding: 20px 10px;
    }
    
    .auth-header {
        padding: 1.5rem;
    }
    
    .auth-form {
        padding: 1.5rem;
    }
}
</style>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('passwordToggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>

<?php require 'footer.php'; ?>