<?php
// point-edit-confirm.php - 確認画面
session_start();
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0];
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];

//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/(point-edit-check.php)$/', $_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: point-edit.php');
}
require 'common.php';
require 'admin-header.php';
require 'admin-menu.php';
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-check-circle"></i> 設定内容の確認</h2>
        <p class="page-description">以下の内容で設定を変更します</p>
    </div>

    <div class="admin-card">
        <form action="point-edit-update.php" method="post">
            <?php
            if (isset($_SESSION['point'])) {
                switch ($_SESSION['point']) {
                    //基本ポイント付与率の変更
                    case 'point':
                        echo '<div class="admin-alert admin-alert-info">';
                        echo '<i class="fas fa-info-circle"></i>';
                        echo '基本ポイント付与率を下記の内容で変更します';
                        echo '</div>';

                        echo '<div class="confirm-details">';
                        echo '<div class="detail-row">';
                        echo '<span class="detail-label">基本ポイント付与率:</span>';
                        echo '<span class="detail-value highlight">' . ($_SESSION["cprate"] * 100) . ' %</span>';
                        echo '</div>';
                        echo '</div>';

                        echo '<div class="admin-btn-group">';
                        echo '<button type="submit" formaction="point-edit.php" class="admin-btn admin-btn-secondary">';
                        echo '<i class="fas fa-arrow-left"></i> 戻る';
                        echo '</button>';
                        echo '<button type="submit" class="admin-btn admin-btn-primary">';
                        echo '<i class="fas fa-save"></i> 変更実行';
                        echo '</button>';
                        echo '</div>';
                        break;

                    //キャンペーンの変更
                    case 'pc-change':
                        echo '<div class="admin-alert admin-alert-info">';
                        echo '<i class="fas fa-info-circle"></i>';
                        echo 'キャンペーンを下記の内容で変更します';
                        echo '</div>';

                        echo '<div class="confirm-table-container">';
                        echo '<table class="admin-table">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>ID</th><th>キャンペーン名</th><th>ポイント付与率</th><th>開始日</th><th>終了日</th><th>優先度</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        echo '<tr>';
                        echo '<td class="admin-text-center"><strong>' . $_SESSION['cp-data'][0] . '</strong></td>';
                        echo '<td>' . h($_SESSION['cp-data'][1]) . '</td>';
                        echo '<td class="admin-text-center">' . ($_SESSION['cp-data'][2] * 100) . ' %</td>';
                        echo '<td>' . $_SESSION['cp-data'][3] . '</td>';
                        echo '<td>' . $_SESSION['cp-data'][4] . '</td>';
                        echo '<td class="admin-text-center">' . $_SESSION['cp-data'][5] . '</td>';
                        echo '</tr>';
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';

                        echo '<div class="admin-btn-group">';
                        if (count($_SESSION['cp-data']) === 6) {
                            echo '<button type="submit" formaction="point-edit.php" class="admin-btn admin-btn-secondary">';
                        } else {
                            echo '<button type="submit" formaction="point-detail-edit.php" class="admin-btn admin-btn-secondary">';
                        }
                        echo '<i class="fas fa-arrow-left"></i> 戻る';
                        echo '</button>';
                        echo '<button type="submit" class="admin-btn admin-btn-primary">';
                        echo '<i class="fas fa-save"></i> 変更実行';
                        echo '</button>';
                        echo '</div>';
                        break;

                    //キャンペーンの削除
                    case 'pc-del':
                        echo '<div class="admin-alert admin-alert-error">';
                        echo '<i class="fas fa-exclamation-triangle"></i>';
                        echo '下記のキャンペーンを削除します。この操作は元に戻すことができません。';
                        echo '</div>';

                        echo '<div class="confirm-table-container">';
                        echo '<table class="admin-table">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>ID</th><th>キャンペーン名</th><th>ポイント付与率</th><th>開始日</th><th>終了日</th><th>優先度</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        echo '<tr>';
                        echo '<td class="admin-text-center"><strong>' . $_SESSION['cp-data'][0] . '</strong></td>';
                        echo '<td>' . h($_SESSION['cp-data'][1]) . '</td>';
                        echo '<td class="admin-text-center">' . ($_SESSION['cp-data'][2] * 100) . ' %</td>';
                        echo '<td>' . $_SESSION['cp-data'][3] . '</td>';
                        echo '<td>' . $_SESSION['cp-data'][4] . '</td>';
                        echo '<td class="admin-text-center">' . $_SESSION['cp-data'][5] . '</td>';
                        echo '</tr>';
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';

                        echo '<div class="admin-btn-group">';
                        echo '<button type="submit" formaction="point-edit.php" class="admin-btn admin-btn-secondary">';
                        echo '<i class="fas fa-arrow-left"></i> 戻る';
                        echo '</button>';
                        echo '<button type="submit" class="admin-btn admin-btn-danger">';
                        echo '<i class="fas fa-trash"></i> 削除実行';
                        echo '</button>';
                        echo '</div>';
                        break;

                    //キャンペーンの追加
                    case 'pc-add':
                        echo '<div class="admin-alert admin-alert-success">';
                        echo '<i class="fas fa-plus-circle"></i>';
                        echo 'キャンペーンを下記の内容で追加します';
                        echo '</div>';

                        echo '<div class="confirm-table-container">';
                        echo '<table class="admin-table">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>キャンペーン名</th><th>ポイント付与率</th><th>開始日</th><th>終了日</th><th>優先度</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        echo '<tr>';
                        echo '<td>' . h($_SESSION['cp-data'][1]) . '</td>';
                        echo '<td class="admin-text-center">' . ($_SESSION['cp-data'][2] * 100) . ' %</td>';
                        echo '<td>' . $_SESSION['cp-data'][3] . '</td>';
                        echo '<td>' . $_SESSION['cp-data'][4] . '</td>';
                        echo '<td class="admin-text-center">' . $_SESSION['cp-data'][5] . '</td>';
                        echo '</tr>';
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';

                        echo '<div class="admin-btn-group">';
                        echo '<button type="submit" formaction="point-edit.php" class="admin-btn admin-btn-secondary">';
                        echo '<i class="fas fa-arrow-left"></i> 戻る';
                        echo '</button>';
                        echo '<button type="submit" class="admin-btn admin-btn-primary">';
                        echo '<i class="fas fa-plus"></i> 追加実行';
                        echo '</button>';
                        echo '</div>';
                        break;

                    //キャンペーン該当商品の削除
                    case 'pcitem-del':
                    header('Location: point-edit-update.php');
                }
            }
            ?>
        </form>
    </div>
</div>

<?php require 'admin-footer.php'; ?>