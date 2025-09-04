<?php
session_start();
require 'common.php';

// ログインチェック
if (!isset($_SESSION['customer'])) {
    $_SESSION['error'] = 'ログインが必要です。';
    header('Location: login-input.php');
    exit;
}

// IDパラメータのチェック
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = '無効なリクエストです。';
    header('Location: shipping-address-list.php');
    exit;
}

$customer_id = $_SESSION['customer']['id'];
$address_id = (int)$_GET['id'];

try {
    $pdo->beginTransaction();
    
    // 対象の配送先情報を取得
    $sql = $pdo->prepare('SELECT customer_id, address_name, is_default FROM shipping_addresses WHERE id = ?');
    $sql->bindParam(1, $address_id);
    $sql->execute();
    $address = $sql->fetch();
    
    // 存在チェック
    if (!$address) {
        throw new Exception('指定された配送先が見つかりません。');
    }
    
    // 権限チェック
    if ($address['customer_id'] != $customer_id) {
        throw new Exception('この配送先を設定する権限がありません。');
    }
    
    // 既にデフォルトの場合
    if ($address['is_default']) {
        throw new Exception('この配送先は既にデフォルト設定されています。');
    }
    
    // 現在のデフォルト配送先を取得（ログ用）
    $sql = $pdo->prepare('SELECT address_name FROM shipping_addresses WHERE customer_id = ? AND is_default = 1');
    $sql->bindParam(1, $customer_id);
    $sql->execute();
    $current_default = $sql->fetch();
    
    // すべての配送先のデフォルトを解除
    $sql = $pdo->prepare('UPDATE shipping_addresses SET is_default = 0 WHERE customer_id = ?');
    $sql->bindParam(1, $customer_id);
    $sql->execute();
    
    // 指定された配送先をデフォルトに設定
    $sql = $pdo->prepare('UPDATE shipping_addresses SET is_default = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND customer_id = ?');
    $sql->bindParam(1, $address_id);
    $sql->bindParam(2, $customer_id);
    $sql->execute();
    
    // 更新された行数をチェック
    if ($sql->rowCount() == 0) {
        throw new Exception('デフォルト配送先の設定に失敗しました。');
    }
    
    $pdo->commit();
    
    // 成功メッセージ
    $message = 'デフォルト配送先を「' . h($address['address_name']) . '」に変更しました。';
    if ($current_default) {
        $message .= '（旧デフォルト：「' . h($current_default['address_name']) . '」）';
    }
    $_SESSION['message'] = $message;
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = $e->getMessage();
}

// 一覧ページにリダイレクト
header('Location: shipping-address-list.php');
exit;
?>