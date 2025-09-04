<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>

<?php
// 削除対象のカートキーをチェック
if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
    header('Location: cart-show.php');
    exit;
}

$cart_key = (int)$_REQUEST['id']; // これはcart-insert.phpのinscountキー
$deleted_product = null;
$deleted_quantity = 0;

// カートから該当商品を削除
if (isset($_SESSION['product'][$cart_key])) {
    $item = $_SESSION['product'][$cart_key];
    $product_id = (int)$item['id']; // 実際の商品ID
    
    // 商品情報をDBから取得
    $sql = $pdo->prepare('SELECT * FROM product WHERE id = ?');
    $sql->bindParam(1, $product_id);
    $sql->execute();
    $deleted_product = $sql->fetch();
    $deleted_quantity = isset($item['count']) ? (int)$item['count'] : 1;
    
    // カートから削除
    unset($_SESSION['product'][$cart_key]);
    $success = true;
} else {
    $success = false;
}

// 残りのカート商品数を計算
$remaining_count = isset($_SESSION['product']) ? count($_SESSION['product']) : 0;
?>

<div class="cart-delete-container">
    <?php if ($success && $deleted_product): ?>
        <!-- 削除成功 -->
        <div class="delete-result success">
            <div class="result-header">
                <div class="result-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="result-title">カートから削除しました</h1>
                <p class="result-subtitle">商品がカートから正常に削除されました</p>
            </div>

            <div class="deleted-item-info">
                <h2 class="section-title">削除された商品</h2>
                <div class="deleted-product-card">
                    <div class="product-image">
                        <img src="images/<?= h($deleted_product['image_name1']).'.jpg'  ?>" 
                             alt="<?= h($deleted_product['name']) ?>">
                    </div>
                    <div class="product-details">
                        <h3 class="product-name"><?= h($deleted_product['name']) ?></h3>
                        <div class="product-specs">
                            <?php if (isset($deleted_product['wireless']) && $deleted_product['wireless']): ?>
                                <span class="spec-tag wireless">
                                    <i class="fas fa-wifi"></i>
                                    ワイヤレス
                                </span>
                            <?php else: ?>
                                <span class="spec-tag wired">
                                    <i class="fas fa-plug"></i>
                                    有線
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="product-price">
                            <span class="unit-price">¥<?= number_format($deleted_product['price']) ?></span>
                            <span class="quantity">× <?= $deleted_quantity ?>個</span>
                        </div>
                        <div class="total-price">
                            小計: ¥<?= number_format($deleted_product['price'] * $deleted_quantity) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cart-status">
                <div class="status-card">
                    <div class="status-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="status-info">
                        <?php if ($remaining_count > 0): ?>
                            <h3>カートの状況</h3>
                            <p>残り <strong><?= $remaining_count ?>点</strong> の商品がカートに入っています</p>
                        <?php else: ?>
                            <h3>カートが空になりました</h3>
                            <p>カートに商品がありません</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <?php if ($remaining_count > 0): ?>
                    <a href="cart-show.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i>
                        カートを確認する
                    </a>
                    <a href="purchase-input.php" class="btn btn-success">
                        <i class="fas fa-credit-card"></i>
                        購入手続きに進む
                    </a>
                <?php else: ?>
                    <a href="product.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i>
                        商品を探す
                    </a>
                <?php endif; ?>
                <a href="product.php" class="btn btn-outline">
                    <i class="fas fa-plus"></i>
                    他の商品を見る
                </a>
            </div>

            <?php if ($remaining_count > 0): ?>
                <!-- 残りのカート内容表示 -->
                <div class="remaining-cart">
                    <h2 class="section-title">カートの残り商品</h2>
                    <div class="cart-preview">
                        <?php
                        $preview_count = 0;
                        if (isset($_SESSION['product']) && is_array($_SESSION['product'])) {
                            foreach ($_SESSION['product'] as $key => $item):
                                if ($preview_count >= 3) break; // 最大3件まで表示
                                
                                if (!isset($item['id']) || !isset($item['count'])) continue;
                                
                                $qty = (int)$item['count'];
                                $item_id = (int)$item['id'];
                                
                                $sql = $pdo->prepare('SELECT * FROM product WHERE id = ?');
                                $sql->bindParam(1, $item_id);
                                $sql->execute();
                                $product = $sql->fetch();
                                
                                if ($product):
                                    $preview_count++;
                                ?>
                            <div class="cart-preview-item">
                                <div class="preview-image">
                                    <img src="images/<?= h($product['image_name1']).'.jpg'  ?>" 
                                         alt="<?= h($product['name']) ?>">
                                </div>
                                <div class="preview-info">
                                    <h4><?= h($product['name']) ?></h4>
                                    <span>¥<?= number_format($product['price']) ?> × <?= $qty ?>個</span>
                                </div>
                            </div>
                        <?php 
                                endif;
                            endforeach;
                        }
                        
                        if ($remaining_count > 3):
                        ?>
                            <div class="more-items">
                                <span>他 <?= $remaining_count - 3 ?>点</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- 削除失敗またはエラー -->
        <div class="delete-result error">
            <div class="result-header">
                <div class="result-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1 class="result-title">削除に失敗しました</h1>
                <p class="result-subtitle">指定された商品が見つからないか、既に削除されています</p>
            </div>

            <div class="error-info">
                <div class="error-message">
                    <h3>エラーの原因</h3>
                    <ul>
                        <li>商品が既にカートから削除されている</li>
                        <li>無効な商品IDが指定された</li>
                        <li>セッションが切れている可能性</li>
                    </ul>
                </div>
            </div>

            <div class="action-buttons">
                <a href="cart-show.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i>
                    カートを確認
                </a>
                <a href="product.php" class="btn btn-outline">
                    <i class="fas fa-shopping-bag"></i>
                    商品一覧に戻る
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.cart-delete-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    min-height: calc(100vh - 200px);
}

