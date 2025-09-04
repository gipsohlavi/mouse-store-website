<?php session_start(); ?>
<?php
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //0 = 現在のURL　1=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];

//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/postage-edit-update.php$/', $_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: postage-edit.php');
}
unset($_SESSION['url']);
?>
<?php require 'common.php'; ?>
<?php require 'admin-header.php'; ?>
<?php require 'admin-menu.php'; ?>
<div class="admin-container">
    <div class="admin-page-title">
        <h2>
            <i class="fas fa-check-circle"></i> 
            <?php
            if (isset($_SESSION['postage'])) {
                switch ($_SESSION['postage']) {
                    //地域ごと送料の更新
                    case 'pos-region':
                        echo '<p>地域ごとの送料の更新が完了しました</p>';
                        break;

                    //離島送料の変更
                    case 'pos-island':
                        echo '<p>離島送料の変更が完了しました</p>';
                        break;

                    //基本送料無料条件の変更
                    case 'posterms':
                        echo '<p>基本送料無料条件の変更が完了しました</p>';
                        break;

                    //キャンペーン送料の変更
                    case 'posterms-change':
                        echo '<p>キャンペーン送料無料条件ID:' . $_SESSION['post-terms'][0] . 'の変更が完了しました</p>';
                        break;

                    //キャンペーン送料の削除
                    case 'posterms-del':
                        echo '<p>キャンペーン送料無料条件ID:' . $_SESSION['post-terms'][0] . 'を削除しました</p>';
                        break;

                    //キャンペーン送料の追加
                    case 'posterms-add':
                        echo '<p>キャンペーン送料無料条件ID:' . $_SESSION['post-terms'][0] . 'を追加しました</p>';
                        break;

                    //エラー表示
                    case 'error':
                        echo 'ERROR: ' . $_SESSION['post-terms'][0];
                        break;
                }
            }
            unset($_SESSION['postage']);
            unset($_SESSION['post-terms']);
            unset($_SESSION['pos-region']);
            unset($_SESSION['post-island']);
            ?>
        </h2>
    </div>
    <form action="postage-edit.php" >
        <button type="submit" class="admin-btn admin-btn-secondary">
            <i class="fas fa-arrow-left"></i> 戻る
        </button>
    </form>
</div>
<?php require 'admin-footer.php'; ?>