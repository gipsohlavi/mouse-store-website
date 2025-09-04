<?php
// campaign-target-edit-confirm.php - 確認画面
session_start();
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0];
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];
//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/(campaign-target-edit-check.php)$/', $_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: point-edit.php');
}
require 'common.php';
require 'admin-header.php';
require 'admin-menu.php';

// セッションデータの処理
$ctdata = [];
if (isset($_SESSION['ct-data'])) {
    foreach ($_SESSION['ct-data'] as $row) {
        $ctdata[] = explode('+', $row);
        //ctdata[0] : ID
        //ctdata[1] : path  
        //ctdata[2] : name
        //ctdata[3] : maker_name
        //ctdata[4] : price
    }
}
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-check-circle"></i> キャンペーン対象商品設定 - 確認</h2>
        <p class="page-description">下記の商品をキャンペーン対象に追加します</p>
    </div>

    <div class="admin-card">
        <div class="admin-alert admin-alert-info">
            <i class="fas fa-info-circle"></i>
            以下の <?= count($ctdata) ?> 件の商品をキャンペーン対象に追加します。よろしいですか？
        </div>

        <div class="confirm-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>商品番号</th>
                        <th>画像</th>
                        <th>商品名</th>
                        <th>メーカー</th>
                        <th>価格</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($ctdata as $row) {
                        // 商品IDを直接使用
                        $product_id = $row[0];
                        $image_path = "images/{$product_id}.jpg";
                        if (!file_exists($image_path)) {
                            $image_path = "images/no-image.jpg";
                        }
                        echo '<tr>';
                        echo '<td class="admin-text-center"><strong>', $row[0], '</strong></td>';
                        echo '<td class="admin-text-center">';
                        echo '<img src="', $image_path, '" alt="', h($row[2]), '" style="width: 60px; height: 60px; object-fit: contain; border-radius: 4px; background: #f9fafb;">';
                        echo '</td>';
                        echo '<td>';
                        echo '<div class="product-confirm-info">';
                        echo '<a href="detail.php?id=', $row[0], '" class="product-link" target="_blank">', h($row[2]), '</a>';
                        echo '</div>';
                        echo '</td>';
                        echo '<td class="admin-text-right"><strong>', $row[3], '</strong></td>';
                        echo '<td class="admin-text-right"><strong>¥', number_format($row[4]), '</strong></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php
        if (isset($_SESSION['error6'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error6']);
            echo '</div>';
            unset($_SESSION['error6']);
        }
        ?>

        <form action="campaign-target-edit-update.php" method="post" autocomplete="off">
            <div class="admin-form-actions">
                <button type="button" formaction="campaign-target-edit.php" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-arrow-left"></i> 戻る
                </button>
                <button type="submit" class="admin-btn admin-btn-primary submit-btn-form">
                    <i class="fas fa-plus"></i> 追加実行
                </button>
            </div>
        </form>
    </div>
</div>


<script>
    // フォーム送信前の最終確認
    document.querySelector('.submit-btn-form').addEventListener('click', function(e) {
        const productCount = <?= count($ctdata) ?>;
        if (!confirm(`${productCount}件の商品をキャンペーン対象に追加します。\n\nこの操作は元に戻すことができません。よろしいですか？`)) {
            e.preventDefault();
        }
    });
</script>

<?php require 'admin-footer.php'; ?>