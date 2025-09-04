<?php
// admin-login-input.php
session_start();
require 'admin-header.php';
?>

<div class="admin-login-container">
    <div class="admin-login-card">
        <div class="admin-login-header">
            <div class="admin-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h2>管理者ログイン</h2>
            <p class="admin-login-subtitle">システム管理者としてログインしてください</p>
        </div>

        <?php
        // セッションにエラーメッセージがある場合、表示
        if (isset($_SESSION['eMessage'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            if ($_SESSION['eMessage'] === 0) {
                echo 'ログイン名またはパスワードが未入力です。';
            } else {
                echo 'ログイン名またはパスワードが違います。';
            }
            echo '</div>';
            // メッセージ表示後はセッションから削除
            unset($_SESSION['eMessage']);
        }
        ?>

        <form action="admin-login-output.php" method="post">
            <div class="admin-form-group">
                <label for="login" class="admin-form-label">
                    <i class="fas fa-user"></i> ログイン名
                </label>
                <input type="text" id="login" name="login" class="admin-form-input"
                       placeholder="ログイン名を入力" required>
            </div>

            <div class="admin-form-group">
                <label for="password" class="admin-form-label">
                    <i class="fas fa-lock"></i> パスワード
                </label>
                <input type="password" id="password" name="password" class="admin-form-input"
                       placeholder="パスワードを入力" required>
            </div>

            <button type="submit" class="admin-login-btn">
                <i class="fas fa-sign-in-alt"></i>
                ログイン
            </button>
        </form>

        <div class="admin-security-info">
            <i class="fas fa-shield-alt"></i>
            このシステムは管理者専用です。不正アクセスは禁止されています。
        </div>
    </div>
</div>

<?php require 'admin-footer.php'; ?>