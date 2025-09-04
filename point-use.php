<?php 
session_start(); 
require 'common.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['use_points'])) {
    $use_points = intval($_POST['use_points']);
    $customer_id = $_SESSION['customer']['id'];
    
    // 保有ポイントを確認
    $sql = $pdo->prepare('SELECT point FROM customer WHERE id = ?');
    $sql->bindParam(1, $customer_id, PDO::PARAM_INT);
    $sql->execute();
    $customer = $sql->fetch();
    
    if ($customer && $customer['point'] >= $use_points && $use_points >= 0) {
        $_SESSION['use_points'] = $use_points;
        $_SESSION['customer']['point'] = $customer['point']; // セッション更新
        echo json_encode(['success' => true, 'message' => $use_points . 'ポイントを使用設定しました']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ポイントが不足しています']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '無効なリクエストです']);
}
?>