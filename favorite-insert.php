<?php
session_start();
require 'common.php';

// Ajaxリクエストかどうかを判定
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Ajaxリクエストの場合
if ($is_ajax) {
    header('Content-Type: application/json');

    $response = [
        'success' => false,
        'message' => '',
        'is_favorite' => false,
        'action' => ''
    ];

    if (!isset($_SESSION['customer'])) {
        $response['message'] = 'ログインが必要です';
        echo json_encode($response);
        exit;
    }

    $customer_id = $_SESSION['customer']['id'];
    $product_id = isset($_POST['id']) ? $_POST['id'] : (isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
    $action = isset($_POST['action']) ? $_POST['action'] : 'toggle';

    try {
        // 現在のお気に入り状態を確認
        $check_sql = $pdo->prepare('SELECT * FROM favorite WHERE customer_id = ? AND product_id = ?');
        $check_sql->bindParam(1, $customer_id);
        $check_sql->bindParam(2, $product_id);
        $check_sql->execute();
        $exists = $check_sql->fetch();

        if ($action === 'check') {
            // 状態確認のみ
            $response['success'] = true;
            $response['is_favorite'] = (bool)$exists;
            $response['action'] = 'check';
        } elseif ($action === 'toggle' || $action === 'add' || $action === 'remove') {
            if ($exists && ($action === 'toggle' || $action === 'remove')) {
                // 削除
                $delete_sql = $pdo->prepare('DELETE FROM favorite WHERE customer_id = ? AND product_id = ?');
                $delete_sql->bindParam(1, $customer_id);
                $delete_sql->bindParam(2, $product_id);
                $delete_sql->execute();

                $response['success'] = true;
                $response['message'] = 'お気に入りから削除しました';
                $response['is_favorite'] = false;
                $response['action'] = 'removed';
            } elseif (!$exists && ($action === 'toggle' || $action === 'add')) {
                // 追加
                $insert_sql = $pdo->prepare('INSERT INTO favorite (customer_id, product_id, favorite_date) VALUES (?, ?, ?)');
                $insert_sql->bindParam(1, $customer_id);
                $insert_sql->bindParam(2, $product_id);
                $now = date('Y-m-d H:i:s');
                $insert_sql->bindParam(3, $now);
                $insert_sql->execute();

                $response['success'] = true;
                $response['message'] = 'お気に入りに追加しました';
                $response['is_favorite'] = true;
                $response['action'] = 'added';
            } else {
                $response['success'] = true;
                $response['message'] = '変更なし';
                $response['is_favorite'] = (bool)$exists;
                $response['action'] = 'no_change';
            }
        }
    } catch (Exception $e) {
        $response['message'] = 'エラーが発生しました';
    }

    echo json_encode($response);
    exit;
}

// 通常のリクエストの場合（既存の処理）
require 'header.php';
require 'menu.php';

if (isset($_SESSION['customer'])) {
    // 既存のお気に入り確認
    $check_sql = $pdo->prepare('SELECT * FROM favorite WHERE customer_id = ? AND product_id = ?');
    $check_sql->bindParam(1, $_SESSION['customer']['id']);
    $check_sql->bindParam(2, $_REQUEST['id']);
    $check_sql->execute();
    $exists = $check_sql->fetch();

    if (!$exists) {
        $sql = $pdo->prepare('INSERT INTO favorite (customer_id, product_id, favorite_date) VALUES (?, ?, ?)');
        $sql->bindParam(1, $_SESSION['customer']['id']);
        $sql->bindParam(2, $_REQUEST['id']);
        $now = date('Y-m-d H:i:s');
        $sql->bindParam(3, $now);
        $sql->execute();
        echo '<p>お気に入りに商品を追加しました。</p>';
    } else {
        echo '<p>この商品は既にお気に入りに登録されています。</p>';
    }
    echo '<hr>';
    require 'favorite.php';
} else {
    echo '<p>お気に入りに商品を追加するには、ログインしてください。</p>';
}

require 'footer.php';
