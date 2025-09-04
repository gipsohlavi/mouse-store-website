<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>
<?php require 'menu.php'; ?>
<?php require 'review-list.php'; ?>

<?php
// 商品ID検証
$product_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if ($product_id <= 0) {
    header('Location: product.php');
    exit;
}

// 基本ポイント付与率取得
$point_campaign_id = 1;
$sql = $pdo->prepare('SELECT campaign_point_rate FROM point_campaign WHERE point_campaign_id = ?');
$sql->bindParam(1, $point_campaign_id);
$sql->execute();
$base_point_data = $sql->fetch();

// メイン商品データ取得（基本情報のみ）
$sql = $pdo->prepare('
    SELECT p.*
    FROM product p
    WHERE p.id = ?
    LIMIT 1
');
$sql->bindParam(1, $product_id, PDO::PARAM_INT);
$sql->execute();
$product = $sql->fetch();

if (!$product) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>商品が見つかりません</title></head><body>';
    echo '<script>alert("商品が見つかりません。商品一覧に戻ります。"); window.location.href = "product.php";</script>';
    echo '</body></html>';
    exit;
}

// マスター情報取得関数
function getMasterData($product_id, $kbn_id, $pdo) {
    $sql = $pdo->prepare('
        SELECT m.name 
        FROM product_master_relation pmr 
        JOIN master m ON pmr.master_id = m.master_id AND pmr.kbn_id = m.kbn
        WHERE pmr.product_id = ? AND pmr.kbn_id = ?
    ');
    $sql->bindParam(1, $product_id, PDO::PARAM_INT);
    $sql->bindParam(2, $kbn_id, PDO::PARAM_INT);
    $sql->execute();
    return $sql->fetchAll(PDO::FETCH_COLUMN);
}

function getSingleMasterData($product_id, $kbn_id, $pdo) {
    $sql = $pdo->prepare('
        SELECT m.name 
        FROM product_master_relation pmr 
        JOIN master m ON pmr.master_id = m.master_id AND pmr.kbn_id = m.kbn
        WHERE pmr.product_id = ? AND pmr.kbn_id = ?
        LIMIT 1
    ');
    $sql->bindParam(1, $product_id, PDO::PARAM_INT);
    $sql->bindParam(2, $kbn_id, PDO::PARAM_INT);
    $sql->execute();
    return $sql->fetchColumn();
}

// 各マスターデータを取得
$maker_name = getSingleMasterData($product_id, 1, $pdo);        // メーカー
$colors = getMasterData($product_id, 2, $pdo);                 // カラー
$connections = getMasterData($product_id, 3, $pdo);            // 接続方式
$sensor_names = getMasterData($product_id, 5, $pdo);           // センサー名
$shape_name = getSingleMasterData($product_id, 7, $pdo);       // 形状
$material_name = getSingleMasterData($product_id, 18, $pdo);   // 素材
$surface_name = getSingleMasterData($product_id, 19, $pdo);    // 表面仕上げ
$switch_names = getMasterData($product_id, 21, $pdo);          // スイッチメーカー
$mcu_name = getSingleMasterData($product_id, 22, $pdo);        // MCU
$charging_port = getSingleMasterData($product_id, 23, $pdo);   // 充電端子
$software_name = getSingleMasterData($product_id, 24, $pdo);   // ソフトウェア
$size_category = getSingleMasterData($product_id, 25, $pdo);   // サイズカテゴリー

// 画像データを商品データから直接取得
$images = getImageFromProductData($product);

// 商品説明文は商品データと一緒に取得済み（productテーブルから）
$product_description = [
    'overview' => $product['product_overview'],
    'detailed_review' => $product['product_detailed_review']
];

// ポイント計算
$pointsum = (int)($product['price'] * $base_point_data['campaign_point_rate']);
$percentage = $base_point_data['campaign_point_rate'];

// 特別キャンペーンポイント取得
$sql = $pdo->prepare('
    SELECT pc.campaign_point_rate 
    FROM point_campaign pc
    INNER JOIN campaign_target ct ON ct.point_campaign_id = pc.point_campaign_id 
    WHERE pc.point_campaign_id != ? 
    AND pc.del_kbn = 0 
    AND pc.start_date <= ?
    AND pc.end_date > ?
    AND ct.target_id = ?
    LIMIT 1
');
$sql->bindParam(1, $point_campaign_id);
$sql->bindParam(2, $today);
$sql->bindParam(3, $today);
$sql->bindParam(4, $product['id']);
$sql->execute();
$campaignrate = $sql->fetch();

if ($campaignrate) {
    $pointsum += (int)($product['price'] * $campaignrate['campaign_point_rate']);
    $percentage += $campaignrate['campaign_point_rate'];
}
?>

<style>
    .product-detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .product-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 40px;
    }

    .product-images {
        position: sticky;
        top: 20px;
    }

    .main-image {
        width: 100%;
        margin-bottom: 20px;
        border-radius: 8px;
        overflow: hidden;
    }

    .main-image img {
        width: 100%;
        height: auto;
    }

    .thumbnail-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .thumbnail-grid img {
        width: 100%;
        height: auto;
        cursor: pointer;
        border-radius: 4px;
        transition: opacity 0.3s;
    }

    .thumbnail-grid img:hover {
        opacity: 0.8;
    }

    .product-info {
        padding: 0 20px;
    }

    .product-title {
        font-size: 2em;
        margin-bottom: 10px;
        color: #333;
    }

    .product-price {
        font-size: 1.8em;
        color: #e74c3c;
        margin-bottom: 20px;
    }

    .product-actions {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .quantity-selector {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }

    .quantity-selector select {
        padding: 8px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
    }

    .btn {
        padding: 12px 30px;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background: #0056b3;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #545b62;
    }

    .specs-section {
        margin-top: 40px;
        padding-top: 40px;
        border-top: 2px solid #e9ecef;
    }

    .specs-title {
        font-size: 1.8em;
        margin-bottom: 30px;
        color: #333;
    }

    .specs-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }

    .spec-category {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
    }

    .spec-category h3 {
        font-size: 1.2em;
        color: #007bff;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #007bff;
    }

    .spec-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .spec-item:last-child {
        border-bottom: none;
    }

    .spec-label {
        color: #6c757d;
        font-size: 0.9em;
    }

    .spec-value {
        color: #333;
        font-weight: 500;
        text-align: right;
    }

    .spec-value.check {
        color: #28a745;
    }

    .btn-outline {
        background: transparent;
        border: 2px solid #007bff;
        color: #007bff;
    }

    .btn-outline:hover {
        background: #007bff;
        color: white;
    }

    .btn-secondary.registered {
        background: #e91e63;
    }

    .btn-secondary.registered:hover {
        background: #c2185b;
    }

    /* 獲得ポイントセクションのスタイル */
    .points-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: var(--radius-lg);
        padding: 20px;
        margin: 20px 0;
        color: white;
        box-shadow: var(--shadow-lg);
        position: relative;
        overflow: hidden;
    }

    .points-section::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
        transform: rotate(45deg);
        animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
        0% { transform: translateX(-100%) rotate(45deg); }
        100% { transform: translateX(100%) rotate(45deg); }
    }

    .points-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
        position: relative;
        z-index: 1;
    }

    .points-label {
        font-size: 1.1em;
        font-weight: 500;
        opacity: 0.9;
    }

    .points-value {
        font-size: 1.8em;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .points-icon {
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2em;
    }

    .points-percentage {
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.9em;
        margin-left: 10px;
    }

    .points-toggle {
        background: rgba(255,255,255,0.2);
        border: 1px solid rgba(255,255,255,0.3);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        cursor: pointer;
        transition: var(--transition);
        font-size: 0.9em;
        position: relative;
        z-index: 1;
    }

    .points-toggle:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-1px);
    }

    .points-breakdown {
        background: rgba(255,255,255,0.1);
        border-radius: var(--radius);
        padding: 15px;
        margin-top: 15px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
        position: relative;
        z-index: 1;
    }

    .breakdown-item {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }

    .breakdown-item:last-child {
        border-bottom: none;
        font-weight: bold;
        font-size: 1.1em;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 2px solid rgba(255,255,255,0.3);
    }

    /* アクションボタンエリアのスタイル */
    .product-actions {
        margin: 30px 0;
    }

    .quantity-selector {
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .quantity-selector label {
        font-weight: 500;
        color: var(--text-primary);
    }

    .quantity-selector select {
        padding: 10px 15px;
        border: 2px solid var(--border-color);
        border-radius: var(--radius);
        font-size: 16px;
        background: white;
        cursor: pointer;
        transition: var(--transition);
        min-width: 80px;
    }

    .quantity-selector select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .action-buttons .btn {
        flex: 1;
        padding: 12px 20px;
        font-size: 1em;
        font-weight: 600;
        border-radius: var(--radius);
        transition: var(--transition);
        text-align: center;
        position: relative;
        overflow: hidden;
        min-height: 48px;
        white-space: nowrap;
    }

    .action-buttons .btn-primary {
        flex: 1.2;
    }

    .action-buttons .btn-secondary {
        flex: 1.2;
    }

    .action-buttons .btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .action-buttons .btn:hover::before {
        width: 300px;
        height: 300px;
    }

    .action-buttons .btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        border: none;
        box-shadow: var(--shadow);
        position: relative;
        z-index: 1;
    }

    .action-buttons .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .action-buttons .btn-secondary {
        background: linear-gradient(135deg, #64748b 0%, #475569 100%);
        border: none;
        box-shadow: var(--shadow);
        position: relative;
        z-index: 1;
    }

    .action-buttons .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .action-buttons .btn-secondary.registered {
        background: linear-gradient(135deg, #e91e63 0%, #c2185b 100%);
    }

    .action-buttons .btn i {
        margin-right: 8px;
        font-size: 1.2em;
        transition: var(--transition);
    }

    .action-buttons .btn:hover i {
        transform: scale(1.1);
    }

    .action-buttons .btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none !important;
    }

    .action-buttons .btn:disabled::before {
        display: none;
    }

    /* ボタン状態のスタイル */
    .action-buttons .btn.loading {
        opacity: 0.8;
        cursor: not-allowed;
    }

    .action-buttons .btn.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        transform: scale(1.02);
    }

    .action-buttons .btn.error {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    }

    /* ハートアニメーション */
    .btn-secondary .fa-heart {
        transition: var(--transition);
    }

    .btn-secondary.registered .fa-heart {
        animation: heartbeat 1s ease-in-out;
    }

    @keyframes heartbeat {
        0% { transform: scale(1); }
        14% { transform: scale(1.3); }
        28% { transform: scale(1); }
        42% { transform: scale(1.3); }
        70% { transform: scale(1); }
    }

    /* カートバッジアニメーション */
    .cart-bounce {
        animation: cartBounce 0.6s ease-in-out;
    }

    @keyframes cartBounce {
        0%, 20%, 60%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        80% { transform: translateY(-5px); }
    }

    @keyframes cartCountPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); background: #f06292; }
        100% { transform: scale(1); }
    }

    /* お気に入りバッジアニメーション */
    .favorite-bounce {
        animation: favoriteBounce 0.6s ease-in-out;
    }

    @keyframes favoriteBounce {
        0%, 20%, 60%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        80% { transform: translateY(-5px); }
    }

    @keyframes favoriteCountPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); background: #f06292; }
        100% { transform: scale(1); }
    }

    /* お気に入り通知のスタイル */
    .favorite-notification .notification-icon.favorite-icon {
        background: #e91e63;
    }

    /* お気に入りバッジの位置調整（detail.php用） */
    .favorite-link {
        position: relative;
    }

    .favorite-count {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #e91e63;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 600;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(233, 30, 99, 0.4);
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .favorite-count.show {
        opacity: 1;
        transform: scale(1);
    }

    /* 通知システムのスタイル */
    .cart-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: var(--radius-lg);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        border: 1px solid var(--border-color);
        min-width: 320px;
        z-index: 10000;
        transform: translateX(400px);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .cart-notification.show {
        transform: translateX(0);
        opacity: 1;
    }

    .cart-notification.hide {
        transform: translateX(400px);
        opacity: 0;
    }

    .notification-content {
        display: flex;
        align-items: center;
        padding: 15px;
        gap: 12px;
    }

    .notification-icon {
        background: #10b981;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .notification-text {
        flex: 1;
    }

    .notification-title {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .notification-product {
        font-size: 0.9em;
        color: var(--text-secondary);
        margin-bottom: 2px;
    }

    .notification-price {
        font-weight: 600;
        color: var(--primary-color);
        font-size: 0.95em;
    }

    .notification-actions {
        flex-shrink: 0;
    }

    .notification-close {
        background: none;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        padding: 5px;
        border-radius: 50%;
        transition: var(--transition);
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .notification-close:hover {
        background: var(--background-secondary);
        color: var(--text-primary);
    }

    /* 在庫状態のスタイル */
    .stock-status {
        margin: 20px 0;
        padding: 15px;
        border-radius: var(--radius);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .stock-status.in-stock {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .stock-status.low-stock {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .stock-status.out-of-stock {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .stock-status i {
        font-size: 1.2em;
    }

    @media (max-width: 768px) {
        .product-detail-grid {
            grid-template-columns: 1fr;
        }

        .specs-grid {
            grid-template-columns: 1fr;
        }

        .product-images {
            position: static;
        }

        .points-section {
            padding: 15px;
            margin: 15px 0;
        }

        .points-main {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .points-value {
            font-size: 1.5em;
        }

        .action-buttons {
            flex-direction: row;
            gap: 10px;
        }

        .action-buttons .btn {
            padding: 10px 15px;
            font-size: 0.95em;
            min-height: 44px;
        }

        .action-buttons .btn-primary {
            flex: 1.3;
        }

        .action-buttons .btn-secondary {
            flex: 1.3;
        }

        .quantity-selector {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .stock-status {
            padding: 12px;
            font-size: 0.9em;
        }
    }

    @media (max-width: 480px) {
        .points-section {
            padding: 12px;
        }

        .points-value {
            font-size: 1.3em;
        }

        .action-buttons .btn {
            padding: 8px 12px;
            font-size: 0.9em;
            min-height: 44px;
        }

        .action-buttons .btn i {
            font-size: 1em;
            margin-right: 6px;
        }
    }

    /* 商品説明文セクション */
    .product-description-section {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin: 30px 0;
        padding: 30px;
        border: 1px solid #e8ecef;
    }

    .description-title {
        font-size: 1.8em;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 25px 0;
        padding-bottom: 15px;
        border-bottom: 3px solid #3498db;
        position: relative;
    }

    .description-title::after {
        content: '';
        position: absolute;
        bottom: -3px;
        left: 0;
        width: 60px;
        height: 3px;
        background: #e74c3c;
    }

    .description-overview {
        margin-bottom: 25px;
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        color: white;
    }

    .overview-text {
        font-size: 1.2em;
        font-weight: 600;
        margin: 0;
        text-align: center;
        line-height: 1.6;
    }

    .description-detailed {
        margin-bottom: 30px;
    }

    .description-detailed h3 {
        font-size: 1.5em;
        font-weight: 600;
        color: #2c3e50;
        margin: 0 0 20px 0;
        padding-left: 15px;
        border-left: 4px solid #3498db;
    }

    .detailed-content {
        line-height: 1.8;
        color: #34495e;
    }

    .detailed-content h3 {
        font-size: 1.3em;
        font-weight: 600;
        color: #2c3e50;
        margin: 25px 0 15px 0;
        padding: 12px 20px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #3498db;
    }

    .detailed-content h4 {
        font-size: 1.2em;
        font-weight: 600;
        color: #2c3e50;
        margin: 20px 0 10px 0;
        padding: 10px 15px;
        background: #f1f3f4;
        border-radius: 6px;
        border-left: 3px solid #3498db;
    }

    .detailed-content p {
        margin: 15px 0;
        padding: 0 10px;
        text-align: justify;
    }

    .product-link {    
        max-width:15%;
        height: 40px;
        margin-bottom: 1rem;
        display: flex;
        justify-content: center;
        text-align:  center; 
    }
    .product-link span {
        width:auto;
        font-weight:400;
        color: #0056b3;
    }

    /* レスポンシブ対応 */
    @media (max-width: 768px) {
        .product-description-section {
            margin: 20px 0;
            padding: 20px;
        }

        .description-title {
            font-size: 1.5em;
        }

        .overview-text {
            font-size: 1.1em;
        }

        .detailed-content h3 {
            font-size: 1.2em;
            padding: 10px 15px;
        }

        .detailed-content h4 {
            font-size: 1.1em;
            padding: 8px 12px;
        }
    }

    @media (max-width: 480px) {
        .product-description-section {
            padding: 15px;
        }

        .description-title {
            font-size: 1.3em;
        }

        .overview-text {
            font-size: 1em;
        }

        .detailed-content h3 {
            font-size: 1.1em;
            padding: 8px 12px;
        }

        .detailed-content h4 {
            font-size: 1em;
            padding: 6px 10px;
        }
    }
</style>

<script>
    function changeMainImage(id, num) {
        const images = [
            'images/' + '<?= $images[0] ?>' + '.jpg',
            'images/' + '<?= $images[1] ?>' + '.jpg',
            'images/' + '<?= $images[2] ?>' + '.jpg',
            'images/' + '<?= $images[3] ?>' + '.jpg'
        ];
        const mainImg = document.getElementById('mainImage');
        mainImg.src = images[num];
        mainImg.onerror = function() { 
            this.src = 'images/no-image.jpg'; 
        };
    }
</script>

<div class="product-detail-container">
    <a href="product.php" class="nav-link product-link">
        <span>＜ 商品検索画面へ戻る</span>
    </a>
    <hr>
    <div class="product-detail-grid">
        
        <!-- 画像セクション -->
        <div class="product-images">
            <div class="main-image">
                <img id="mainImage" src="images/<?= $images[0] ?>.jpg" alt="<?= h($product['name']) ?>" onerror="this.src='images/no-image.jpg'">
            </div>
            <div class="thumbnail-grid">
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <img onclick="changeMainImage(<?= $product['id'] ?>, <?= $i ?>)"
                        src="images/<?= $images[$i] ?>.jpg"
                        alt="<?= h($product['name']) ?> - 画像<?= $i + 1 ?>"
                        onerror="this.src='images/no-image.jpg'">
                <?php endfor; ?>
            </div>
        </div>

        <!-- 商品情報セクション -->
        <div class="product-info">
            <h1 class="product-title"><?= h($product['name']) ?></h1>
            <div class="product-price">¥<?= number_format($product['price']) ?></div>

            <!-- 獲得ポイント表示 -->
            <div class="points-section">
                <div class="points-main">
                    <div>
                        <div class="points-label">
                            <i class="fas fa-coins points-icon"></i>
                            獲得ポイント
                            <span class="points-percentage"><?= ($percentage * 100) ?>%</span>
                        </div>
                        <div class="points-value">
                            <?= number_format($pointsum, 0) ?> <span style="font-size: 0.8em;">point</span>
                        </div>
                    </div>
                    <button id="toggleButton" class="points-toggle">
                        <i class="fas fa-chevron-down"></i> 内訳表示
                    </button>
                </div>
                
                <div id="toggleElement" class="points-breakdown" style="display: none;">
                    <div class="breakdown-item">
                        <span>基本ポイント (<?= ($base_point_data['campaign_point_rate'] * 100) ?>%)</span>
                        <span><?= floor($product['price'] * $base_point_data['campaign_point_rate']) ?> point</span>
                    </div>
                    <?php if ($campaignrate): ?>
                        <div class="breakdown-item">
                            <span>特別ポイント (<?= ($campaignrate['campaign_point_rate'] * 100) ?>%)</span>
                            <span><?= floor($product['price'] * $campaignrate['campaign_point_rate']) ?> point</span>
                        </div>
                    <?php endif; ?>
                    <div class="breakdown-item">
                        <span>合計獲得ポイント</span>
                        <span><?= number_format($pointsum, 0) ?> point</span>
                    </div>
                </div>
            </div>

            <script>
                const toggleButton = document.getElementById('toggleButton');
                const toggleElement = document.getElementById('toggleElement');

                toggleButton.addEventListener('click', () => {
                    const isHidden = toggleElement.style.display === 'none' || toggleElement.style.display === '';
                    toggleElement.style.display = isHidden ? 'block' : 'none';
                    toggleButton.innerHTML = isHidden ? 
                        '<i class="fas fa-chevron-up"></i> 内訳非表示' : 
                        '<i class="fas fa-chevron-down"></i> 内訳表示';
                });
            </script>

            <div class="product-actions">
                <form action="cart-insert.php" method="post">
                    <div class="quantity-selector">
                        <label for="count">個数：</label>
                        <select name="count" id="count">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="name" value="<?= $product['name'] ?>">
                    <input type="hidden" name="price" value="<?= $product['price'] ?>">
                    <input type="hidden" name="tax" value="<?= $product['tax_id'] ?>">

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary" id="addToCartBtn">
                            <i class="fas fa-cart-plus"></i>
                            <span class="btn-text">カートに追加</span>
                        </button>
                        <a href="favorite-insert.php?id=<?= $product['id'] ?>" class="btn btn-secondary">
                            <i class="fas fa-heart"></i>
                            <span class="btn-text">お気に入りに追加</span>
                        </a>
                    </div>
                </form>
            </div>

            <!-- 在庫状態 -->
            <div class="stock-status <?php 
                if ($product['stock_quantity'] > 10) echo 'in-stock';
                elseif ($product['stock_quantity'] > 0) echo 'low-stock';
                else echo 'out-of-stock';
            ?>">
                <?php if ($product['stock_quantity'] > 10): ?>
                    <i class="fas fa-check-circle"></i>
                    <span>在庫あり（十分な在庫）</span>
                <?php elseif ($product['stock_quantity'] > 0): ?>
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>残りわずか（<?= $product['stock_quantity'] ?>個）</span>
                <?php else: ?>
                    <i class="fas fa-times-circle"></i>
                    <span>在庫切れ</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 商品説明セクション -->
    <?php if ($product_description['overview'] || $product_description['detailed_review']): ?>
    <div class="product-description-section">
        <h2 class="description-title">商品について</h2>
        
        <!-- 概要 -->
        <?php if ($product_description['overview']): ?>
        <div class="description-overview">
            <p class="overview-text"><?= h($product_description['overview']) ?></p>
        </div>
        <?php endif; ?>
        
        <!-- 詳細レビュー -->
        <?php if ($product_description['detailed_review']): ?>
        <div class="description-detailed">
            <h3>詳細レビュー</h3>
            <div class="detailed-content">
                <?php
                // プレーンテキストをHTMLに変換
                $formatted_review = $product_description['detailed_review'];
                // ■で始まる行を見出しに変換
                $formatted_review = preg_replace('/^■\s*(.+)$/m', '<h4>$1</h4>', $formatted_review);
                // 改行を<br>に変換し、段落を<p>タグで囲む
                $formatted_review = nl2br(h($formatted_review));
                // <h4>タグ内の&lt;/&gt;をデコード
                $formatted_review = preg_replace('/&lt;h4&gt;(.+?)&lt;\/h4&gt;/', '<h4>$1</h4>', $formatted_review);
                echo $formatted_review;
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- 技術仕様セクション -->
    <div class="specs-section">
        <h2 class="specs-title">技術仕様</h2>
        <div class="specs-grid">
            <!-- テクニカル -->
            <div class="spec-category">
                <h3>テクニカル</h3>
                
                <?php if ($mcu_name): ?>
                    <div class="spec-item">
                        <span class="spec-label">MCU</span>
                        <span class="spec-value"><?= h($mcu_name) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($sensor_names)): ?>
                    <div class="spec-item">
                        <span class="spec-label">センサー</span>
                        <span class="spec-value"><?= h(implode(', ', $sensor_names)) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($product['dpi_max']): ?>
                    <div class="spec-item">
                        <span class="spec-label">最大解像度</span>
                        <span class="spec-value"><?= number_format($product['dpi_max']) ?> DPI</span>
                    </div>
                <?php endif; ?>

                <?php if ($product['polling_rate']): ?>
                    <div class="spec-item">
                        <span class="spec-label">ポーリングレート</span>
                        <span class="spec-value"><?= number_format($product['polling_rate']) ?> Hz</span>
                    </div>
                <?php endif; ?>

                <?php if ($product['lod_distance_mm']): ?>
                    <div class="spec-item">
                        <span class="spec-label">LOD</span>
                        <span class="spec-value">
                            <?= $product['lod_distance_mm'] ?> mm
                            <?= $product['lod_adjustable'] ? '（調整可能）' : '' ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($product['motion_sync_support']): ?>
                    <div class="spec-item">
                        <span class="spec-label">Motion Sync</span>
                        <span class="spec-value check">
                            <i class="fas fa-check"></i> 対応
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ボタン・スイッチ -->
            <div class="spec-category">
                <h3>ボタン・スイッチ</h3>
                <div class="spec-item">
                    <span class="spec-label">ボタン数</span>
                    <span class="spec-value"><?= $product['button_count'] ?></span>
                </div>

                <?php if (!empty($switch_names)): ?>
                    <div class="spec-item">
                        <span class="spec-label">スイッチ</span>
                        <span class="spec-value"><?= h(implode(', ', $switch_names)) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($product['debounce_time_ms'] !== null): ?>
                    <div class="spec-item">
                        <span class="spec-label">デバウンスタイム</span>
                        <span class="spec-value">
                            <?= $product['debounce_time_ms'] ?> ms
                            <?= $product['debounce_adjustable'] ? '（調整可能）' : '' ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($product['click_delay_ms']): ?>
                    <div class="spec-item">
                        <span class="spec-label">クリック遅延</span>
                        <span class="spec-value"><?= $product['click_delay_ms'] ?> ms</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 接続・バッテリー -->
            <div class="spec-category">
                <h3>接続・バッテリー</h3>

                <?php if (!empty($connections)): ?>
                    <div class="spec-item">
                        <span class="spec-label">接続</span>
                        <span class="spec-value"><?= h(implode(' / ', $connections)) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($product['battery_capacity_mah']): ?>
                    <div class="spec-item">
                        <span class="spec-label">バッテリー容量</span>
                        <span class="spec-value"><?= $product['battery_capacity_mah'] ?> mAh</span>
                    </div>
                <?php endif; ?>

                <?php if ($product['battery_life_hours']): ?>
                    <div class="spec-item">
                        <span class="spec-label">バッテリー持続時間</span>
                        <span class="spec-value"><?= $product['battery_life_hours'] ?> 時間</span>
                    </div>
                <?php endif; ?>

                <?php if ($charging_port): ?>
                    <div class="spec-item">
                        <span class="spec-label">充電端子</span>
                        <span class="spec-value"><?= h($charging_port) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($software_name): ?>
                    <div class="spec-item">
                        <span class="spec-label">ソフトウェア</span>
                        <span class="spec-value"><?= h($software_name) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 寸法 -->
            <div class="spec-category">
                <h3>寸法</h3>

                <?php if ($shape_name): ?>
                    <div class="spec-item">
                        <span class="spec-label">形状</span>
                        <span class="spec-value"><?= h($shape_name) ?></span>
                    </div>
                <?php endif; ?>

                <div class="spec-item">
                    <span class="spec-label">幅</span>
                    <span class="spec-value"><?= $product['width'] ?> mm</span>
                </div>

                <div class="spec-item">
                    <span class="spec-label">全長</span>
                    <span class="spec-value"><?= $product['depth'] ?> mm</span>
                </div>

                <?php if ($product['height']): ?>
                    <div class="spec-item">
                        <span class="spec-label">高さ</span>
                        <span class="spec-value"><?= $product['height'] ?> mm</span>
                    </div>
                <?php endif; ?>

                <div class="spec-item">
                    <span class="spec-label">重量</span>
                    <span class="spec-value"><?= $product['weight'] ?> g</span>
                </div>
            </div>

            <!-- デザイン -->
            <div class="spec-category">
                <h3>デザイン</h3>

                <?php if (!empty($colors)): ?>
                    <div class="spec-item">
                        <span class="spec-label">カラー</span>
                        <span class="spec-value"><?= h(implode(', ', $colors)) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($material_name): ?>
                    <div class="spec-item">
                        <span class="spec-label">素材</span>
                        <span class="spec-value"><?= h($material_name) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($surface_name): ?>
                    <div class="spec-item">
                        <span class="spec-label">表面仕上げ</span>
                        <span class="spec-value"><?= h($surface_name) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($size_category): ?>
                    <div class="spec-item">
                        <span class="spec-label">サイズカテゴリー</span>
                        <span class="spec-value"><?= h($size_category) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- その他 -->
            <div class="spec-category">
                <h3>その他</h3>
                <?php if ($product['cable_length'] > 0): ?>
                    <div class="spec-item">
                        <span class="spec-label">ケーブル長</span>
                        <span class="spec-value"><?= $product['cable_length'] ?> cm</span>
                    </div>
                <?php endif; ?>

                <?php if ($product['for_gift']): ?>
                    <div class="spec-item">
                        <span class="spec-label">ギフト対応</span>
                        <span class="spec-value check">
                            <i class="fas fa-check"></i> 対応
                        </span>
                    </div>
                <?php endif; ?>

                <div class="spec-item">
                    <span class="spec-label">商品番号</span>
                    <span class="spec-value">#<?= str_pad($product['id'], 6, '0', STR_PAD_LEFT) ?></span>
                </div>

                <?php if ($maker_name): ?>
                    <div class="spec-item">
                        <span class="spec-label">メーカー</span>
                        <span class="spec-value"><?= h($maker_name) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- レビューセクション -->
    <div class="reviews-container" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        <?php
        // レビュー投稿ボタン（ログイン時のみ）
        if (isset($_SESSION['customer'])) {
            // 既存レビューの確認
            $review_check = $pdo->prepare('SELECT * FROM review WHERE customer_id = ? AND product_id = ?');
            $review_check->bindParam(1, $_SESSION['customer']['id']);
            $review_check->bindParam(2, $product['id']);
            $review_check->execute();
            $has_reviewed = $review_check->fetch();

            echo '<div style="text-align: right; margin-bottom: 20px;">';
            if ($has_reviewed) {
                echo '<a href="review-add.php?product_id=', $product['id'], '" class="btn btn-outline">';
                echo '<i class="fas fa-edit"></i> レビューを編集';
                echo '</a>';
            } else {
                echo '<a href="review-add.php?product_id=', $product['id'], '" class="btn btn-primary">';
                echo '<i class="fas fa-pen"></i> レビューを書く';
                echo '</a>';
            }
            echo '</div>';
        }

        // レビュー一覧の表示
        displayReviews($product['id'], $pdo);
        ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isLoggedIn = <?= isset($_SESSION['customer']) ? 'true' : 'false' ?>;

    // カートボタンの成功フィードバック（product.phpと同じアニメーション）
    const cartForm = document.querySelector('form[action="cart-insert.php"]');
    const addToCartBtn = document.getElementById('addToCartBtn');
    
    // 現在のカートアイテム数を取得
    let cartItemCount = <?= isset($_SESSION['product']) ? count($_SESSION['product']) : 0 ?>;
    
    // 現在のお気に入り数を取得
    let favoriteItemCount = <?php 
        if (isset($_SESSION['customer'])) {
            $fav_count_sql = $pdo->prepare('SELECT COUNT(*) as count FROM favorite WHERE customer_id = ?');
            $fav_count_sql->bindParam(1, $_SESSION['customer']['id']);
            $fav_count_sql->execute();
            echo $fav_count_sql->fetch()['count'];
        } else {
            echo '0';
        }
    ?>;
    
    if (cartForm && addToCartBtn) {
        cartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // 商品情報を取得
            const productName = document.querySelector('.product-title').textContent;
            const productPrice = document.querySelector('.product-price').textContent;
            const productImage = document.querySelector('#mainImage');
            
            // ボタンを無効化
            addToCartBtn.disabled = true;
            const originalContent = addToCartBtn.innerHTML;
            addToCartBtn.classList.add('loading');
            addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span class="btn-text">追加中...</span>';
            
            // フォームデータを送信
            const formData = new FormData(cartForm);
            fetch('cart-insert.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok || response.redirected) {
                    // カート数を更新
                    cartItemCount++;
                    updateCartBadge(cartItemCount);
                    animateCartBadge();

                    // 成功フィードバック
                    addToCartBtn.classList.add('success');
                    addToCartBtn.innerHTML = '<i class="fas fa-check"></i> <span class="btn-text">追加完了！</span>';

                    // カートアニメーション
                    createCartAnimation(productImage, addToCartBtn);

                    // 通知表示
                    showAddToCartNotification(productName, productPrice);

                    setTimeout(() => {
                        addToCartBtn.classList.remove('success', 'loading');
                        addToCartBtn.innerHTML = originalContent;
                        addToCartBtn.disabled = false;
                    }, 2000);
                } else {
                    throw new Error('カートへの追加に失敗しました');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                addToCartBtn.classList.add('error');
                addToCartBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <span class="btn-text">エラー</span>';
                
                setTimeout(() => {
                    addToCartBtn.classList.remove('error', 'loading');
                    addToCartBtn.innerHTML = originalContent;
                    addToCartBtn.disabled = false;
                }, 2000);
            });
        });
    }

    // お気に入りボタンの処理
    const favoriteLink = document.querySelector('a[href^="favorite-insert.php"]');
    if (favoriteLink && isLoggedIn) {
        const productId = favoriteLink.href.match(/id=(\d+)/)[1];

        // 初期状態をチェック
        fetch('favorite-insert.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `id=${productId}&action=check`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.is_favorite) {
                    favoriteLink.innerHTML = '<i class="fas fa-heart"></i> <span class="btn-text">お気に入り登録済み</span>';
                    favoriteLink.classList.add('registered');
                }
            })
            .catch(error => console.error('Error:', error));

        // クリックイベントをAjax化
        favoriteLink.onclick = function(e) {
            e.preventDefault();
            
            // アニメーション効果
            const icon = this.querySelector('i');
            const text = this.querySelector('.btn-text');
            icon.classList.add('fa-spin');
            
            fetch('favorite-insert.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `id=${productId}&action=toggle`
                })
                .then(response => response.json())
                .then(data => {
                    icon.classList.remove('fa-spin');
                    if (data.success) {
                        if (data.is_favorite) {
                            // お気に入りに追加された場合
                            favoriteItemCount++;
                            this.innerHTML = '<i class="fas fa-heart"></i> <span class="btn-text">お気に入り登録済み</span>';
                            this.classList.add('registered');
                            
                            // お気に入り数を更新してアニメーション
                            updateFavoriteBadge(favoriteItemCount);
                            animateFavoriteBadge();
                            
                            // 通知表示
                            const productName = document.querySelector('.product-title').textContent;
                            showAddToFavoriteNotification(productName);
                        } else {
                            // お気に入りから削除された場合
                            favoriteItemCount--;
                            this.innerHTML = '<i class="fas fa-heart"></i> <span class="btn-text">お気に入りに追加</span>';
                            this.classList.remove('registered');
                            
                            // お気に入り数を更新
                            updateFavoriteBadge(favoriteItemCount);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    icon.classList.remove('fa-spin');
                });
        };
    }
});

// カートバッジ更新関数
function updateCartBadge(count) {
    const cartBadge = document.querySelector('.cart-count');
    if (cartBadge) {
        cartBadge.textContent = count > 99 ? '99+' : count;
        if (count > 0) {
            cartBadge.classList.add('show');
            cartBadge.style.display = 'inline-block';
        } else {
            cartBadge.classList.remove('show');
            cartBadge.style.display = 'none';
        }
    } else if (count > 0) {
        // バッジが存在しない場合は新規作成
        const cartLink = document.querySelector('.cart-link');
        if (cartLink) {
            const newBadge = document.createElement('span');
            newBadge.className = 'cart-count show';
            newBadge.textContent = count > 99 ? '99+' : count;
            cartLink.appendChild(newBadge);
        }
    }
}

// お気に入りバッジ更新関数
function updateFavoriteBadge(count) {
    const favoriteBadge = document.querySelector('.favorite-count');
    if (favoriteBadge) {
        favoriteBadge.textContent = count > 99 ? '99+' : count;
        if (count > 0) {
            favoriteBadge.classList.add('show');
            favoriteBadge.style.display = 'inline-block';
        } else {
            favoriteBadge.classList.remove('show');
            favoriteBadge.style.display = 'none';
        }
    } else if (count > 0) {
        // バッジが存在しない場合は新規作成
        const favoriteLink = document.querySelector('.favorite-link');
        if (favoriteLink) {
            // まず親要素にrelativeを設定
            favoriteLink.style.position = 'relative';
            
            const newBadge = document.createElement('span');
            newBadge.textContent = count > 99 ? '99+' : count;
            
            // 全てのスタイルを明示的に設定（CSSに依存しない）
            Object.assign(newBadge.style, {
                position: 'absolute',
                top: '-10px',
                right: '-15px',
                background: '#e91e63',
                color: 'white',
                borderRadius: '50%',
                width: '20px',
                height: '20px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '0.7rem',
                fontWeight: '600',
                border: '2px solid white',
                boxShadow: '0 2px 4px rgba(233, 30, 99, 0.4)',
                transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                opacity: '0',
                transform: 'scale(0)',
                zIndex: '10'
            });
            
            favoriteLink.appendChild(newBadge);
            
            // DOM追加後、アニメーション開始
            requestAnimationFrame(() => {
                newBadge.style.opacity = '1';
                newBadge.style.transform = 'scale(1)';
            });
        }
    }
}

// お気に入りバッジアニメーション
function animateFavoriteBadge() {
    const favoriteIcon = document.querySelector('.favorite-link');
    const favoriteBadge = document.querySelector('.favorite-count');
    
    if (favoriteIcon) {
        favoriteIcon.classList.add('favorite-bounce');
        setTimeout(() => {
            favoriteIcon.classList.remove('favorite-bounce');
        }, 600);
    }
    
    // バッジ自体にもパルスアニメーション
    if (favoriteBadge) {
        favoriteBadge.style.animation = 'favoriteCountPulse 0.6s ease-in-out';
        setTimeout(() => {
            favoriteBadge.style.animation = '';
        }, 600);
    }
}

// カートバッジアニメーション
function animateCartBadge() {
    const cartIcon = document.querySelector('.cart-link');
    const cartBadge = document.querySelector('.cart-count');
    
    if (cartIcon) {
        cartIcon.classList.add('cart-bounce');
        setTimeout(() => {
            cartIcon.classList.remove('cart-bounce');
        }, 600);
    }
    
    // バッジ自体にもパルスアニメーション
    if (cartBadge) {
        cartBadge.style.animation = 'cartCountPulse 0.6s ease-in-out';
        setTimeout(() => {
            cartBadge.style.animation = '';
        }, 600);
    }
}

// カートアニメーション（商品画像がカートに飛んでいく効果）
function createCartAnimation(productImg, button) {
    const cartIcon = document.querySelector('.cart-link, .cart-icon, [href*="cart"]');

    if (!productImg || !cartIcon) return;

    // 商品画像をクローン
    const flyingImg = productImg.cloneNode(true);
    flyingImg.style.position = 'fixed';
    flyingImg.style.width = '60px';
    flyingImg.style.height = '60px';
    flyingImg.style.zIndex = '9999';
    flyingImg.style.pointerEvents = 'none';
    flyingImg.style.borderRadius = '8px';
    flyingImg.style.transition = 'all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
    flyingImg.style.boxShadow = '0 4px 20px rgba(0,0,0,0.3)';

    // 開始位置を設定
    const imgRect = productImg.getBoundingClientRect();
    flyingImg.style.left = imgRect.left + 'px';
    flyingImg.style.top = imgRect.top + 'px';

    document.body.appendChild(flyingImg);

    // カートアイコンの位置を取得
    const cartRect = cartIcon.getBoundingClientRect();

    // アニメーション開始
    setTimeout(() => {
        flyingImg.style.left = (cartRect.left + cartRect.width / 2 - 30) + 'px';
        flyingImg.style.top = (cartRect.top + cartRect.height / 2 - 30) + 'px';
        flyingImg.style.transform = 'scale(0.3) rotate(360deg)';
        flyingImg.style.opacity = '0.8';
    }, 50);

    // カートアイコンを揺らす
    cartIcon.classList.add('cart-bounce');
    setTimeout(() => {
        cartIcon.classList.remove('cart-bounce');
    }, 1000);

    // アニメーション終了後にクリーンアップ
    setTimeout(() => {
        if (flyingImg.parentNode) {
            flyingImg.remove();
        }
    }, 850);
}

// 追加完了通知
function showAddToCartNotification(productName, productPrice) {
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="notification-text">
                <div class="notification-title">カートに追加しました</div>
                <div class="notification-product">${productName}</div>
                <div class="notification-price">${productPrice}</div>
            </div>
            <div class="notification-actions">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(notification);

    // アニメーションで表示
    setTimeout(() => notification.classList.add('show'), 10);

    // 3秒後に自動消去
    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 3000);
}

// お気に入り追加通知
function showAddToFavoriteNotification(productName) {
    const notification = document.createElement('div');
    notification.className = 'cart-notification favorite-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon favorite-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="notification-text">
                <div class="notification-title">お気に入りに追加しました</div>
                <div class="notification-product">${productName}</div>
            </div>
            <div class="notification-actions">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(notification);

    // アニメーションで表示
    setTimeout(() => notification.classList.add('show'), 10);

    // 3秒後に自動消去
    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 3000);
}
</script>

<?php require 'footer.php'; ?>