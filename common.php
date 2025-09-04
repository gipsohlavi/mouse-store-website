<?php
$pdo = new PDO('mysql:host=localhost;dbname=skadaip2;charset=utf8', 'root', '');

// htmlspecialcharsを使いやすくする関数
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$today = date("Y-m-d H:i:s");
$region_id = 11;

// 商品画像を取得（最適化版）
function getImage($productId, $pdo)
{
    // 必要な画像カラムのみ取得
    $sql = $pdo->prepare('SELECT image_name1, image_name2, image_name3, image_name4, image_name5, image_name6, image_name7 FROM product WHERE id = ? LIMIT 1');
    $sql->bindParam(1, $productId, PDO::PARAM_INT);
    $sql->execute();
    
    $row = $sql->fetch(); // foreachではなくfetchを使用
    
    if ($row) {
        return [
            $row['image_name1'],
            $row['image_name2'],
            $row['image_name3'],
            $row['image_name4'],
            $row['image_name5'],
            $row['image_name6'],
            $row['image_name7']
        ];
    }
    
    // データが見つからない場合のデフォルト
    return [$productId, $productId.'_1', $productId.'_2', $productId.'_3', '', '', ''];
}

// さらに最適化された版（商品データを再利用）
function getImageFromProductData($productData)
{
    return [
        $productData['image_name1'] ?? $productData['id'],
        $productData['image_name2'] ?? $productData['id'].'_1',
        $productData['image_name3'] ?? $productData['id'].'_2',
        $productData['image_name4'] ?? $productData['id'].'_3',
        $productData['image_name5'] ?? '',
        $productData['image_name6'] ?? '',
        $productData['image_name7'] ?? ''
    ];
}

//各種金額を求める
function getPrices($purchaseId, $pdo)
{
    $sql = $pdo->prepare('select p.grand_total, sum(total) as total, tt.tax_amount, tt.sub_total, t.tax ' .
        'from purchase p ' .
        'inner join purchase_detail pd on p.id = pd.purchase_id ' .
        'inner join tax_total tt on p.id = tt.id ' .
        'inner join tax t on t.tax_id = tt.tax_id ' .
        'where p.id = ? ' .
        'group by pd.purchase_id, tt.tax_id');
    $sql->bindParam(1, $purchaseId, PDO::PARAM_INT);
    $sql->execute();
    $prices = $sql->fetchAll();
    return $prices;
}

// 送料を計算する関数
function calculateShipping($customer_id, $total_amount, $pdo) {
    // 顧客の地域IDを取得
    $sql = $pdo->prepare('SELECT region_id, remote_island_check FROM customer WHERE id = ?');
    $sql->bindParam(1, $customer_id, PDO::PARAM_INT);
    $sql->execute();
    $customer = $sql->fetch();
    
    if (!$customer) {
        return ['error' => '顧客情報が見つかりません'];
    }
    
    // 送料無料基準を取得（現在有効な設定）
    $sql = $pdo->prepare('SELECT postage_fee_free FROM postage_free WHERE start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) AND del_kbn = 0 ORDER BY start_date DESC LIMIT 1');
    $sql->execute();
    $free_threshold = $sql->fetch();
    
    $shipping_fee = 0;
    $is_free_shipping = false;
    
    if ($free_threshold && $total_amount >= $free_threshold['postage_fee_free']) {
        $is_free_shipping = true;
    } else {
        // 通常送料を取得
        $sql = $pdo->prepare('SELECT postage_fee FROM postage WHERE region_id = ? AND start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) ORDER BY start_date DESC LIMIT 1');
        $sql->bindParam(1, $customer['region_id'], PDO::PARAM_INT);
        $sql->execute();
        $postage = $sql->fetch();
        
        if ($postage) {
            $shipping_fee = $postage['postage_fee'];
        }
        
        // 離島手数料を追加
        if ($customer['remote_island_check']) {
            $sql = $pdo->prepare('SELECT remote_island_fee FROM postage_remote_island WHERE start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) ORDER BY start_date DESC LIMIT 1');
            $sql->execute();
            $remote_fee = $sql->fetch();
            if ($remote_fee) {
                $shipping_fee += $remote_fee['remote_island_fee'];
            }
        }
    }
    
    return [
        'shipping_fee' => $shipping_fee,
        'is_free_shipping' => $is_free_shipping,
        'region_id' => $customer['region_id'],
        'remote_island_check' => $customer['remote_island_check']
    ];
}

// ポイントを計算する関数
function calculatePoints($total_amount, $product_ids, $pdo) {
    $total_points = 0;
    
    // 基本ポイント（priority=0）を取得
    $sql = $pdo->prepare('SELECT campaign_point_rate FROM point_campaign WHERE priority = 0 AND start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) AND del_kbn = 0');
    $sql->execute();
    $basic_campaign = $sql->fetch();
    
    if ($basic_campaign) {
        $total_points += floor($total_amount * $basic_campaign['campaign_point_rate']);
    }
    
    // 特別キャンペーン（最高優先度）を取得
    if (!empty($product_ids)) {
        $sql = $pdo->prepare('SELECT pc.campaign_point_rate, pc.priority FROM point_campaign pc 
                             INNER JOIN campaign_target ct ON pc.point_campaign_id = ct.point_campaign_id 
                             WHERE pc.start_date <= NOW() AND (pc.end_date IS NULL OR pc.end_date >= NOW()) 
                             AND pc.del_kbn = 0 AND ct.del_kbn = 0 AND pc.priority > 0
                             AND ct.target_type = 2 AND ct.target_id IN (' . implode(',', array_fill(0, count($product_ids), '?')) . ')
                             ORDER BY pc.priority DESC LIMIT 1');
        
        $i = 1;
        foreach ($product_ids as $product_id) {
            $sql->bindParam($i++, $product_id, PDO::PARAM_INT);
        }
        $sql->execute();
        $special_campaign = $sql->fetch();
        
        if ($special_campaign) {
            $total_points += floor($total_amount * $special_campaign['campaign_point_rate']);
        }
    }
    
    return $total_points;
}

/**
 * ポイント履歴を記録する関数
 */
function recordPointHistory($pdo, $customer_id, $point_change, $transaction_type, $description = '', $related_purchase_id = null) {
    try {
        // 現在のポイント残高を取得
        $customer_sql = $pdo->prepare('SELECT point FROM customer WHERE id = ?');
        $customer_sql->bindParam(1, $customer_id, PDO::PARAM_INT);
        $customer_sql->execute();
        $customer_data = $customer_sql->fetch();
        $current_balance = $customer_data ? $customer_data['point'] : 0;
        
        // 新しい残高を計算
        $new_balance = $current_balance + $point_change;
        
        // ポイント履歴を記録
        $history_sql = $pdo->prepare('
            INSERT INTO point_history 
            (customer_id, point_change, point_balance, transaction_type, related_purchase_id, description) 
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $history_sql->execute([
            $customer_id,
            $point_change,
            $new_balance,
            $transaction_type,
            $related_purchase_id,
            $description
        ]);
        
        // customerテーブルのポイントを更新
        $update_sql = $pdo->prepare('UPDATE customer SET point = ? WHERE id = ?');
        $update_sql->execute([$new_balance, $customer_id]);
        
        return $new_balance;
        
    } catch (Exception $e) {
        error_log("Point history recording error: " . $e->getMessage());
        return false;
    }
}