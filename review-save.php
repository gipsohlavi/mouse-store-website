<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>


<style>
    .review-save-container {
        max-width: 600px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .result-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        text-align: center;
    }

    .success-header {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 40px 30px;
        position: relative;
    }

    .success-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: repeating-linear-gradient(
            45deg,
            transparent,
            transparent 2px,
            rgba(255,255,255,0.05) 2px,
            rgba(255,255,255,0.05) 4px
        );
        animation: shimmer 3s linear infinite;
    }

    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .success-icon {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        position: relative;
        z-index: 1;
    }

    .success-icon i {
        font-size: 40px;
        color: white;
        animation: bounceIn 0.6s ease-out;
    }

    @keyframes bounceIn {
        0% {
            opacity: 0;
            transform: scale(0.3);
        }
        50% {
            opacity: 1;
            transform: scale(1.05);
        }
        70% {
            transform: scale(0.9);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }

    .success-title {
        font-size: 2em;
        font-weight: bold;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }

    .success-message {
        font-size: 1.1em;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }

    .error-header {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 40px 30px;
    }

    .error-icon {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }

    .error-icon i {
        font-size: 40px;
        color: white;
    }

    .result-content {
        padding: 40px 30px;
    }

    .review-summary {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
        text-align: left;
    }

    .review-summary h4 {
        color: #333;
        margin-bottom: 15px;
        font-size: 1.2em;
    }

    .rating-display {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .stars {
        color: #ffc107;
        font-size: 1.5em;
        margin-right: 10px;
    }

    .rating-text {
        color: #666;
        font-weight: 500;
    }

    .comment-preview {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        color: #333;
        line-height: 1.6;
        max-height: 120px;
        overflow-y: auto;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn {
        padding: 15px 30px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        font-size: 16px;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        position: relative;
        overflow: hidden;
    }

    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.2);
        transition: left 0.5s;
    }

    .btn:hover::before {
        left: 100%;
    }

    .btn-primary {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.4);
    }

    .btn-secondary {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
    }

    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
    }

    .login-required {
        text-align: center;
        padding: 60px 20px;
    }

    .login-icon {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
    }

    .login-icon i {
        font-size: 50px;
        color: white;
    }

    .login-message {
        font-size: 1.3em;
        color: #333;
        margin-bottom: 30px;
    }

    .progress-bar {
        width: 100%;
        height: 4px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 2px;
        overflow: hidden;
        margin-top: 20px;
    }

    .progress-fill {
        height: 100%;
        background: rgba(255, 255, 255, 0.6);
        width: 0%;
        border-radius: 2px;
        animation: progress 3s ease-out forwards;
    }

    @keyframes progress {
        to { width: 100%; }
    }

    .floating-elements {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .floating-star {
        position: absolute;
        color: rgba(255, 255, 255, 0.3);
        animation: float 6s ease-in-out infinite;
    }

    .floating-star:nth-child(1) { top: 20%; left: 10%; animation-delay: -1s; }
    .floating-star:nth-child(2) { top: 60%; left: 80%; animation-delay: -3s; }
    .floating-star:nth-child(3) { top: 80%; left: 20%; animation-delay: -5s; }

    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }

    @media (max-width: 768px) {
        .review-save-container {
            margin: 30px auto;
        }

        .action-buttons {
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

<?php
if (!isset($_SESSION['customer'])) {
?>
    <div class="review-save-container">
        <div class="result-card">
            <div class="login-required">
                <div class="login-icon">
                    <i class="fas fa-user-lock"></i>
                </div>
                <div class="login-message">
                    レビューを投稿するには<br>ログインが必要です
                </div>
                <div class="action-buttons">
                    <a href="login-input.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        ログイン
                    </a>
                    <a href="customer-input.php" class="btn btn-secondary">
                        <i class="fas fa-user-plus"></i>
                        新規登録
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php
    require 'footer.php';
    exit;
}

$customer_id = $_SESSION['customer']['id'];
$product_id = $_REQUEST['product_id'];
$rating = $_REQUEST['rating'];
$comment = $_REQUEST['comment'];

// 商品情報を取得
$product_sql = $pdo->prepare('SELECT name FROM product WHERE id = ?');
$product_sql->bindParam(1, $product_id);
$product_sql->execute();
$product = $product_sql->fetch();

// 既存のレビューを確認
$check_sql = $pdo->prepare('SELECT * FROM review WHERE customer_id = ? AND product_id = ?');
$check_sql->bindParam(1, $customer_id);
$check_sql->bindParam(2, $product_id);
$check_sql->execute();
$existing = $check_sql->fetch();

$is_update = $existing ? true : false;
$success = false;
$error_message = '';

try {
    if ($existing) {
        // 更新
        $sql = $pdo->prepare('UPDATE review SET rating = ?, comment = ?, created_at = NOW() WHERE customer_id = ? AND product_id = ?');
        $sql->bindParam(1, $rating);
        $sql->bindParam(2, $comment);
        $sql->bindParam(3, $customer_id);
        $sql->bindParam(4, $product_id);
        $sql->execute();
        $success = true;
    } else {
        // 新規作成
        $sql = $pdo->prepare('INSERT INTO review (customer_id, product_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())');
        $sql->bindParam(1, $customer_id);
        $sql->bindParam(2, $product_id);
        $sql->bindParam(3, $rating);
        $sql->bindParam(4, $comment);
        $sql->execute();
        $success = true;
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

?>

<div class="review-save-container">
    <div class="result-card">
        <?php if ($success): ?>
            <div class="success-header">
                <div class="floating-elements">
                    <div class="floating-star"><i class="fas fa-star"></i></div>
                    <div class="floating-star"><i class="fas fa-star"></i></div>
                    <div class="floating-star"><i class="fas fa-star"></i></div>
                </div>
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="success-title">
                    <?= $is_update ? 'レビューを更新しました！' : 'レビューを投稿しました！' ?>
                </div>
                <div class="success-message">
                    ご投稿いただきありがとうございます
                </div>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
            </div>

            <div class="result-content">
                <div class="review-summary">
                    <h4><?= h($product['name']) ?></h4>
                    <div class="rating-display">
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i <= $rating ? '' : '-o' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-text">
                            <?= $rating ?> / 5 (<?= ['', 'とても悪い', '悪い', '普通', '良い', 'とても良い'][$rating] ?>)
                        </div>
                    </div>
                    <div class="comment-preview">
                        <?= nl2br(h($comment)) ?>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="detail.php?id=<?= $product_id ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i>
                        商品ページに戻る
                    </a>
                    <a href="product.php" class="btn btn-secondary">
                        <i class="fas fa-th-large"></i>
                        商品一覧を見る
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="error-header">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="success-title">
                    エラーが発生しました
                </div>
                <div class="success-message">
                    レビューの投稿に失敗しました
                </div>
            </div>

            <div class="result-content">
                <div class="review-summary">
                    <h4>エラー詳細</h4>
                    <div class="comment-preview">
                        <?= h($error_message) ?>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        戻って修正
                    </a>
                    <a href="detail.php?id=<?= $product_id ?>" class="btn btn-primary">
                        <i class="fas fa-home"></i>
                        商品ページへ
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // 成功時の自動リダイレクト（オプション）
    <?php if ($success): ?>
    setTimeout(() => {
        const confirmRedirect = confirm('商品ページに戻りますか？');
        if (confirmRedirect) {
            window.location.href = 'detail.php?id=<?= $product_id ?>';
        }
    }, 5000);
    <?php endif; ?>

    // ページ読み込み時のアニメーション
    document.addEventListener('DOMContentLoaded', function() {
        const card = document.querySelector('.result-card');
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease-out';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100);
    });
</script>

<?php require 'footer.php'; ?>