<?php
session_start();
require 'common.php';

//URLの取得
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0];
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];

//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/product-list-stock\.php\?id=([0-9]+)$/', $_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: product-list.php');
}

// POST以外のリクエストは拒否
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: product-list.php');
    exit;
}

// 商品IDの検証
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $response = ['success' => false, 'message' => '無効な商品IDです。'];
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        $_SESSION['product_error'] = $response['message'];
        header('Location: product-list.php');
    }
    exit;
}


$product_id = intval($_POST['id']);

//在庫データの更新
if (preg_match('/product-list-stock\.php\?id=([0-9]+)$/', $_SESSION['url'][1]) === 1) {
    try {
        $new_stock = (int)$_REQUEST['stock'] + (int)$_REQUEST['adjustment'];
        $sql = $pdo->prepare("UPDATE product SET stock_quantity = ? WHERE id = ?");
        $sql->bindParam(1,$new_stock);
        $sql->bindParam(2,$product_id);
        $sql->execute();
        header('Location: product-list-stock.php?id='.$product_id);
        exit;
    } catch (Exception $e) {
        header('Location: product-list.php');
        exit;
    }
}

//購入データの削除
try {
    // トランザクション開始
    $pdo->beginTransaction();

    // 商品の存在確認
    $sql = $pdo->prepare("SELECT name FROM product WHERE id = ?");
    $sql->execute([$product_id]);
    $product = $sql->fetch();

    if (!$product) {
        throw new Exception('指定された商品が見つかりません。');
    }

    // 関連データの削除チェック（購入履歴がある場合は削除不可）
    $sql = $pdo->prepare("SELECT COUNT(*) FROM purchase_detail WHERE product_id = ?");
    $sql->execute([$product_id]);
    $purchase_count = $sql->fetchColumn();

    if ($purchase_count > 0) {
        throw new Exception('この商品には購入履歴があるため削除できません。');
    }

    // お気に入り関連の削除
    $sql = $pdo->prepare("DELETE FROM favorite WHERE product_id = ?");
    $sql->execute([$product_id]);

    // レビュー関連の削除
    $sql = $pdo->prepare("DELETE FROM review WHERE product_id = ?");
    $sql->execute([$product_id]);

    // 商品マスター関連の削除
    $sql = $pdo->prepare("DELETE FROM product_master_relation WHERE product_id = ?");
    $sql->execute([$product_id]);

    // キャンペーン対象の削除
    $sql = $pdo->prepare("DELETE FROM campaign_target WHERE target_id = ? AND target_type = 2");
    $sql->execute([$product_id]);

    // 商品画像の削除
    for ($i = 1; $i <= 7; $i++) {
        $image_files = [
            "images/{$product_id}.jpg",
            "images/{$product_id}_{$i}.jpg"
        ];

        foreach ($image_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    // 商品データの削除
    $sql = $pdo->prepare("DELETE FROM product WHERE id = ?");
    $sql->execute([$product_id]);

    // トランザクションコミット
    $pdo->commit();

    $response = [
        'success' => true,
        'message' => "商品「{$product['name']}」を削除しました。"
    ];

    // Ajax リクエストかどうかで応答を分岐
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        $_SESSION['product_success'] = $response['message'];
        header('Location: product-list.php');
    }
} catch (Exception $e) {
    // エラー時はロールバック
    $pdo->rollBack();

    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];

    // Ajax リクエストかどうかで応答を分岐
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        $_SESSION['product_error'] = $response['message'];
        header('Location: product-list.php');
    }
}
exit;
