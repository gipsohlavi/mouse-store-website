<?php
session_start();

// 管理者ログインチェック
if (!isset($_SESSION['admin'])) {
    require 'admin-header.php';
    ?>
    <div class="admin-container" style="max-width:960px;margin:40px auto;padding:24px;background:#fff;border-radius:12px;box-shadow:0 6px 16px rgba(0,0,0,0.06);text-align:center;">
        <h2 style="margin:0 0 12px;">管理者ログインが必要です</h2>
        <p style="color:#555;margin:0 0 16px;">続行するには <strong>Adminログイン</strong> からログインしてください。</p>
        <a href="admin-login-input.php" class="btn" style="display:inline-block;min-width:200px;height:44px;line-height:44px;padding:0 16px;background:var(--primary-color,#2563eb);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;">Adminログインへ</a>
    </div>
    <?php
    require 'admin-footer.php';
    exit;
}

require 'admin-header.php';
require 'admin-menu.php';
?>

<!-- 管理者ページのコンテンツ -->
<div class="admin-container">
    <!-- こっから下にindexページかいていく -->
</div>

<?php require 'admin-footer.php'; ?>