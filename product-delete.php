<?php
// product-delete.php

session_start();
require 'common.php';

// レスポンスをJSON形式で返すことを指定
header('Content-Type: application/json');

// POSTリクエストであることを確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    exit;
}

// 削除対象のIDを受け取る
$id = $_POST['id'] ?? null;

// IDが有効な数値か確認
if (!filter_var($id, FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => '無効な商品IDです。']);
    exit;
}

try {
    // トランザクションを開始
    $pdo->beginTransaction();

    // 関連テーブルのレコードを先に削除（外部キー制約がある場合）
    // 例えば、product_master_relation テーブルから関連するレコードを削除
    $sql_relation = $pdo->prepare('DELETE FROM product_master_relation WHERE product_id = ?');
    $sql_relation->execute([$id]);

    // `product` テーブルから商品を削除
    $sql_product = $pdo->prepare('DELETE FROM product WHERE id = ?');
    $sql_product->execute([$id]);

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
        'message' => '削除に失敗しました: ' . $e->getMessage()
    ]);
}
?>