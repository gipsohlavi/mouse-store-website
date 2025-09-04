<?php
session_start();
require 'common.php';
require 'header.php';
require 'menu.php';

function echo_product_card($row, $rank, $pdo, $recommend_badge = false, $sale_badge = false) {
    $images = getImage($row['id'], $pdo);
    $id = $row['id'];
    $image_path = "images/{$images[0]}.jpg";
    if (!file_exists($image_path)) {
        $image_path = "images/no-image.jpg";
    }

    $conn_sql = $pdo->prepare('SELECT m.name FROM product_master_relation pmr JOIN master m ON pmr.master_id = m.master_id WHERE pmr.product_id = ? AND pmr.kbn_id = 3');
    $conn_sql->bindParam(1, $id);
    $conn_sql->execute();
    $connections = $conn_sql->fetchAll(PDO::FETCH_COLUMN);
    $is_wireless = false;
    foreach ($connections as $conn) {
        if (strpos($conn, 'ワイヤレス') !== false || strpos($conn, '無線') !== false || strpos($conn, 'Bluetooth') !== false) {
            $is_wireless = true;
            break;
        }
    }

    $sensor_sql = $pdo->prepare('SELECT m.name FROM product_master_relation pmr JOIN master m ON pmr.master_id = m.master_id WHERE pmr.product_id = ? AND pmr.kbn_id = 5 LIMIT 1');
    $sensor_sql->bindParam(1, $id);
    $sensor_sql->execute();
    $sensor = $sensor_sql->fetchColumn();

    echo '<div class="product-card">';
    echo '<div class="product-image">';

    if ($rank) {
        echo '<div class="rank-badge rank-', $rank, '">', $rank, '位</div>';
    } elseif ($recommend_badge) {
        echo '<div class="product-badge recommend">おすすめ</div>';
    } elseif ($sale_badge) {
        echo '<div class="product-badge sale">SALE</div>';
    }

    if ($is_wireless) {
        echo '<div class="wireless-indicator" title="ワイヤレス対応">';
        echo '<i class="fas fa-wifi"></i>';
        echo '</div>';
    }

    echo '<button class="favorite-btn" data-product-id="', $id, '" aria-label="お気に入りに追加">';
    echo '<i class="far fa-heart"></i>';
    echo '</button>';

    echo '<img src="', $image_path, '" alt="', h($row['name']), '" loading="lazy">';
    echo '</div>';

    echo '<div class="product-info">';
    echo '<h3 class="product-title">';
    echo '<a href="detail.php?id=', $id, '">', h($row['name']), '</a>';
    echo '</h3>';

    echo '<div class="price-wrapper">';
    echo '<div class="product-price">¥', number_format($row['price']), '</div>';
    if ($row['on_sale']) {
        $original_price = $row['price'] * 1.2;
        echo '<div class="price-compare">¥', number_format($original_price), '</div>';
    }
    echo '</div>';

    echo '<div class="feature-highlights">';
    if ($row['weight'] < 50) {
        echo '<span class="feature-tag highlight"><i class="fas fa-feather-alt"></i>超軽量 ', $row['weight'], 'g</span>';
    }
    if ($sensor && (strpos($sensor, '3950') !== false || strpos($sensor, '3395') !== false || strpos($sensor, 'Focus Pro') !== false)) {
        echo '<span class="feature-tag highlight"><i class="fas fa-microchip"></i>フラグシップセンサー</span>';
    }
    if ($row['polling_rate'] >= 8000) {
        echo '<span class="feature-tag highlight"><i class="fas fa-bolt"></i>8KHz対応</span>';
    }
    if ($row['motion_sync_support']) {
        echo '<span class="feature-tag"><i class="fas fa-sync"></i>Motion Sync</span>';
    }
    if ($row['battery_life_hours'] >= 70) {
        echo '<span class="feature-tag"><i class="fas fa-battery-full"></i>', $row['battery_life_hours'], '時間駆動</span>';
    }
    echo '</div>';

    echo '<div class="performance-meter">';
    $speed_percent = min(100, ($row['dpi_max'] / 36000) * 100);
    echo '<div class="meter-item"><div class="meter-label">Speed</div><div class="meter-bar"><div class="meter-fill speed" style="width: ', $speed_percent, '%"></div></div></div>';
    $precision_percent = min(100, ($row['polling_rate'] / 8000) * 100);
    echo '<div class="meter-item"><div class="meter-label">Precision</div><div class="meter-bar"><div class="meter-fill precision" style="width: ', $precision_percent, '%"></div></div></div>';
    $lightweight_percent = max(0, min(100, ((150 - $row['weight']) / 150) * 100));
    echo '<div class="meter-item"><div class="meter-label">Agility</div><div class="meter-bar"><div class="meter-fill lightweight" style="width: ', $lightweight_percent, '%"></div></div></div>';
    echo '</div>';

    $rating = rand(35, 50) / 10;
    $review_count = rand(10, 200);
    echo '<div class="product-rating">';
    echo '<div class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            echo '<i class="fas fa-star"></i>';
        } elseif ($i - 0.5 <= $rating) {
            echo '<i class="fas fa-star-half-alt"></i>';
        } else {
            echo '<i class="far fa-star"></i>';
        }
    }
    echo '</div>';
    echo '<span class="rating-count">(', $review_count, ')</span>';
    echo '</div>';

    echo '<div class="stock-status">';
    if ($row['stock_quantity'] > 10) {
        echo '<span class="stock-available"><i class="fas fa-check-circle"></i>在庫あり</span>';
    } elseif ($row['stock_quantity'] > 0) {
        echo '<span class="stock-limited"><i class="fas fa-exclamation-triangle"></i>残り', $row['stock_quantity'], '個</span>';
    } else {
        echo '<span class="stock-out"><i class="fas fa-times-circle"></i>在庫切れ</span>';
    }
    echo '</div>';

    echo '<div class="product-actions">';
    echo '<a href="detail.php?id=', $id, '" class="btn btn-outline"><i class="fas fa-info-circle"></i>詳細</a>';
    if ($row['stock_quantity'] > 0) {
        echo '<button class="btn btn-primary add-to-cart" data-product-id="', $id, '"><i class="fas fa-cart-plus"></i>カートに追加</button>';
    } else {
        echo '<button class="btn btn-disabled" disabled><i class="fas fa-ban"></i>在庫切れ</button>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
?>

<div class="ranking-header">
    <div class="ranking-icon">
        <i class="fas fa-trophy"></i>
    </div>
    <h2 class="ranking-title">月間ランキング</h2>
    <div class="ranking-subtitle">今月最も売れているトップ5の製品</div>
</div>
<div class="product-list-container">
    <div class="product-list product-slider">
<?php
$sql = $pdo->prepare('select * from product as p inner join (
    select product_id from ranking group by product_id order by 
    sum(sales_quantity) desc limit 5 ) as top_products on p.id = 
    top_products.product_id');
$sql->execute();
$products = $sql->fetchAll();

$rank = 1;
foreach ($products as $row) {
    echo_product_card($row, $rank, $pdo);
    $rank++;
}
?>
    </div>
    <button class="slider-btn prev-btn">&#10094;</button>
    <button class="slider-btn next-btn">&#10095;</button>
</div>

---

<div class="ranking-header">
    <div class="ranking-icon">
        <i class="fas fa-thumbs-up"></i>
    </div>
    <h2 class="ranking-title">おすすめ商品</h2>
    <div class="ranking-subtitle">今あなたにイチオシの製品</div>
</div>
<div class="product-list-container">
    <div class="product-list product-slider-recommend">
<?php
$sql = $pdo->prepare('select * from product where recommend = 1 order by id desc limit 10');
$sql->execute();
$recommended_products = $sql->fetchAll();
$total_recommended_items = count($recommended_products);

foreach ($recommended_products as $row) {
    echo_product_card($row, '', $pdo, true, false);
}
?>
    </div>
    <button class="slider-btn prev-btn-recommend">&#10094;</button>
    <button class="slider-btn next-btn-recommend">&#10095;</button>
</div>

---

<div class="ranking-header">
    <div class="ranking-icon">
        <i class="fas fa-tags"></i>
    </div>
    <h2 class="ranking-title">セール商品</h2>
    <div class="ranking-subtitle">今だけの特別価格！</div>
</div>
<div class="product-list-container">
    <div class="product-list product-slider-sale">
<?php
$sql = $pdo->prepare('select * from product where on_sale = 1 order by id desc limit 10');
$sql->execute();
$sale_products = $sql->fetchAll();
$total_sale_items = count($sale_products);

foreach ($sale_products as $row) {
    echo_product_card($row, '', $pdo, false, true);
}
?>
    </div>
    <button class="slider-btn prev-btn-sale">&#10094;</button>
    <button class="slider-btn next-btn-sale">&#10095;</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ランキングスライダー
    const rankingSlider = document.querySelector('.product-slider');
    const rankingPrevBtn = document.querySelector('.prev-btn');
    const rankingNextBtn = document.querySelector('.next-btn');
    const rankingTotalItems = 5;
    const rankingItemsPerSlide = 3;
    let rankingIndex = rankingItemsPerSlide;

    // クローン要素の作成
    if (rankingTotalItems > rankingItemsPerSlide) {
        for (let i = 0; i < rankingItemsPerSlide; i++) {
            if (rankingSlider.children[i]) {
                const firstItem = rankingSlider.children[i].cloneNode(true);
                rankingSlider.appendChild(firstItem);
            }
        }
        for (let i = 0; i < rankingItemsPerSlide; i++) {
            if (rankingSlider.children[rankingTotalItems - 1 - i]) {
                const lastItem = rankingSlider.children[rankingTotalItems - 1 - i].cloneNode(true);
                rankingSlider.prepend(lastItem);
            }
        }
        rankingSlider.style.transform = `translateX(-${rankingIndex * (100 / rankingItemsPerSlide)}%)`;
    } else {
        rankingPrevBtn.style.display = 'none';
        rankingNextBtn.style.display = 'none';
    }

    function updateRankingSlider() {
        rankingSlider.style.transition = 'transform 0.5s ease-in-out';
        rankingSlider.style.transform = `translateX(-${rankingIndex * (100 / rankingItemsPerSlide)}%)`;
    }

    rankingNextBtn.addEventListener('click', function() {
        rankingIndex++;
        updateRankingSlider();
    });

    rankingPrevBtn.addEventListener('click', function() {
        rankingIndex--;
        updateRankingSlider();
    });

    rankingSlider.addEventListener('transitionend', function() {
        if (rankingIndex >= rankingTotalItems + rankingItemsPerSlide) {
            rankingSlider.style.transition = 'none';
            rankingIndex = rankingItemsPerSlide;
            rankingSlider.style.transform = `translateX(-${rankingIndex * (100 / rankingItemsPerSlide)}%)`;
        }
        if (rankingIndex < rankingItemsPerSlide) {
            rankingSlider.style.transition = 'none';
            rankingIndex = rankingTotalItems + rankingIndex;
            rankingSlider.style.transform = `translateX(-${rankingIndex * (100 / rankingItemsPerSlide)}%)`;
        }
    });
    
    // おすすめ商品スライダー
    const recommendSlider = document.querySelector('.product-slider-recommend');
    const recommendPrevBtn = document.querySelector('.prev-btn-recommend');
    const recommendNextBtn = document.querySelector('.next-btn-recommend');
    const recommendedProductsCount = <?php echo $total_recommended_items; ?>;
    const recommendItemsPerSlide = 3;
    let recommendIndex = recommendItemsPerSlide;

    if (recommendedProductsCount > recommendItemsPerSlide) {
        // クローン要素の作成
        for (let i = 0; i < recommendItemsPerSlide; i++) {
            if (recommendSlider.children[i]) {
                const firstItem = recommendSlider.children[i].cloneNode(true);
                recommendSlider.appendChild(firstItem);
            }
        }
        for (let i = 0; i < recommendItemsPerSlide; i++) {
            if (recommendSlider.children[recommendedProductsCount - 1 - i]) {
                const lastItem = recommendSlider.children[recommendedProductsCount - 1 - i].cloneNode(true);
                recommendSlider.prepend(lastItem);
            }
        }
        recommendSlider.style.transform = `translateX(-${recommendIndex * (100 / recommendItemsPerSlide)}%)`;

        function updateRecommendSlider() {
            const slideDistance = -recommendIndex * (100 / recommendItemsPerSlide);
            recommendSlider.style.transition = 'transform 0.5s ease-in-out';
            recommendSlider.style.transform = `translateX(${slideDistance}%)`;
        }

        recommendNextBtn.addEventListener('click', function() {
            recommendIndex++;
            updateRecommendSlider();
        });

        recommendPrevBtn.addEventListener('click', function() {
            recommendIndex--;
            updateRecommendSlider();
        });

        recommendSlider.addEventListener('transitionend', function() {
            if (recommendIndex >= recommendedProductsCount + recommendItemsPerSlide) {
                recommendSlider.style.transition = 'none';
                recommendIndex = recommendItemsPerSlide;
                recommendSlider.style.transform = `translateX(-${recommendIndex * (100 / recommendItemsPerSlide)}%)`;
            }
            if (recommendIndex < recommendItemsPerSlide) {
                recommendSlider.style.transition = 'none';
                recommendIndex = recommendedProductsCount + recommendIndex;
                recommendSlider.style.transform = `translateX(-${recommendIndex * (100 / recommendItemsPerSlide)}%)`;
            }
        });
    } else {
        if (recommendPrevBtn) recommendPrevBtn.style.display = 'none';
        if (recommendNextBtn) recommendNextBtn.style.display = 'none';
    }

    // セール商品スライダー
    const saleSlider = document.querySelector('.product-slider-sale');
    const salePrevBtn = document.querySelector('.prev-btn-sale');
    const saleNextBtn = document.querySelector('.next-btn-sale');
    const saleProductsCount = <?php echo $total_sale_items; ?>;
    const saleItemsPerSlide = 3;
    let saleIndex = saleItemsPerSlide;

    if (saleProductsCount > saleItemsPerSlide) {
        // クローン要素の作成
        for (let i = 0; i < saleItemsPerSlide; i++) {
            if (saleSlider.children[i]) {
                const firstItem = saleSlider.children[i].cloneNode(true);
                saleSlider.appendChild(firstItem);
            }
        }
        for (let i = 0; i < saleItemsPerSlide; i++) {
            if (saleSlider.children[saleProductsCount - 1 - i]) {
                const lastItem = saleSlider.children[saleProductsCount - 1 - i].cloneNode(true);
                saleSlider.prepend(lastItem);
            }
        }
        saleSlider.style.transform = `translateX(-${saleIndex * (100 / saleItemsPerSlide)}%)`;

        function updateSaleSlider() {
            const slideDistance = -saleIndex * (100 / saleItemsPerSlide);
            saleSlider.style.transition = 'transform 0.5s ease-in-out';
            saleSlider.style.transform = `translateX(${slideDistance}%)`;
        }

        saleNextBtn.addEventListener('click', function() {
            saleIndex++;
            updateSaleSlider();
        });

        salePrevBtn.addEventListener('click', function() {
            saleIndex--;
            updateSaleSlider();
        });

        saleSlider.addEventListener('transitionend', function() {
            if (saleIndex >= saleProductsCount + saleItemsPerSlide) {
                saleSlider.style.transition = 'none';
                saleIndex = saleItemsPerSlide;
                saleSlider.style.transform = `translateX(-${saleIndex * (100 / saleItemsPerSlide)}%)`;
            }
            if (saleIndex < saleItemsPerSlide) {
                saleSlider.style.transition = 'none';
                saleIndex = saleProductsCount + saleIndex;
                saleSlider.style.transform = `translateX(-${saleIndex * (100 / saleItemsPerSlide)}%)`;
            }
        });
    } else {
        if (salePrevBtn) salePrevBtn.style.display = 'none';
        if (saleNextBtn) saleNextBtn.style.display = 'none';
    }
});
</script>

<?php require 'footer.php'; ?>