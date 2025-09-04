<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>
<?php require 'menu.php'; ?>

<?php
if (!isset($_SESSION['customer'])) {
    echo '<div class="login-required-container">';
    echo '<div class="login-card">';
    echo '<div class="login-icon"><i class="fas fa-user-lock"></i></div>';
    echo '<h2>ログインが必要です</h2>';
    echo '<p>レビューを投稿するには、まずログインしてください。</p>';
    echo '<div class="login-actions">';
    echo '<a href="login-input.php" class="btn btn-primary">ログイン</a>';
    echo '<a href="customer-input.php" class="btn btn-secondary">新規登録</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    require 'footer.php';
    exit;
}

$product_id = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;

if ($product_id <= 0) {
    echo '<p>商品が見つかりません。</p>';
    require 'footer.php';
    exit;
}

// 商品情報を取得
$sql = $pdo->prepare('SELECT * FROM product WHERE id = ?');
$sql->bindParam(1, $product_id, PDO::PARAM_INT);
$sql->execute();
$product = $sql->fetch();

if (!$product) {
    echo '<p>商品が見つかりません。</p>';
    require 'footer.php';
    exit;
}

// 既存のレビューを確認
$check_sql = $pdo->prepare('SELECT * FROM review WHERE customer_id = ? AND product_id = ?');
$check_sql->bindParam(1, $_SESSION['customer']['id'], PDO::PARAM_INT);
$check_sql->bindParam(2, $product_id, PDO::PARAM_INT);
$check_sql->execute();
$existing_review = $check_sql->fetch();
?>

<style>
    .login-required-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 60vh;
        padding: 20px;
    }

    .login-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        padding: 40px;
        text-align: center;
        max-width: 400px;
        width: 100%;
    }

    .login-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }

    .login-icon i {
        font-size: 40px;
        color: white;
    }

    .login-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .review-form-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .review-form-container h2 {
        font-size: 2em;
        margin-bottom: 30px;
        color: #333;
        text-align: center;
    }

    .product-info-review {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 12px;
        margin-bottom: 30px;
    }

    .product-image-small {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
    }

    .alert-info {
        background: #d1ecf1;
        border: 1px solid #bee5eb;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        color: #0c5460;
    }

    .alert-info i {
        margin-right: 8px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #333;
    }

    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 5px;
        margin-bottom: 10px;
    }

    .star-rating input {
        display: none;
    }

    .star-rating label {
        font-size: 2em;
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
    }

    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label {
        color: #ffc107;
    }

    .review-textarea {
        width: 100%;
        min-height: 150px;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-family: inherit;
        font-size: 16px;
        resize: vertical;
        transition: border-color 0.3s;
    }

    .review-textarea:focus {
        outline: none;
        border-color: #007bff;
    }

    .submit-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 30px;
    }

    .btn {
        padding: 12px 30px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 16px;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
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
        transform: translateY(-2px);
    }

    .btn-outline {
        background: transparent;
        border: 2px solid #6c757d;
        color: #6c757d;
    }

    .btn-outline:hover {
        background: #6c757d;
        color: white;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #545b62;
    }

    @media (max-width: 768px) {
        .product-info-review {
            flex-direction: column;
            text-align: center;
        }

        .submit-buttons {
            flex-direction: column;
            align-items: center;
        }

        .btn {
            width: 100%;
            max-width: 280px;
            justify-content: center;
        }
    }
</style>

<div class="review-form-container">
    <h2><?= $existing_review ? 'レビューを編集' : 'レビューを投稿' ?></h2>

    <div class="product-info-review">
        <?php
        $images = getImageFromProductData($product);
        ?>
        <img src="images/<?= $images[0] ?>.jpg" alt="<?= h($product['name']) ?>" class="product-image-small" onerror="this.src='images/no-image.jpg'">
        <div>
            <h3><?= h($product['name']) ?></h3>
            <p>価格: ¥<?= number_format($product['price']) ?></p>
        </div>
    </div>

    <?php if ($existing_review): ?>
        <div class="alert-info">
            <i class="fas fa-info-circle"></i> 既にこの商品のレビューを投稿済みです。内容を更新できます。
        </div>
    <?php endif; ?>

    <form action="review-save.php" method="post">
        <input type="hidden" name="product_id" value="<?= $product_id ?>">

        <div class="form-group">
            <label class="form-label">評価</label>
            <div class="star-rating">
                <input type="radio" name="rating" value="5" id="star5" <?= $existing_review && $existing_review['rating'] == 5 ? 'checked' : '' ?> required>
                <label for="star5"><i class="fas fa-star"></i></label>

                <input type="radio" name="rating" value="4" id="star4" <?= $existing_review && $existing_review['rating'] == 4 ? 'checked' : '' ?>>
                <label for="star4"><i class="fas fa-star"></i></label>

                <input type="radio" name="rating" value="3" id="star3" <?= $existing_review && $existing_review['rating'] == 3 ? 'checked' : (!$existing_review ? 'checked' : '') ?>>
                <label for="star3"><i class="fas fa-star"></i></label>

                <input type="radio" name="rating" value="2" id="star2" <?= $existing_review && $existing_review['rating'] == 2 ? 'checked' : '' ?>>
                <label for="star2"><i class="fas fa-star"></i></label>

                <input type="radio" name="rating" value="1" id="star1" <?= $existing_review && $existing_review['rating'] == 1 ? 'checked' : '' ?>>
                <label for="star1"><i class="fas fa-star"></i></label>
            </div>
        </div>

        <div class="form-group">
            <label for="comment" class="form-label">レビューコメント</label>
            <textarea name="comment" id="comment" class="review-textarea" placeholder="使用感や良かった点、気になった点などをお書きください..." required><?= $existing_review ? h($existing_review['comment']) : '' ?></textarea>
        </div>

        <div class="submit-buttons">
            <a href="detail.php?id=<?= $product_id ?>" class="btn btn-outline">キャンセル</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i>
                <?= $existing_review ? 'レビューを更新' : 'レビューを投稿' ?>
            </button>
        </div>
    </form>
</div>

<?php require 'footer.php'; ?>