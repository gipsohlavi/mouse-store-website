<?php session_start(); ?>
<?php
if (isset($_SESSION['url'][0])){
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //0 = 現在のURL　1=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];

//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/(campaign-target-edit.php)$/',$_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: point-edit.php');
}
?>
<?php require 'common.php'; ?>
<?php
if (isset($_REQUEST['check'])){
    $_SESSION['ct-data'] = $_REQUEST['check'];
    header('Location: campaign-target-edit-confirm.php');
} else {
    $_SESSION['error6'] = '<p><font color="red">追加する場合は商品を選択してください</font></p>';
    header('Location: campaign-target-edit.php');
}
?>
