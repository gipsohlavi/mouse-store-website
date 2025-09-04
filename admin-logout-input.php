<?php
// admin-logout-input.php - ログアウト確認画面
session_start();
require 'admin-header.php';
?>

<div class="admin-logout-container">
    <div class="admin-logout-card">
        <div class="admin-logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>

        <h2 class="admin-logout-title">ログアウトの確認</h2>

        <p class="admin-logout-message">
            管理者システムからログアウトします。<br>
            ログアウト後は、再度ログインが必要になります。<br>
            よろしいですか？
        </p>

        <div class="admin-logout-actions">
            <a href="javascript:history.back()" class="admin-btn-outline">
                <i class="fas fa-arrow-left"></i>
                キャンセル
            </a>
            <a href="admin-logout-output.php" class="admin-btn-danger">
                <i class="fas fa-sign-out-alt"></i>
                ログアウト
            </a>
        </div>
    </div>
</div>

<?php require 'admin-footer.php'; ?>