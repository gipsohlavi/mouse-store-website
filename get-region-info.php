<?php
/**
 * 地域情報取得API
 * POSTで都道府県を受け取り、地域情報と配送料をJSONで返す
 */

require 'common.php';

// Content-Typeをjsonに設定
header('Content-Type: application/json; charset=utf-8');

// POSTメソッドのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POSTメソッドのみ対応しています']);
    exit;
}

// 都道府県の取得とバリデーション
$prefecture = trim($_POST['prefecture'] ?? '');

if (empty($prefecture)) {
    echo json_encode(['success' => false, 'error' => '都道府県を入力してください']);
    exit;
}

try {
    // 都道府県から地域情報を取得
    $sql = $pdo->prepare('
        SELECT r.region_id, rm.name as region_name
        FROM region r
        JOIN master rm ON rm.master_id = r.region_id AND rm.kbn = 11
        JOIN master pm ON pm.master_id = r.prefectures_id AND pm.kbn = 12
        WHERE pm.name = ?
        LIMIT 1
    ');
    $sql->bindParam(1, $prefecture);
    $sql->execute();
    $region_data = $sql->fetch(PDO::FETCH_ASSOC);
    
    if (!$region_data) {
        echo json_encode([
            'success' => false, 
            'error' => 'この都道府県の地域情報が見つかりませんでした'
        ]);
        exit;
    }
    
    // 配送料金を取得
    $shipping_fee_sql = $pdo->prepare('
        SELECT postage_fee 
        FROM postage 
        WHERE region_id = ? AND end_date IS NULL
        ORDER BY start_date DESC
        LIMIT 1
    ');
    $shipping_fee_sql->bindParam(1, $region_data['region_id']);
    $shipping_fee_sql->execute();
    $shipping_fee_data = $shipping_fee_sql->fetch(PDO::FETCH_ASSOC);
    
    $shipping_fee = $shipping_fee_data ? $shipping_fee_data['postage_fee'] : 1200; // デフォルト配送料
    
    // レスポンスデータを構築
    $response = [
        'success' => true,
        'region_id' => $region_data['region_id'],
        'region_name' => $region_data['region_name'],
        'prefecture' => $prefecture,
        'shipping_fee' => $shipping_fee,
        'shipping_fee_formatted' => number_format($shipping_fee)
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Region Info API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'システムエラーが発生しました'
    ]);
}
?>