<?php
session_start();
require 'common.php';

// ログインチェック
if (!isset($_SESSION['customer'])) {
    header('Location: login-input.php');
    exit;
}

// カートの中身チェック
if (!isset($_SESSION['product']) || empty($_SESSION['product'])) {
    header('Location: cart-show.php');
    exit;
}

// POSTデータのチェック
if (!isset($_POST['shipping_address_id']) || !is_numeric($_POST['shipping_address_id'])) {
    $_SESSION['error'] = '配送先が選択されていません。';
    header('Location: purchase-shipping-select.php');
    exit;
}

$customer_id = $_SESSION['customer']['id'];
$address_id = (int)$_POST['shipping_address_id'];

try {
    // 選択された配送先が顧客のものかチェック
    $sql = $pdo->prepare('
        SELECT id, address_name, recipient_name, region_id, remote_island_check 
        FROM shipping_addresses 
        WHERE id = ? AND customer_id = ?
    ');
    $sql->bindParam(1, $address_id);
    $sql->bindParam(2, $customer_id);
    $sql->execute();
    $selected_address = $sql->fetch();
    
    if (!$selected_address) {
        throw new Exception('選択された配送先が見つかりません。');
    }
    
    // セッションに配送先IDを保存
    $_SESSION['selected_shipping_address'] = $address_id;
    
    // 成功メッセージ
    $_SESSION['message'] = '配送先「' . h($selected_address['address_name']) . '」を選択しました。';
    
    // 購入手続きページにリダイレクト
    header('Location: purchase-input.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: purchase-shipping-select.php');
    exit;
}
?>