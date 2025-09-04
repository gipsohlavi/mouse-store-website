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
    
    // 削除対象の配送先情報を取得
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
        throw new Exception('この配送先を削除する権限がありません。');
    }
    
    // デフォルト配送先の削除チェック
    if ($address['is_default']) {
        // 他に配送先があるかチェック
        $sql = $pdo->prepare('SELECT COUNT(*) FROM shipping_addresses WHERE customer_id = ? AND id != ?');
        $sql->bindParam(1, $customer_id);
        $sql->bindParam(2, $address_id);
        $sql->execute();
        $other_count = $sql->fetchColumn();
        
        if ($other_count == 0) {
            throw new Exception('最後の配送先は削除できません。少なくとも1つの配送先が必要です。');
        }
        
        // 他の配送先を新しいデフォルトに設定
        $sql = $pdo->prepare('
            UPDATE shipping_addresses 
            SET is_default = 1 
            WHERE customer_id = ? AND id != ? 
            ORDER BY created_at 
            LIMIT 1
        ');
        $sql->bindParam(1, $customer_id);
        $sql->bindParam(2, $address_id);
        $sql->execute();
    }
    
    // 配送先を削除
    $sql = $pdo->prepare('DELETE FROM shipping_addresses WHERE id = ? AND customer_id = ?');
    $sql->bindParam(1, $address_id);
    $sql->bindParam(2, $customer_id);
    $sql->execute();
    
    // 削除された行数をチェック
    if ($sql->rowCount() == 0) {
        throw new Exception('配送先の削除に失敗しました。');
    }
    
    $pdo->commit();
    $_SESSION['message'] = '配送先「' . h($address['address_name']) . '」を削除しました。';
    
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