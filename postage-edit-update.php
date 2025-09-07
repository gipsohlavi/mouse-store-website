
<?php session_start(); ?>
<?php
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //0 = 現在のURL　1=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];

//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/postage-edit-confirm.php$/', $_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: postage-edit.php');
}
?>
<?php require 'common.php'; ?>
<?php
if (isset($_SESSION['postage'])) {
    try {
        switch ($_SESSION['postage']) {
            //地域ごと送料の更新
            case 'pos-region':
                $postage_id = 1;
                foreach ($_SESSION['pos-region'] as $row) {
                    $sql = $pdo->prepare('UPDATE postage SET postage_fee = ?, upd_date = ? 
                                        WHERE postage_id = ? AND region_id = ?');
                    $sql->bindParam(1, $row[2]);
                    $sql->bindParam(2, $today);
                    $sql->bindParam(3, $postage_id);
                    $sql->bindParam(4, $row[0]);
                    $sql->execute();
                }
                break;

            //離島送料の変更
            case 'pos-island':
                $island_id = 1;
                $sql = $pdo->prepare('UPDATE postage_remote_island  SET remote_island_fee = ?, upd_date = ? 
                                    WHERE remote_island_fee_id  = ?');
                $sql->bindParam(1, $_SESSION['post-island']);
                $sql->bindParam(2, $today);
                $sql->bindParam(3, $island_id);
                $sql->execute();
                break;

            //基本送料無料条件の変更
            case 'posterms':
                $postage_fee_free_id = 1;
                $sql = $pdo->prepare('UPDATE postage_free SET postage_fee_free = ?, upd_date = ? 
                                    WHERE postage_fee_free_id  = ?');
                $sql->bindParam(1, $_SESSION['post-terms']);
                $sql->bindParam(2, $today);
                $sql->bindParam(3, $postage_fee_free_id);
                $sql->execute();
                break;

            //キャンペーン送料の変更
            case 'posterms-change':
                $sql = $pdo->prepare('UPDATE postage_free 
                                    SET postage_fee_free = ?, start_date = ?, end_date = ?, upd_date = ? 
                                    WHERE postage_fee_free_id  = ?');
                $sql->bindParam(1, $_SESSION['post-terms'][1]);
                $sql->bindParam(2, $_SESSION['post-terms'][2]);
                $sql->bindParam(3, $_SESSION['post-terms'][3]);
                $sql->bindParam(4, $today);
                $sql->bindParam(5, $_SESSION['post-terms'][0]);
                $sql->execute();

                break;

            //キャンペーン送料の削除
            case 'posterms-del':
                $sql = $pdo->prepare('UPDATE postage_free
                                    SET del_kbn = 1 
                                    WHERE postage_fee_free_id  = ?');
                $sql->bindParam(1, $_SESSION['post-terms'][0]);
                $sql->execute();
                break;

            //キャンペーン送料の追加
            case 'posterms-add':
                $sql = $pdo->query('SELECT IFNULL(MAX(postage_fee_free_id), 0) + 1 AS next_id FROM postage_free');
                $sql->execute();
                $data = $sql->fetch();
                $_SESSION['post-terms'][0] = (int)$data['next_id'];
                $sql = $pdo->prepare('INSERT INTO postage_free 
                                    (postage_fee_free_id, postage_fee_free, start_date, end_date, del_kbn, ins_date)
                                    VALUES (?, ?, ?, ?, 0, ?)');
                $sql->bindParam(1, $_SESSION['post-terms'][0]);
                $sql->bindParam(2, $_SESSION['post-terms'][1]);
                $sql->bindParam(3, $_SESSION['post-terms'][2]);
                $sql->bindParam(4, $_SESSION['post-terms'][3]);
                $sql->bindParam(5, $today);
                $sql->execute();
                break;
        }
        
    } catch (Exception $e){
        $_SESSION['postage'] = 'error';
        $_SESSION['post-terms'][0] = $e;
    }
}
header('Location: postage-edit-finish.php');
?>