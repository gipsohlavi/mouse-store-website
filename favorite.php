<?php
if (isset($_SESSION['customer'])) {
    $sql = $pdo->prepare(
        'select id, name, price from favorite, product ' .
            'where customer_id=? and product_id=id ' .
            'order by favorite_date desc'
    );
    $sql->bindParam(1, $_SESSION['customer']['id']);
    $sql->execute();
    $favorites = $sql->fetchAll();

    if (!empty($favorites)) {
        echo '<div class="favorites-container">';
        echo '<div class="favorites-header">';
        echo '<h2><i class="fas fa-heart"></i> お気に入り商品</h2>';
        echo '<p class="favorites-count">', count($favorites), '件の商品</p>';
        echo '</div>';

        echo '<div class="favorites-grid">';

        foreach ($favorites as $row) {
            $id = h($row['id']);
            $name = h($row['name']);
            $price = h($row['price']);

            // 商品詳細情報を取得
            $detail_sql = $pdo->prepare('SELECT * FROM product WHERE id = ?');
            $detail_sql->bindParam(1, $id);
            $detail_sql->execute();
            $product_detail = $detail_sql->fetch();

            // 画像パス設定（商品IDを直接使用）
            $image_path = "images/{$id}.jpg";
            if (!file_exists($image_path)) {
                $image_path = "images/no-image.jpg";
            }

            echo '<div class="favorite-card" data-price="', $price, '" data-tax="', $product_detail['tax_id'], '">';
            echo '<div class="favorite-image">';
            echo '<img src="', $image_path, '" alt="', $name, '" loading="lazy">';
            echo '</div>';

            echo '<div class="favorite-info">';
            echo '<h3 class="favorite-title">';
            echo '<a href="detail.php?id=', $id, '">', $name, '</a>';
            echo '</h3>';

            echo '<div class="favorite-price">¥', number_format($price), '</div>';

            // 商品詳細があれば特徴を表示
            if ($product_detail) {
                echo '<div class="favorite-features">';

                // 重量
                if ($product_detail['weight']) {
                    echo '<span class="feature-item">';
                    echo '<i class="fas fa-weight-hanging"></i>';
                    echo $product_detail['weight'], 'g';
                    echo '</span>';
                }

                // DPI
                if ($product_detail['dpi_max']) {
                    echo '<span class="feature-item">';
                    echo '<i class="fas fa-mouse"></i>';
                    echo number_format($product_detail['dpi_max']), ' DPI';
                    echo '</span>';
                }

                // 8KHz対応
                if ($product_detail['polling_rate'] >= 8000) {
                    echo '<span class="feature-item highlight">';
                    echo '<i class="fas fa-bolt"></i>';
                    echo '8KHz';
                    echo '</span>';
                }

                echo '</div>';
            }

            // 在庫状況
            if ($product_detail) {
                echo '<div class="favorite-stock">';
                if ($product_detail['stock_quantity'] > 10) {
                    echo '<span class="stock-available"><i class="fas fa-check-circle"></i>在庫あり</span>';
                } elseif ($product_detail['stock_quantity'] > 0) {
                    echo '<span class="stock-limited"><i class="fas fa-exclamation-triangle"></i>残り', $product_detail['stock_quantity'], '個</span>';
                } else {
                    echo '<span class="stock-out"><i class="fas fa-times-circle"></i>在庫切れ</span>';
                }
                echo '</div>';
            }

            echo '<div class="favorite-actions">';
            echo '<a href="detail.php?id=', $id, '" class="btn btn-outline">';
            echo '<i class="fas fa-info-circle"></i>';
            echo '詳細を見る';
            echo '</a>';

            if ($product_detail && $product_detail['stock_quantity'] > 0) {
                echo '<button class="btn btn-primary add-to-cart" data-product-id="', $id, '">';
                echo '<i class="fas fa-cart-plus"></i>';
                echo 'カートに追加';
                echo '</button>';
            } else {
                echo '<button class="btn btn-disabled" disabled>';
                echo '<i class="fas fa-ban"></i>';
                echo '在庫切れ';
                echo '</button>';
            }

            echo '<button class="btn btn-danger remove-favorite" data-product-id="', $id, '" data-product-name="', h($name), '">';
            echo '<i class="fas fa-heart-broken"></i>';
            echo '削除';
            echo '</button>';
            echo '</div>';

            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';

?>
        <script>
            // 現在のカートアイテム数を取得
            let cartItemCount = <?= isset($_SESSION['product']) ? count($_SESSION['product']) : 0 ?>;
            
            // 現在のお気に入り数を取得
            let favoriteItemCount = <?= count($favorites) ?>;

            document.addEventListener('DOMContentLoaded', function() {
                // お気に入り削除
                document.querySelectorAll('.remove-favorite').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const productId = this.dataset.productId;
                        const productName = this.dataset.productName;

                        if (confirm(`「${productName}」をお気に入りから削除しますか？`)) {
                            this.disabled = true;
                            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 削除中...';

                            // favorite-delete.phpにAjaxリクエスト
                            fetch(`favorite-delete.php?id=${productId}`, {
                                    method: 'GET'
                                })
                                .then(response => {
                                    if (response.ok) {
                                        // お気に入り数を更新
                                        favoriteItemCount--;
                                        updateFavoriteBadge(favoriteItemCount);

                                        // カードをフェードアウト
                                        const card = this.closest('.favorite-card');
                                        card.style.transition = 'all 0.5s ease';
                                        card.style.transform = 'scale(0.8)';
                                        card.style.opacity = '0';

                                        setTimeout(() => {
                                            card.remove();

                                            // 残り件数を更新
                                            const remainingCards = document.querySelectorAll('.favorite-card').length;
                                            const countElement = document.querySelector('.favorites-count');
                                            if (countElement) {
                                                countElement.textContent = `${remainingCards}件の商品`;
                                            }

                                            // お気に入りがなくなった場合
                                            if (remainingCards === 0) {
                                                setTimeout(() => {
                                                    location.reload();
                                                }, 500);
                                            }
                                        }, 500);

                                        // 成功通知
                                        showNotification('お気に入りから削除しました', 'success');
                                    } else {
                                        throw new Error('削除に失敗しました');
                                    }
                                })
                                .catch(error => {
                                    this.disabled = false;
                                    this.innerHTML = '<i class="fas fa-heart-broken"></i> 削除';
                                    alert('削除に失敗しました: ' + error.message);
                                });
                        }
                    });
                });

                // カートに追加
                document.querySelectorAll('.add-to-cart').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const productId = this.dataset.productId;
                        const card = this.closest('.favorite-card');
                        const productName = card.querySelector('.favorite-title a').textContent;
                        const productPrice = card.querySelector('.favorite-price').textContent;

                        this.disabled = true;
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 追加中...';

                        // cart-insert.phpに商品を追加（必要な情報を全て送信）
                        const formData = new FormData();
                        formData.append('id', productId);
                        formData.append('name', productName);
                        formData.append('price', card.dataset.price);
                        formData.append('tax', card.dataset.tax);
                        formData.append('count', 1);

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

                                    // 成功アニメーション
                                    this.classList.add('success');
                                    this.innerHTML = '<i class="fas fa-check"></i> 追加完了!';

                                    // カートアニメーション（商品画像がカートに飛ぶ）
                                    const productImage = card.querySelector('.favorite-image img');
                                    createCartAnimation(productImage, this);

                                    // 通知表示
                                    showAddToCartNotification(productName, productPrice);

                                    setTimeout(() => {
                                        this.classList.remove('success');
                                        this.innerHTML = originalText;
                                        this.disabled = false;
                                    }, 2000);
                                } else {
                                    throw new Error('カートへの追加に失敗しました');
                                }
                            })
                            .catch(error => {
                                this.innerHTML = originalText;
                                this.disabled = false;
                                alert('カートへの追加に失敗しました: ' + error.message);
                            });
                    });
                });
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
                        cartLink.style.position = 'relative';
                        const newBadge = document.createElement('span');
                        newBadge.textContent = count > 99 ? '99+' : count;
                        
                        Object.assign(newBadge.style, {
                            position: 'absolute',
                            top: '-10px',
                            right: '-10px',
                            background: '#2563eb',
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
                            boxShadow: '0 2px 4px rgba(37, 99, 235, 0.4)',
                            transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                            opacity: '0',
                            transform: 'scale(0)',
                            zIndex: '10'
                        });
                        
                        cartLink.appendChild(newBadge);
                        
                        requestAnimationFrame(() => {
                            newBadge.style.opacity = '1';
                            newBadge.style.transform = 'scale(1)';
                        });
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
                
                if (cartBadge) {
                    cartBadge.style.animation = 'cartCountPulse 0.6s ease-in-out';
                    setTimeout(() => {
                        cartBadge.style.animation = '';
                    }, 600);
                }
            }

            // カートアニメーション（商品画像がカートに飛んでいく効果）
            function createCartAnimation(productImg, button) {
                const cartIcon = document.querySelector('.cart-link');

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

            // カート追加通知
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

            // 通知表示
            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;

                document.body.appendChild(notification);

                setTimeout(() => notification.classList.add('show'), 10);

                setTimeout(() => {
                    notification.classList.add('hide');
                    setTimeout(() => notification.remove(), 300);
                }, 2000);
            }
        </script>

        <style>
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
                50% { transform: scale(1.3); background: #3b82f6; }
                100% { transform: scale(1); }
            }

            /* 通知システムのスタイル */
            .cart-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.15);
                border: 1px solid #e5e7eb;
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
                color: #1f2937;
                margin-bottom: 4px;
            }

            .notification-product {
                font-size: 0.9em;
                color: #6b7280;
                margin-bottom: 2px;
            }

            .notification-price {
                font-weight: 600;
                color: #2563eb;
                font-size: 0.95em;
            }

            .notification-actions {
                flex-shrink: 0;
            }

            .notification-close {
                background: none;
                border: none;
                color: #6b7280;
                cursor: pointer;
                padding: 5px;
                border-radius: 50%;
                transition: all 0.3s;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .notification-close:hover {
                background: #f9fafb;
                color: #1f2937;
            }

            .favorites-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }

            .favorites-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #e5e7eb;
            }

            .favorites-header h2 {
                color: #1f2937;
                font-size: 1.8rem;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .favorites-header h2 i {
                color: #e91e63;
            }

            .favorites-count {
                color: #6b7280;
                font-size: 1rem;
                margin: 0;
            }

            .favorites-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
            }

            .favorite-card {
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                overflow: hidden;
                transition: all 0.3s ease;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            }

            .favorite-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
                border-color: #e91e63;
            }

            .favorite-image {
                height: 200px;
                background: #f9fafb;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }

            .favorite-image img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                transition: transform 0.3s ease;
            }

            .favorite-card:hover .favorite-image img {
                transform: scale(1.05);
            }

            .favorite-info {
                padding: 20px;
            }

            .favorite-title {
                margin-bottom: 10px;
            }

            .favorite-title a {
                font-size: 1.1rem;
                font-weight: 600;
                color: #1f2937;
                text-decoration: none;
                transition: color 0.3s;
            }

            .favorite-title a:hover {
                color: #2563eb;
            }

            .favorite-price {
                font-size: 1.4rem;
                font-weight: 700;
                color: #2563eb;
                margin-bottom: 15px;
            }

            .favorite-features {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                margin-bottom: 15px;
            }

            .feature-item {
                background: #f3f4f6;
                color: #6b7280;
                padding: 4px 8px;
                border-radius: 6px;
                font-size: 0.875rem;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .feature-item.highlight {
                background: linear-gradient(135deg, #2563eb15, #7c3aed15);
                color: #2563eb;
                border: 1px solid #2563eb30;
            }

            .feature-item i {
                font-size: 0.8rem;
            }

            .favorite-stock {
                margin-bottom: 15px;
            }

            .stock-available {
                color: #10b981;
                font-size: 0.875rem;
                font-weight: 500;
            }

            .stock-limited {
                color: #f59e0b;
                font-size: 0.875rem;
                font-weight: 500;
            }

            .stock-out {
                color: #ef4444;
                font-size: 0.875rem;
                font-weight: 500;
            }

            .favorite-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 1fr auto;
                gap: 8px;
            }

            .favorite-actions .btn:first-child {
                grid-column: 1;
            }

            .favorite-actions .btn:nth-child(2) {
                grid-column: 2;
            }

            .favorite-actions .remove-favorite {
                grid-column: 1 / -1;
            }

            .btn {
                padding: 0.75rem 1rem;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 500;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                transition: all 0.3s ease;
                font-size: 0.875rem;
            }

            .btn-outline {
                background: transparent;
                color: #6b7280;
                border: 1px solid #d1d5db;
            }

            .btn-outline:hover {
                background: #f3f4f6;
                color: #2563eb;
                border-color: #2563eb;
            }

            .btn-primary {
                background: #2563eb;
                color: white;
                border: 1px solid #2563eb;
            }

            .btn-primary:hover {
                background: #1d4ed8;
            }

            .btn-primary.success {
                background: #10b981;
                border-color: #10b981;
            }

            .btn-danger {
                background: #ef4444;
                color: white;
                border: 1px solid #ef4444;
            }

            .btn-danger:hover {
                background: #dc2626;
                border-color: #dc2626;
            }

            .btn-disabled {
                background: #d1d5db;
                color: #6b7280;
                cursor: not-allowed;
                border: 1px solid #d1d5db;
            }

            /* 通知スタイル */
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                background: white;
                border-radius: 8px;
                padding: 15px 20px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                transform: translateX(400px);
                opacity: 0;
                transition: all 0.3s ease;
            }

            .notification.show {
                transform: translateX(0);
                opacity: 1;
            }

            .notification.hide {
                transform: translateX(400px);
                opacity: 0;
            }

            .notification.success {
                border-left: 4px solid #10b981;
            }

            .notification-content {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .notification-content i {
                color: #10b981;
            }

            @media (max-width: 768px) {
                .favorites-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }

                .favorites-grid {
                    grid-template-columns: 1fr;
                    gap: 15px;
                }

                .favorite-actions {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    <?php

    } else {
        // お気に入りが空の場合
        echo '<div class="empty-favorites">';
        echo '<div class="empty-favorites-content">';
        echo '<div class="empty-icon">';
        echo '<i class="fas fa-heart"></i>';
        echo '</div>';
        echo '<h2>お気に入りリストは空です</h2>';
        echo '<p>気になる商品を見つけたら、ハートボタンでお気に入りに追加しましょう。</p>';
        echo '<div class="empty-actions">';
        echo '<a href="product.php" class="btn btn-primary">';
        echo '<i class="fas fa-shopping-bag"></i>';
        echo '商品を探す';
        echo '</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

    ?>
        <style>
            .empty-favorites {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 400px;
                padding: 40px 20px;
            }

            .empty-favorites-content {
                text-align: center;
                max-width: 500px;
            }

            .empty-icon {
                font-size: 4rem;
                color: #e5e7eb;
                margin-bottom: 20px;
            }

            .empty-favorites h2 {
                font-size: 1.5rem;
                color: #1f2937;
                margin-bottom: 10px;
            }

            .empty-favorites p {
                color: #6b7280;
                margin-bottom: 30px;
                font-size: 1.1rem;
            }

            .empty-actions {
                display: flex;
                justify-content: center;
            }

            .btn {
                padding: 0.75rem 1.5rem;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 500;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                transition: all 0.3s ease;
            }

            .btn-primary {
                background: #2563eb;
                color: white;
            }

            .btn-primary:hover {
                background: #1d4ed8;
            }
        </style>
    <?php
    }
} else {
    // 未ログイン
    echo '<div class="login-required">';
    echo '<div class="login-content">';
    echo '<div class="login-icon">';
    echo '<i class="fas fa-user-circle"></i>';
    echo '</div>';
    echo '<h2>ログインが必要です</h2>';
    echo '<p>お気に入り商品を表示するには、ログインしてください。</p>';
    echo '<div class="login-actions">';
    echo '<a href="login-input.php" class="btn btn-primary">';
    echo '<i class="fas fa-sign-in-alt"></i>';
    echo 'ログイン';
    echo '</a>';
    echo '<a href="customer-input.php" class="btn btn-outline">';
    echo '<i class="fas fa-user-plus"></i>';
    echo '新規会員登録';
    echo '</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    ?>
<?php
}
?>