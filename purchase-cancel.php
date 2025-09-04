<?php
// purchase-cancel.php

session_start();
require 'common.php';

// レスポンスをJSON形式で返すことを指定
header('Content-Type: application/json');

// POSTリクエストであることを確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    exit;
}

// 注文IDを受け取る
$purchase_id = $_POST['id'] ?? null;

// IDが有効な数値か確認
if (!filter_var($purchase_id, FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => '無効な注文IDです。']);
    exit;
}

try {
    // トランザクションを開始
    $pdo->beginTransaction();

    // purchase_detail テーブルから関連する明細を削除
    // purchaseテーブルに外部キー制約があるため、先に明細を削除する必要がある
    $sql_detail = $pdo->prepare('DELETE FROM purchase_detail WHERE purchase_id = ?');
    $sql_detail->execute([$purchase_id]);

    // tax_total テーブルから関連する税額情報を削除
    $sql_tax = $pdo->prepare('DELETE FROM tax_total WHERE id = ?');
    $sql_tax->execute([$purchase_id]);

    // purchase テーブルから注文情報を削除
    $sql_purchase = $pdo->prepare('DELETE FROM purchase WHERE id = ?');
    $sql_purchase->execute([$purchase_id]);
    
    // 変更を確定
    $pdo->commit();

    // 成功レスポンスを返す
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    // エラーが発生したらロールバック
    $pdo->rollBack();

    // 失敗レスポンスを返す
    echo json_encode([
        'success' => false,
        'message' => '注文のキャンセルに失敗しました: ' . $e->getMessage()
    ]);
}
?>