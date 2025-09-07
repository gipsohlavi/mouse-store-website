<?php session_start(); ?>
<?php
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //0 = 現在のURL　1=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];

//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/postage-edit-check.php$/', $_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: postage-edit.php');
}
?>
<?php require 'common.php'; ?>
<?php require 'admin-header.php'; ?>
<?php require 'admin-menu.php'; ?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-check-circle"></i> 設定内容の確認</h2>
        <p class="page-description">以下の内容で設定を変更します</p>
    </div>
    
    <div class="admin-card">
        <form action="postage-edit-update.php" method="post">
            <?php
            if (isset($_SESSION['postage'])) {
                switch ($_SESSION['postage']) {
                    //地域ごと送料の更新
                    case 'pos-region':
                        echo <<<END
                        <div class="admin-alert admin-alert-info">
                            <i class="fas fa-info-circle"></i>
                            地域ごとの送料を下記の内容で変更します
                        </div>
                        <div class="confirm-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>地域</th><th>送料</th>
                                    </tr>
                                </thead>
                                <tbody>
END;
                        foreach ($_SESSION['pos-region'] as $row) {
                            echo '<tr>';
                            echo '<td class="admin-text-center"><strong>' . $row[1] . '</strong></td>';
                            echo '<td class="admin-text-center">' . number_format($row[2]) . ' 円</td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                        
                        echo '<div class="admin-btn-group">';
                        echo '<button type="submit" formaction="postage-edit.php" class="admin-btn admin-btn-secondary">';
                        echo '<i class="fas fa-arrow-left"></i> 戻る';
                        echo '</button>';
                        echo '<button type="submit" class="admin-btn admin-btn-primary">';
                        echo '<i class="fas fa-plus"></i> 変更実行';
                        echo '</button>';
                        echo '</div>';
                        break;

                    //離島送料の変更
                    case 'pos-island':
                        echo '<div class="admin-alert admin-alert-info">';
                        echo '<i class="fas fa-info-circle"></i>';
                        echo '離島送料を下記の内容で変更します';
                        echo '</div>';

                        
                        echo '<div class="confirm-details">';
                        echo '<div class="detail-row">';
                        echo '<span class="detail-label">離島送料:</span>';
                        echo '<span class="detail-value highlight">' . number_format($_SESSION["post-island"]) . ' 円</span>';
                        echo '</div>';
                        echo '</div>';
                        
                        echo '<div class="admin-btn-group">';
                        echo '<button type="submit" formaction="postage-edit.php" class="admin-btn admin-btn-secondary">';
                        echo '<i class="fas fa-arrow-left"></i> 戻る';
                        echo '</button>';
                        echo '<button type="submit" class="admin-btn admin-btn-primary">';
                        echo '<i class="fas fa-plus"></i> 変更実行';
                        echo '</button>';
                        echo '</div>';
                        break;

                    //基本送料無料条件の変更
                    case 'posterms':
                        echo '<div class="admin-alert admin-alert-info">';
                        echo '<i class="fas fa-info-circle"></i>';
                        echo '基本送料無料条件を下記の内容で変更します';
                        echo '</div>';

                        echo '<div class="confirm-details">';
                        echo '<div class="detail-row">';
                        echo '<span class="detail-label">条件価格:</span>';
                        echo '<span class="detail-value highlight">' . number_format($_SESSION["post-terms"]) . ' 円</span>';
                        echo '</div>';
                        echo '</div>';
                        
                        echo '<div class="admin-btn-group">';
                        echo '<button type="submit" formaction="postage-edit.php" class="admin-btn admin-btn-secondary">';
                        echo '<i class="fas fa-arrow-left"></i> 戻る';
                        echo '</button>';
                        echo '<button type="submit" class="admin-btn admin-btn-primary">';
                        echo '<i class="fas fa-plus"></i> 変更実行';
                        echo '</button>';
                        echo '</div>';
                        break;

                    //キャンペーン送料の変更
                    case 'posterms-change':
                        echo '<div class="admin-alert admin-alert-info">';
                        echo '<i class="fas fa-info-circle"></i>';
                        echo 'キャンペーン送料無料条件を下記の内容で変更します';
                        echo '</div>';

                        echo '<div class="confirm-table-container">';
                        echo '<table class="admin-table">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>ID</th><th>条件価格<br>（～円以上）</th><th>開始時期</th><th>終了時期</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        echo '<form action="postage-edit-check.php" method="post">';
                        echo '<tr>';
                        echo '<th class="admin-text-center"><strong>' . $_SESSION['post-terms'][0] . '</strong></th>';
                        echo '<td>' . number_format($_SESSION['post-terms'][1]) . '</td>';
                        echo '<td class="admin-text-center">' . $_SESSION['post-terms'][2] . '</td>';
                        echo '<td class="admin-text-center">' . $_SESSION['post-terms'][3] . '</td>';
                        echo '</tr>';
                        echo '</form>';
                        echo '</tbody>';
                        echo '<table>';
                        echo '</div>';

                        echo '<div class="admin-btn-group">';
                        echo '<button type="submit" formaction="postage-edit.php" class="admin-btn admin-btn-secondary">';
                        echo '<i class="fas fa-arrow-left"></i> 戻る';
                        echo '</button>';
                        echo '<button type="submit" class="admin-btn admin-btn-primary">';
                        echo '<i class="fas fa-plus"></i> 変更実行';
                        echo '</button>';
                        echo '</div>';
                        break;

                    //キャンペーン送料の削除
                    case 'posterms-del':
                        echo '<div class="admin-alert admin-alert-error">';
                        echo '<i class="fas fa-exclamation-triangle"></i>';
                        echo '下記のキャンペーン送料無料条件を削除します。この操作は元に戻すことができません。';
                        echo '</div>';

                        echo '<div class="confirm-table-container">';
                        echo '<table class="admin-table">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>ID</th><th>条件価格<br>（～円以上）</th><th>開始時期</th><th>終了時期</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        echo '<form action="postage-edit-check.php" method="post">';
                        echo '<tr>';
                        echo '<th class="admin-text-center"><strong>' . $_SESSION['post-terms'][0] . '</strong></th>';
                        echo '<td>' . $_SESSION['post-terms'][1] . '</td>';
                        echo '<td class="admin-text-center">' . $_SESSION['post-terms'][2] . '</td>';
                        echo '<td class="admin-text-center">' . $_SESSION['post-terms'][3] . '</td>';
                        echo '</tr>';
                        echo '</form>';
                        echo '</tbody>';
                        echo '<table>';
                        echo '</div>';

                        echo '<div class="admin-btn-group">';
                        echo '<button type="submit" formaction="postage-edit.php" class="admin-btn admin-btn-secondary">';
                        echo '<i class="fas fa-arrow-left"></i> 戻る';
                        echo '</button>';
                        echo '<button type="submit" class="admin-btn admin-btn-primary">';
                        echo '<i class="fas fa-plus"></i> 削除実行';
                        echo '</button>';
                        echo '</div>';
                        break;

                    //キャンペーン送料の追加
                    case 'posterms-add':
                        echo '<div class="admin-alert admin-alert-success">';
                        echo '<i class="fas fa-plus-circle"></i>';
                        echo 'キャンペーン送料無料条件を下記の内容で追加します';
                        echo '</div>';

                        echo '<div class="confirm-table-container">';
                        echo '<table class="admin-table">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>条件価格<br>（～円以上）</th><th>開始時期</th><th>終了時期</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        echo '<form action="postage-edit-check.php" method="post">';
                        echo '<tr>';
                        echo '<td>' . $_SESSION['post-terms'][1] . '</td>';
                        echo '<td class="admin-text-center">' . $_SESSION['post-terms'][2] . '</td>';
                        echo '<td class="admin-text-center">' . $_SESSION['post-terms'][3] . '</td>';
                        echo '</tr>';
                        echo '</form>';
                        echo '</tbody>';
                        echo '<table>';
                        echo '</div>';
                        
                        echo '<div class="admin-btn-group">';
                        echo '<button type="submit" formaction="postage-edit.php" class="admin-btn admin-btn-secondary">';
                        echo '<i class="fas fa-arrow-left"></i> 戻る';
                        echo '</button>';
                        echo '<button type="submit" class="admin-btn admin-btn-primary">';
                        echo '<i class="fas fa-plus"></i> 追加実行';
                        echo '</button>';
                        echo '</div>';
                        break;
                }
            }
            ?>
        </form>
    </div>
</div>

<?php require 'admin-footer.php'; ?>