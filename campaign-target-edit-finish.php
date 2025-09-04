<?php
// campaign-target-edit-finish.php - 完了画面
session_start();
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0];
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];
//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/(campaign-target-edit-update.php)$/', $_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: point-edit.php');
}
require 'common.php';
require 'admin-header.php';
require 'admin-menu.php';
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-check"></i> キャンペーン対象商品設定 - 完了</h2>
        <p class="page-description">商品の追加が完了しました</p>
    </div>

    <div class="admin-card admin-success-card">
        <div class="success-content">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="success-message">
                <h3>追加完了</h3>
                <p>キャンペーンID: <strong><?= h($_SESSION['cp-data'][0]) ?></strong> に対象商品を追加しました。</p>
                <div class="success-details">
                    <i class="fas fa-info-circle"></i>
                    設定されたキャンペーンは即座に適用されます。
                </div>
            </div>
        </div>

        <div class="success-actions">
            <a href="point-detail-edit.php" class="admin-btn admin-btn-primary">
                <i class="fas fa-arrow-left"></i> キャンペーン詳細に戻る
            </a>
            <a href="point-edit.php" class="admin-btn admin-btn-secondary">
                <i class="fas fa-list"></i> キャンペーン一覧へ
            </a>
        </div>
    </div>
</div>



<script>
    // 3秒後に自動リダイレクトのオプション（コメントアウト）
    /*
    setTimeout(() => {
        if (confirm('キャンペーン詳細画面に移動しますか？')) {
            window.location.href = 'point-detail-edit.php';
        }
    }, 3000);
    */

    // 成功アニメーション
    document.addEventListener('DOMContentLoaded', function() {
        const successIcon = document.querySelector('.success-icon');
        successIcon.style.transform = 'scale(0)';
        successIcon.style.transition = 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)';

        setTimeout(() => {
            successIcon.style.transform = 'scale(1)';
        }, 200);
    });
</script>

<?php
require 'admin-footer.php';
?>