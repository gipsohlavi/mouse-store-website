<?php
function displayReviews($product_id, $pdo)
{
    $sql = $pdo->prepare('
        SELECT r.*, c.name as customer_name
        FROM review r
        JOIN customer c ON r.customer_id = c.id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC
    ');
    $sql->bindParam(1, $product_id);
    $sql->execute();
    $reviews = $sql->fetchAll();

    echo '<style>
        .reviews-section {
            margin-top: 40px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .reviews-section h3 {
            font-size: 1.8em;
            margin-bottom: 25px;
            color: #333;
            text-align: center;
        }

        .rating-summary {
            display: flex;
            gap: 40px;
            margin-bottom: 30px;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .average-rating {
            text-align: center;
            min-width: 150px;
        }

        .rating-number {
            font-size: 3em;
            font-weight: bold;
            color: #007bff;
            display: block;
        }

        .stars {
            color: #ffc107;
            font-size: 1.2em;
            margin: 10px 0;
        }

        .review-count {
            color: #666;
            font-size: 0.9em;
        }

        .rating-distribution {
            flex: 1;
        }

        .rating-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .rating-label {
            min-width: 30px;
            font-size: 0.9em;
            color: #666;
        }

        .bar-container {
            flex: 1;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #ffc107, #fd7e14);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .rating-percentage {
            min-width: 35px;
            font-size: 0.9em;
            color: #666;
            text-align: right;
        }

        .reviews-list {
            margin-top: 30px;
        }

        .review-item {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .review-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .reviewer-name {
            font-weight: 600;
            color: #333;
            font-size: 1.1em;
        }

        .review-date {
            color: #666;
            font-size: 0.9em;
        }

        .review-rating {
            color: #ffc107;
            font-size: 1.2em;
            margin-bottom: 15px;
        }

        .review-comment {
            line-height: 1.6;
            color: #444;
            font-size: 1em;
        }

        .no-reviews {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-reviews i {
            font-size: 3em;
            color: #ccc;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .rating-summary {
                flex-direction: column;
                gap: 20px;
            }

            .review-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>';

    if (count($reviews) > 0) {
        echo '<div class="reviews-section">';
        echo '<h3><i class="fas fa-star"></i> カスタマーレビュー</h3>';

        // 評価サマリー
        $avg_sql = $pdo->prepare('SELECT AVG(rating) as avg, COUNT(*) as count FROM review WHERE product_id = ?');
        $avg_sql->bindParam(1, $product_id);
        $avg_sql->execute();
        $summary = $avg_sql->fetch();

        echo '<div class="rating-summary">';
        echo '<div class="average-rating">';
        echo '<span class="rating-number">', round($summary['avg'], 1), '</span>';
        echo '<div class="stars">';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= floor($summary['avg'])) {
                echo '<i class="fas fa-star"></i>';
            } elseif ($i - 0.5 <= $summary['avg']) {
                echo '<i class="fas fa-star-half-alt"></i>';
            } else {
                echo '<i class="far fa-star"></i>';
            }
        }
        echo '</div>';
        echo '<span class="review-count">', $summary['count'], ' 件のレビュー</span>';
        echo '</div>';

        // 評価分布
        echo '<div class="rating-distribution">';
        for ($i = 5; $i >= 1; $i--) {
            $count_sql = $pdo->prepare('SELECT COUNT(*) as count FROM review WHERE product_id = ? AND rating = ?');
            $count_sql->bindParam(1, $product_id);
            $count_sql->bindParam(2, $i);
            $count_sql->execute();
            $count = $count_sql->fetchColumn();
            $percentage = $summary['count'] > 0 ? ($count / $summary['count']) * 100 : 0;

            echo '<div class="rating-bar">';
            echo '<span class="rating-label">', $i, '星</span>';
            echo '<div class="bar-container">';
            echo '<div class="bar-fill" style="width: ', $percentage, '%"></div>';
            echo '</div>';
            echo '<span class="rating-percentage">', round($percentage), '%</span>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';

        // 個別レビュー
        echo '<div class="reviews-list">';
        foreach ($reviews as $review) {
            echo '<div class="review-item">';
            echo '<div class="review-header">';
            echo '<div class="reviewer-name"><i class="fas fa-user-circle"></i> ', h($review['customer_name']), '</div>';
            echo '<div class="review-date">', date('Y年m月d日', strtotime($review['created_at'])), '</div>';
            echo '</div>';
            echo '<div class="review-rating">';
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= $review['rating']) {
                    echo '<i class="fas fa-star"></i>';
                } else {
                    echo '<i class="far fa-star"></i>';
                }
            }
            echo '</div>';
            echo '<div class="review-comment">', nl2br(h($review['comment'])), '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="reviews-section">';
        echo '<h3><i class="fas fa-star"></i> カスタマーレビュー</h3>';
        echo '<div class="no-reviews">';
        echo '<i class="fas fa-comment-slash"></i>';
        echo '<h4>まだレビューがありません</h4>';
        echo '<p>この商品の最初のレビューを投稿してみませんか？</p>';
        echo '</div>';
        echo '</div>';
    }
}
?>