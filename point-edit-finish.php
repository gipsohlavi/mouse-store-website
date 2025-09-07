<?php session_start(); ?>
<?php
if (isset($_SESSION['url'][0])){
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //0 = 現在のURL　1=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];
//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/point-edit-confirm.php$/',$_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: point-edit.php');
}
?>
<?php require 'common.php'; ?>
<?php
// 実行処理（確認→実行でDB反映）
if (isset($_SESSION['point'])){
    switch ($_SESSION['point']){
        case 'point':
            $point_campaign_id = 1;
            $sql=$pdo->prepare('UPDATE point_campaign SET campaign_point_rate = ?, upd_date = ? WHERE point_campaign_id = ?');
            $sql->bindParam(1,$_SESSION['cprate']);
            $sql->bindParam(2,$today);
            $sql->bindParam(3,$point_campaign_id);
            $sql->execute();
            break;

        case 'pc-change':
            $sql=$pdo->prepare('UPDATE point_campaign SET campaign_name = ?, campaign_point_rate = ?, start_date = ?, end_date = ?, priority = ?, upd_date = ? WHERE point_campaign_id  = ?');
            $sql->bindParam(1,$_SESSION['cp-data'][1]);
            $sql->bindParam(2,$_SESSION['cp-data'][2]);
            $sql->bindParam(3,$_SESSION['cp-data'][3]);
            $sql->bindParam(4,$_SESSION['cp-data'][4]);
            $sql->bindParam(5,$_SESSION['cp-data'][5]);
            $sql->bindParam(6,$today);
            $sql->bindParam(7,$_SESSION['cp-data'][0]);
            $sql->execute();
            break;

        case 'pc-del':
            $sql=$pdo->prepare('UPDATE point_campaign SET del_kbn = 1 WHERE point_campaign_id  = ?');
            $sql->bindParam(1,$_SESSION['cp-data'][0]);
            $sql->execute();
            break;

        case 'pc-add':
            // 新しいIDを採番
            $maxStmt = $pdo->query('SELECT IFNULL(MAX(point_campaign_id),0) FROM point_campaign');
            $newId = (int)$maxStmt->fetchColumn() + 1;
            $_SESSION['cp-data'][0] = $newId;
            $sql=$pdo->prepare('INSERT INTO point_campaign (point_campaign_id, campaign_name, campaign_point_rate, start_date, end_date, priority, ins_date) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $sql->bindParam(1,$newId);
            $sql->bindParam(2,$_SESSION['cp-data'][1]);
            $sql->bindParam(3,$_SESSION['cp-data'][2]);
            $sql->bindParam(4,$_SESSION['cp-data'][3]);
            $sql->bindParam(5,$_SESSION['cp-data'][4]);
            $sql->bindParam(6,$_SESSION['cp-data'][5]);
            $sql->bindParam(7,$today);
            $sql->execute();
            break;

        case 'pcitem-del':
            if (isset($_REQUEST['ctid'])) { $_SESSION['ctid'] = $_REQUEST['ctid']; }
            $sql=$pdo->prepare('UPDATE campaign_target SET del_kbn = 1 WHERE id = ?');
            $sql->bindParam(1,$_SESSION['ctid']);
            $sql->execute();
            break;
    }
}
?>
<?php require 'admin-header.php'; ?>
<?php require 'admin-menu.php'; ?>
<div class="admin-container">
    <div class="admin-page-title">
        <h2>
            <i class="fas fa-check-circle"></i> 
            <?php
            if (isset($_SESSION['point'])){
                switch ($_SESSION['point']){
                    ////基本ポイント付与率の変更
                    case 'point':
                        echo '<p>基本送料無料条件の変更が完了しました</p>';
                        break;
                    
                    //キャンペーン送料の変更
                    case 'pc-change':
                        echo '<p>キャンペーンID:' . $_SESSION['cp-data'][0]. 'の変更が完了しました</p>';
                        break;
                    
                    //キャンペーン送料の削除
                    case 'pc-del':
                        echo '<p>キャンペーンID:' . $_SESSION['cp-data'][0]. 'を削除しました</p>';
                        break;
                    
                    //キャンペーン送料の追加
                    case 'pc-add':
                        echo '<p>キャンペーンID:' . $_SESSION['cp-data'][0]. 'を追加しました</p>';
                        break;
                    //キャンペーン該当商品の削除
                    case 'pcitem-del':
                        echo '<p>キャンペーン対象から商品を削除しました</p>';
                        unset($_SESSION['cpitem-data']);
                        unset($_SESSION['ctid']);
                        break;
                }
                
                if ($_SESSION['point'] != 'pcitem-del') {    
                    unset($_SESSION['point']);
                    unset($_SESSION['cp-data']);
                }
            }
            ?>
        </h2>
    </div>
    <?php 
    if(isset($_SESSION['point']) && $_SESSION['point'] === 'pcitem-del') {
        echo '<form action="point-detail-edit.php">';
    } else {
        echo '<form action="point-edit.php">';
    }
    ?>
        <button type="submit" class="admin-btn admin-btn-secondary">
            <i class="fas fa-arrow-left"></i> 戻る
        </button>
    </form>
</div>
<?php require 'admin-footer.php'; ?>