.delete-result {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.result-header {
    text-align: center;
    padding: 40px 30px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border-bottom: 1px solid #f3f4f6;
}

.delete-result.error .result-header {
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
}

.result-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 2.5rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
}

.delete-result.error .result-icon {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
}

.result-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 10px;
}

.result-subtitle {
    color: #6b7280;
    font-size: 1.1rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 20px;
    padding-left: 15px;
    border-left: 4px solid #2563eb;
}

.deleted-item-info {
    padding: 30px;
    border-bottom: 1px solid #f3f4f6;
}

.deleted-product-card {
    display: flex;
    gap: 20px;
    padding: 25px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.deleted-product-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.product-image {
    width: 120px;
    height: 120px;
    flex-shrink: 0;
    background: white;
    border-radius: 8px;
    padding: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.product-details {
    flex: 1;
}

.product-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 10px;
}

.product-specs {
    margin-bottom: 15px;
}

.spec-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
}

.spec-tag.wireless {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

.spec-tag.wired {
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.product-price {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
}

.unit-price {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
}

.quantity {
    color: #6b7280;
}

.total-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2563eb;
}

.cart-status {
    padding: 30px;
    border-bottom: 1px solid #f3f4f6;
}

.status-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 12px;
}

.status-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.status-info h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 5px;
}

.status-info p {
    color: #6b7280;
}

.remaining-cart {
    padding: 30px;
    border-bottom: 1px solid #f3f4f6;
}

.cart-preview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.cart-preview-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

.preview-image {
    width: 50px;
    height: 50px;
    flex-shrink: 0;
    background: white;
    border-radius: 6px;
    padding: 5px;
}

.preview-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.preview-info h4 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 3px;
    line-height: 1.3;
}

.preview-info span {
    font-size: 0.8rem;
    color: #6b7280;
}

.more-items {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
    background: #f3f4f6;
    border: 1px dashed #d1d5db;
    border-radius: 8px;
    color: #6b7280;
    font-size: 0.9rem;
}

.error-info {
    padding: 30px;
    border-bottom: 1px solid #f3f4f6;
}

.error-message {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 20px;
}

.error-message h3 {
    color: #991b1b;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 15px;
}

.error-message ul {
    color: #7f1d1d;
    margin-left: 20px;
}

.error-message li {
    margin-bottom: 5px;
    line-height: 1.5;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    padding: 30px;
    background: #f8fafc;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
}

.btn-primary {
    background: #2563eb;
    color: white;
}

.btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-outline {
    background: white;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
    color: #374151;
}

/* レスポンシブ */
@media (max-width: 768px) {
    .cart-delete-container {
        padding: 15px;
    }
    
    .deleted-product-card {
        flex-direction: column;
        text-align: center;
    }
    
    .product-image {
        width: 150px;
        height: 150px;
        align-self: center;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: stretch;
    }
    
    .cart-preview {
        grid-template-columns: 1fr;
    }
    
    .status-card {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .result-title {
        font-size: 1.5rem;
    }
    
    .product-price {
        flex-direction: column;
        gap: 5px;
    }
    
    .btn {
        justify-content: center;
        padding: 15px 20px;
    }
}
</style>

<?php require 'footer.php'; ?>