<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>
<?php

//商品ID
if (!isset($_SESSION['product'])) {
    $_SESSION['product'] = [];
}

// 更新モードかどうかをチェック
$isUpdateMode = isset($_REQUEST['update_mode']) && $_REQUEST['update_mode'] == '1';

if ($isUpdateMode) {
    // 数量更新モード：既存の商品の数量を更新
    $product_id = $_REQUEST['id'];
    $col = $_REQUEST['col'];
    $new_count = (int)$_REQUEST['count'];

    // 在庫チェック
    $stock_sql = $pdo->prepare('SELECT stock_quantity FROM product WHERE id = ?');
    $stock_sql->bindParam(1, $product_id, PDO::PARAM_INT);
    $stock_sql->execute();
    $stock_info = $stock_sql->fetch();

    if (!$stock_info) {
        echo '<div class="alert alert-error">';
        echo '<i class="fas fa-exclamation-triangle"></i>';
        echo '<p>商品情報が見つかりません。</p>';
        echo '</div>';
        echo '<a href="cart-show.php" class="btn btn-primary">カートに戻る</a>';
        require 'footer.php';
        exit;
    }

    if ($new_count > $stock_info['stock_quantity']) {
        echo '<div class="alert alert-error">';
        echo '<i class="fas fa-exclamation-triangle"></i>';
        echo '<p>在庫が不足しています。在庫数: ', $stock_info['stock_quantity'], '個</p>';
        echo '</div>';
        echo '<a href="cart-show.php" class="btn btn-primary">カートに戻る</a>';
        require 'footer.php';
        exit;
    }

    // 該当商品を見つけて数量更新
    $updated = false;
    foreach ($_SESSION['product'] as $key => $item) {
        if ((int)$item['col'] === (int)$col) {
            $_SESSION['product'][$key]['count'] = $new_count;
            $updated = true;
            break;
        }
    }

    if ($updated) {
        echo '<div class="cart-update-success">';
        echo '<div class="success-icon"><i class="fas fa-check-circle"></i></div>';
        echo '<h2>数量を更新しました</h2>';
        echo '<p>「', h($_REQUEST['name']), '」の数量を ', $new_count, '個に変更しました。</p>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-error">';
        echo '<i class="fas fa-exclamation-triangle"></i>';
        echo '<p>商品がカートに存在しません。</p>';
        echo '</div>';
    }
} else {
    // 通常の追加モード

    //cart-insert回数のカウント
    if (!isset($_SESSION['inscount'])) {
        $_SESSION['inscount'] = 0;
    } else {
        $_SESSION['inscount']++;
    }

    // 在庫チェック
    $product_id = $_REQUEST['id'];
    $add_count = (int)$_REQUEST['count'];

    $stock_sql = $pdo->prepare('SELECT stock_quantity FROM product WHERE id = ?');
    $stock_sql->bindParam(1, $product_id, PDO::PARAM_INT);
    $stock_sql->execute();
    $stock_info = $stock_sql->fetch();

    if (!$stock_info) {
        echo '<div class="alert alert-error">';
        echo '<i class="fas fa-exclamation-triangle"></i>';
        echo '<p>商品情報が見つかりません。</p>';
        echo '</div>';
        echo '<a href="product.php" class="btn btn-primary">商品一覧に戻る</a>';
        require 'footer.php';
        exit;
    }

    // 既にカートにある場合の合計数をチェック
    $current_count = 0;
    foreach ($_SESSION['product'] as $item) {
        if ($item['id'] == $product_id) {
            $current_count += $item['count'];
        }
    }

    $total_count = $current_count + $add_count;

    if ($total_count > $stock_info['stock_quantity']) {
        echo '<div class="alert alert-error">';
        echo '<i class="fas fa-exclamation-triangle"></i>';
        echo '<p>在庫が不足しています。</p>';
        echo '<p>在庫数: ', $stock_info['stock_quantity'], '個 / カート内: ', $current_count, '個</p>';
        echo '<p>追加可能数: ', max(0, $stock_info['stock_quantity'] - $current_count), '個</p>';
        echo '</div>';
        echo '<a href="product.php" class="btn btn-primary">商品一覧に戻る</a>';
        require 'footer.php';
        exit;
    }

    $_SESSION['product'][$_SESSION['inscount']] = [
        'id' => $_REQUEST['id'],
        'name' => $_REQUEST['name'],
        'price' => $_REQUEST['price'],
        'count' => $_REQUEST['count'],
        'tax' => $_REQUEST['tax'],
        'col' => $_SESSION['inscount'],
    ];

    echo '<div class="cart-add-success">';
    echo '<div class="success-icon"><i class="fas fa-check-circle"></i></div>';
    echo '<h2>カートに商品を追加しました</h2>';
    echo '<div class="added-product">';
    echo '<p class="product-name">「', h($_REQUEST['name']), '」</p>';
    echo '<p class="product-details">価格: ¥', number_format($_REQUEST['price']), ' × ', $_REQUEST['count'], '個</p>';
    echo '</div>';
    echo '</div>';
}

echo '<div class="cart-actions-after">';
echo '<a href="cart-show.php" class="btn btn-primary">';
echo '<i class="fas fa-shopping-cart"></i>';
echo 'カートを確認する';
echo '</a>';
echo '<a href="product.php" class="btn btn-outline">';
echo '<i class="fas fa-arrow-left"></i>';
echo '買い物を続ける';
echo '</a>';
echo '</div>';

echo '<hr>';
echo '<div class="current-cart">';
echo '<h3><i class="fas fa-list"></i> 現在のカート内容</h3>';
require 'cart.php';
echo '</div>';
?>

<style>
    .cart-add-success,
    .cart-update-success {
        text-align: center;
        padding: 40px 20px;
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        border-radius: 12px;
        margin: 20px 0;
        border: 1px solid #10b981;
    }

    .success-icon {
        font-size: 3rem;
        color: #10b981;
        margin-bottom: 20px;
    }

    .cart-add-success h2,
    .cart-update-success h2 {
        color: #065f46;
        margin-bottom: 15px;
        font-size: 1.5rem;
    }

    .added-product {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .product-name {
        font-size: 1.2rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 8px;
    }

    .product-details {
        color: #6b7280;
        margin: 0;
    }

    .cart-actions-after {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin: 30px 0;
    }

    .current-cart {
        margin-top: 30px;
        padding: 20px;
        background: #f9fafb;
        border-radius: 8px;
    }

    .current-cart h3 {
        color: #1f2937;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert {
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-error {
        background: #fef2f2;
        border: 1px solid #fca5a5;
        color: #991b1b;
    }

    .alert i {
        font-size: 1.2rem;
    }

    @media (max-width: 768px) {
        .cart-actions-after {
            flex-direction: column;
            align-items: center;
        }

        .cart-add-success,
        .cart-update-success {
            padding: 30px 15px;
        }
    }
</style>

<?php require 'footer.php'; ?>