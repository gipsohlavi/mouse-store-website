<?php session_start();?>
<?php
if (isset($_SESSION['url'][0])){
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //1 = 現在のURL　0=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];

//遷移元のチェック
if (!isset($_SESSION['url'][1]) || !(preg_match('/point-detail-edit\.php$/',$_SESSION['url'][1]) === 0 || preg_match('/point-edit-confirm\.php$/',$_SESSION['url'][1]) === 0)) {
    return;
    unset($_SESSION['url']);
    header('Location: point-edit.php');
}
?>
<?php require 'common.php'; ?>
<?php
if (isset($_SESSION['point'])){
    //$_SESSION['point'][0] : point_campaign_id
    //$_SESSION['point'][1] : campaign_name
    //$_SESSION['point'][2] : campaign_point_rate
    //$_SESSION['point'][3] : satrt_date
    //$_SESSION['point'][4] : end_date
    //$_SESSION['point'][5] : priority
    switch ($_SESSION['point']){
        //基本ポイント付与率の変更
        case 'point':
            $point_campaign_id = 1;
            $sql=$pdo->prepare('UPDATE point_campaign SET campaign_point_rate = ?, upd_date = ? 
                                WHERE point_campaign_id = ?');
            $sql->bindParam(1,$_SESSION['cprate']);
            $sql->bindParam(2,$today);
            $sql->bindParam(3,$point_campaign_id);
            $sql->execute();  
            break;
        
        //キャンペーンの変更
        case 'pc-change':
            $sql=$pdo->prepare('UPDATE point_campaign 
                                SET campaign_name = ?, campaign_point_rate = ?, start_date = ?, 
                                    end_date = ?, priority = ?, upd_date = ? 
                                WHERE point_campaign_id  = ?');
            $sql->bindParam(1,$_SESSION['cp-data'][1]);
            $sql->bindParam(2,$_SESSION['cp-data'][2]);
            $sql->bindParam(3,$_SESSION['cp-data'][3]);
            $sql->bindParam(4,$_SESSION['cp-data'][4]);
            $sql->bindParam(5,$_SESSION['cp-data'][5]);
            $sql->bindParam(6,$today);
            $sql->bindParam(7,$_SESSION['cp-data'][0]);
            $sql->execute();  
            
            break;
        
        //キャンペーンの削除
        case 'pc-del':
            $sql=$pdo->prepare('UPDATE point_campaign 
                                SET del_kbn = 1 
                                WHERE point_campaign_id  = ?');
            $sql->bindParam(1,$_SESSION['cp-data'][0]);
            $sql->execute();  
            break;
        
        //キャンペーンの追加
        case 'pc-add':
            $sql=$pdo->query('SELECT MAX(point_campaign_id) FROM point_campaign ');
            $sql->execute();
            $data = $sql->fetch();
            $_SESSION['cp-data'][0] = $data[0] + 1;
            $sql=$pdo->prepare('INSERT INTO point_campaign 
                                (point_campaign_id, campaign_name, campaign_point_rate, 
                                    start_date, end_date, priority, ins_date) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)');
            $sql->bindParam(1,$_SESSION['cp-data'][0]);
            $sql->bindParam(2,$_SESSION['cp-data'][1]);
            $sql->bindParam(3,$_SESSION['cp-data'][2]);
            $sql->bindParam(4,$_SESSION['cp-data'][3]);
            $sql->bindParam(5,$_SESSION['cp-data'][4]);
            $sql->bindParam(6,$_SESSION['cp-data'][5]);
            $sql->bindParam(7,$today);
            $sql->execute();
            break;

        //キャンペーン対象商品の削除
        case 'pcitem-del':
            $_SESSION['ctid'] = $_REQUEST['ctid'];
            $sql=$pdo->prepare('UPDATE campaign_target 
                                SET del_kbn = 1 
                                WHERE id = ?');
            $sql->bindParam(1,$_REQUEST['ctid']);
            $sql->execute();
            break;
    }
}
header('Location: point-edit-finish.php');
?>