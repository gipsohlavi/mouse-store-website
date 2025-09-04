<?php
// campaign-target-edit-update.php - 処理実行（変更なし）
session_start();
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0];
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];
//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/(campaign-target-edit-confirm.php)$/', $_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: point-edit.php');
}
require 'common.php';

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

//登録する商品種別
$target_type = 2;
foreach ($ctdata as $row) {
    $sql = $pdo->prepare('INSERT INTO campaign_target 
                        (point_campaign_id, target_type, target_id) 
                        VALUES (?, ?, ?)');
    $sql->bindParam(1, $_SESSION['cp-data'][0]);
    $sql->bindParam(2, $target_type);
    $sql->bindParam(3, $row[0]);
    $sql->execute();
}

header('Location: campaign-target-edit-finish.php');
