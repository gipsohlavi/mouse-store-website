<?php
/**
 * 郵便番号検索API
 * POSTで郵便番号を受け取り、住所情報と地域情報をJSONで返す
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

// 郵便番号の取得とバリデーション
$postcode = trim($_POST['postcode'] ?? '');

if (empty($postcode)) {
    echo json_encode(['success' => false, 'error' => '郵便番号を入力してください']);
    exit;
}

// 7桁の数字チェック
if (!preg_match('/^\d{7}$/', $postcode)) {
    echo json_encode(['success' => false, 'error' => '郵便番号は7桁の数字で入力してください']);
    exit;
}

try {
    // 郵便番号を3-4桁形式にフォーマット
    $formatted_postcode = substr($postcode, 0, 3) . '-' . substr($postcode, 3);
    
    // 実際のAPIでは郵便番号データベースまたは外部APIを使用
    // ここではサンプルデータとDB連携の実装例を示します
    
    // 1. 郵便番号から住所を取得（実装例）
    $address_data = getAddressFromPostcode($postcode, $pdo);
    
    if (!$address_data) {
        // 外部API（zipcloud等）を使用する場合の例
        $address_data = getAddressFromExternalAPI($postcode);
        
        if (!$address_data) {
            echo json_encode([
                'success' => false, 
                'error' => 'この郵便番号の住所情報が見つかりませんでした'
            ]);
            exit;
        }
    }
    
    // 2. 都道府県から地域情報を取得
    $region_data = getRegionInfoByPrefecture($address_data['prefecture'], $pdo);
    
    // 3. 配送料金を取得
    $shipping_fee = getShippingFee($region_data['region_id'], $pdo);
    
    // レスポンスデータを構築
    $response = [
        'success' => true,
        'postcode_formatted' => $formatted_postcode,
        'address' => [
            'prefecture' => $address_data['prefecture'],
            'city' => $address_data['city'],
            'town' => $address_data['town'] ?? '',
            'full' => $address_data['prefecture'] . $address_data['city'] . ($address_data['town'] ?? '')
        ],
        'region' => [
            'region_id' => $region_data['region_id'],
            'region_name' => $region_data['region_name'],
            'shipping_fee' => $shipping_fee,
            'shipping_fee_formatted' => number_format($shipping_fee)
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Postcode API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'システムエラーが発生しました'
    ]);
}

/**
 * データベースから郵便番号に対応する住所を取得
 * 実際の実装では郵便番号マスターテーブルから取得
 */
function getAddressFromPostcode($postcode, $pdo) {
    // サンプル実装：実際は郵便番号マスターテーブルを作成して検索
    $sample_data = [
        '1600023' => ['prefecture' => '東京都', 'city' => '新宿区', 'town' => '西新宿'],
        '1000001' => ['prefecture' => '東京都', 'city' => '千代田区', 'town' => '千代田'],
        '5300001' => ['prefecture' => '大阪府', 'city' => '大阪市北区', 'town' => '梅田'],
        '7300013' => ['prefecture' => '広島県', 'city' => '広島市中区', 'town' => '八丁堀'],
        '2310023' => ['prefecture' => '神奈川県', 'city' => '横浜市中区', 'town' => '山下町'],
        '4600002' => ['prefecture' => '愛知県', 'city' => '名古屋市中区', 'town' => '丸の内'],
        '8120011' => ['prefecture' => '福岡県', 'city' => '福岡市博多区', 'town' => '博多駅前'],
    ];
    
    return $sample_data[$postcode] ?? null;
    
    // 実際のDB実装例：
    /*
    $sql = $pdo->prepare('
        SELECT prefecture, city, town 
        FROM postcode_master 
        WHERE postcode = ?
    ');
    $sql->bindParam(1, $postcode);
    $sql->execute();
    return $sql->fetch(PDO::FETCH_ASSOC);
    */
}

/**
 * 外部APIから郵便番号情報を取得（zipcloud等）
 */
function getAddressFromExternalAPI($postcode) {
    $url = "https://zipcloud.ibsnet.co.jp/api/search?zipcode=" . $postcode;
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'KELOT Address Search'
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || $data['status'] !== 200 || empty($data['results'])) {
        return null;
    }
    
    $result = $data['results'][0];
    
    return [
        'prefecture' => $result['address1'],
        'city' => $result['address2'],
        'town' => $result['address3'] ?? ''
    ];
}

/**
 * 都道府県から地域情報を取得
 */
function getRegionInfoByPrefecture($prefecture, $pdo) {
    $sql = $pdo->prepare('
        SELECT r.region_id, m.name as region_name
        FROM region r
        JOIN master m ON m.master_id = r.region_id AND m.kbn = 11
        JOIN master pm ON pm.master_id = r.prefectures_id AND pm.kbn = 12
        WHERE pm.name = ?
        LIMIT 1
    ');
    $sql->bindParam(1, $prefecture);
    $sql->execute();
    $result = $sql->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        return $result;
    }
    
    // デフォルト地域（関東・中部）
    return [
        'region_id' => 3,
        'region_name' => '関東・中部'
    ];
}

/**
 * 地域IDから配送料金を取得
 */
function getShippingFee($region_id, $pdo) {
    $sql = $pdo->prepare('
        SELECT postage_fee 
        FROM postage 
        WHERE region_id = ? AND end_date IS NULL
        ORDER BY start_date DESC
        LIMIT 1
    ');
    $sql->bindParam(1, $region_id);
    $sql->execute();
    $result = $sql->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['postage_fee'] : 1200; // デフォルト配送料
}
?>