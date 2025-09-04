<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>

<?php
// ログインチェック
if (!isset($_SESSION['customer'])) {
    header('Location: login-input.php');
    exit;
}

// カートの中身チェック
if (!isset($_SESSION['product']) || empty($_SESSION['product'])) {
    header('Location: cart-show.php');
    exit;
}

$customer_id = $_SESSION['customer']['id'];

// 顧客の配送先一覧を取得
$shipping_sql = $pdo->prepare('
    SELECT sa.*, r.region_id 
    FROM shipping_addresses sa
    LEFT JOIN region r ON r.prefectures_id = (
        SELECT master_id FROM master WHERE kbn = 12 AND name = sa.prefecture
    )
    WHERE sa.customer_id = ? 
    ORDER BY sa.is_default DESC, sa.created_at ASC
');
$shipping_sql->bindParam(1, $customer_id, PDO::PARAM_INT);
$shipping_sql->execute();
$shipping_addresses = $shipping_sql->fetchAll();

// 配送先が1つもない場合は追加画面へリダイレクト
if (empty($shipping_addresses)) {
    $_SESSION['error'] = '配送先を登録してください。';
    header('Location: shipping-address-add.php');
    exit;
}

// 現在選択されている配送先ID
$selected_address_id = $_SESSION['selected_shipping_address'] ?? $shipping_addresses[0]['id'];

// 配送料計算用の関数
function calculateShippingFee($region_id, $remote_island_check, $pdo) {
    $base_fee = 0;
    $remote_fee = 0;
    
    if ($region_id) {
        $postage_sql = $pdo->prepare('SELECT postage_fee FROM postage WHERE region_id = ? AND start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) ORDER BY start_date DESC LIMIT 1');
        $postage_sql->bindParam(1, $region_id, PDO::PARAM_INT);
        $postage_sql->execute();
        $postage_info = $postage_sql->fetch();
        if ($postage_info) {
            $base_fee = $postage_info['postage_fee'];
        }
    }
    
    if ($remote_island_check) {
        $remote_sql = $pdo->prepare('SELECT remote_island_fee FROM postage_remote_island WHERE start_date <= CURDATE() AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY start_date DESC LIMIT 1');
        $remote_sql->execute();
        $remote_info = $remote_sql->fetch();
        if ($remote_info) {
            $remote_fee = $remote_info['remote_island_fee'];
        }
    }
    
    return $base_fee + $remote_fee;
}

// 送料無料基準額を取得
$free_shipping_threshold = 0;
$free_shipping_sql = $pdo->prepare('SELECT postage_fee_free FROM postage_free WHERE start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) AND del_kbn = 0 ORDER BY start_date DESC LIMIT 1');
$free_shipping_sql->execute();
$free_shipping_info = $free_shipping_sql->fetch();
if ($free_shipping_info) {
    $free_shipping_threshold = $free_shipping_info['postage_fee_free'];
}

// カート内商品の合計金額を計算
$total_amount = 0;
foreach ($_SESSION['product'] as $product_data) {
    $total_amount += $product_data['price'] * $product_data['count'];
}
?>

<div class="shipping-select-container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-shipping-fast"></i>
            配送先の選択
        </h1>
        <p class="page-description">お届け先を選択してください</p>
    </div>

    <form method="POST" action="purchase-shipping-update.php" class="shipping-form">
        <div class="address-list">
            <?php foreach ($shipping_addresses as $address): ?>
                <?php
                $shipping_fee = calculateShippingFee($address['region_id'], $address['remote_island_check'], $pdo);
                $is_free_shipping = ($free_shipping_threshold > 0 && $total_amount >= $free_shipping_threshold);
                $final_shipping_fee = $is_free_shipping ? 0 : $shipping_fee;
                ?>
                
                <div class="address-option">
                    <label class="address-card <?= $address['id'] == $selected_address_id ? 'selected' : '' ?>">
                        <input type="radio" 
                               name="shipping_address_id" 
                               value="<?= $address['id'] ?>"
                               <?= $address['id'] == $selected_address_id ? 'checked' : '' ?>>
                        
                        <div class="card-content">
                            <div class="address-header">
                                <h3 class="address-name"><?= h($address['address_name']) ?></h3>
                                <?php if ($address['is_default']): ?>
                                    <span class="default-badge">
                                        <i class="fas fa-star"></i>
                                        デフォルト
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="address-details">
                                <div class="recipient-info">
                                    <i class="fas fa-user"></i>
                                    <span><?= h($address['recipient_name']) ?></span>
                                </div>
                                
                                <?php if ($address['phone']): ?>
                                    <div class="phone-info">
                                        <i class="fas fa-phone"></i>
                                        <span><?= h($address['phone']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="address-info">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div class="address-text">
                                        <?php if ($address['postal_code']): ?>
                                            <div>〒<?= h($address['postal_code']) ?></div>
                                        <?php endif; ?>
                                        <div><?= h($address['prefecture']) ?><?= h($address['city']) ?></div>
                                        <div><?= h($address['address_line1']) ?></div>
                                        <?php if ($address['address_line2']): ?>
                                            <div><?= h($address['address_line2']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($address['remote_island_check']): ?>
                                    <div class="remote-island-notice">
                                        <i class="fas fa-island-tropical"></i>
                                        <span>離島配送</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="shipping-fee-display">
                                <div class="fee-label">配送料:</div>
                                <div class="fee-amount">
                                    <?php if ($is_free_shipping): ?>
                                        <span class="free-label">無料</span>
                                    <?php else: ?>
                                        ¥<?= number_format($final_shipping_fee) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </label>
                    
                    <div class="address-actions">
                        <a href="shipping-address-edit.php?id=<?= $address['id'] ?>" class="btn-edit">
                            <i class="fas fa-edit"></i>
                            編集
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="add-address-option">
            <a href="shipping-address-add.php" class="btn btn-outline">
                <i class="fas fa-plus"></i>
                新しい配送先を追加
            </a>
        </div>
        
        <div class="form-actions">
            <a href="cart-show.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                カートに戻る
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-arrow-right"></i>
                この配送先で続ける
            </button>
        </div>
    </form>
</div>

<style>
.shipping-select-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e5e7eb;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.page-description {
    color: #6b7280;
    font-size: 1.1rem;
}

.address-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-bottom: 30px;
}

.address-option {
    position: relative;
}

.address-card {
    display: block;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.address-card:hover {
    border-color: #2563eb;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
}

.address-card.selected {
    border-color: #2563eb;
    background: #f0f9ff;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.address-card input[type="radio"] {
    display: none;
}

.card-content {
    width: 100%;
}

.address-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.address-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.default-badge {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 5px;
}

.address-details {
    margin-bottom: 15px;
}

.recipient-info, .phone-info, .address-info {
    display: flex;
    gap: 10px;
    margin-bottom: 8px;
}

.recipient-info i, .phone-info i {
    color: #6b7280;
    width: 16px;
    margin-top: 2px;
}

.address-info {
    align-items: flex-start;
}

.address-info i {
    color: #ef4444;
    width: 16px;
    margin-top: 2px;
}

.address-text {
    flex: 1;
    line-height: 1.4;
}

.remote-island-notice {
    display: flex;
    align-items: center;
    gap: 5px;
    background: #fef3c7;
    color: #92400e;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
    width: fit-content;
}

.shipping-fee-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #e5e7eb;
    font-weight: 600;
}

.fee-label {
    color: #6b7280;
}

.fee-amount {
    color: #1f2937;
    font-size: 1.1rem;
}

.free-label {
    background: #dcfce7;
    color: #16a34a;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 0.9rem;
}

.address-actions {
    position: absolute;
    top: 15px;
    right: 15px;
}

.btn-edit {
    background: #f3f4f6;
    color: #6b7280;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: all 0.3s;
}

.btn-edit:hover {
    background: #e5e7eb;
    color: #374151;
}

.add-address-option {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    border: 2px dashed #d1d5db;
    border-radius: 12px;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    gap: 20px;
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
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
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

@media (max-width: 768px) {
    .shipping-select-container {
        padding: 15px;
    }
    
    .address-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .address-actions {
        position: static;
        margin-top: 15px;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioButtons = document.querySelectorAll('input[name="shipping_address_id"]');
    const addressCards = document.querySelectorAll('.address-card');
    
    // ラジオボタンの変更イベント
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            updateSelectedCard(this.value);
        });
    });
    
    // カードクリックでラジオボタンを選択
    addressCards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio && !radio.checked) {
                radio.checked = true;
                updateSelectedCard(radio.value);
            }
        });
    });
    
    function updateSelectedCard(selectedId) {
        // すべてのカードから選択状態を削除
        addressCards.forEach(card => {
            card.classList.remove('selected');
            
            // スムーズなトランジション効果
            card.style.transform = 'scale(1)';
            card.style.borderColor = '#e5e7eb';
            card.style.background = 'white';
            card.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.05)';
        });
        
        // 選択されたカードにスタイルを適用
        const selectedCard = document.querySelector(`input[value="${selectedId}"]`).closest('.address-card');
        if (selectedCard) {
            selectedCard.classList.add('selected');
            
            // アニメーション効果
            selectedCard.style.transform = 'scale(1.02)';
            selectedCard.style.borderColor = '#2563eb';
            selectedCard.style.background = '#f0f9ff';
            selectedCard.style.boxShadow = '0 8px 25px rgba(37, 99, 235, 0.15)';
            
            // 少し遅れてスケールを元に戻す
            setTimeout(() => {
                selectedCard.style.transform = 'scale(1)';
            }, 200);
            
            // 選択されたカードを画面内にスクロール
            selectedCard.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
    }
    
    // 初期状態で選択されているカードを設定
    const checkedRadio = document.querySelector('input[name="shipping_address_id"]:checked');
    if (checkedRadio) {
        updateSelectedCard(checkedRadio.value);
    }
    
    // キーボードナビゲーション対応
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            const currentSelected = document.querySelector('input[name="shipping_address_id"]:checked');
            if (!currentSelected) return;
            
            const allRadios = Array.from(radioButtons);
            const currentIndex = allRadios.indexOf(currentSelected);
            let nextIndex;
            
            if (e.key === 'ArrowDown') {
                nextIndex = (currentIndex + 1) % allRadios.length;
            } else {
                nextIndex = currentIndex === 0 ? allRadios.length - 1 : currentIndex - 1;
            }
            
            const nextRadio = allRadios[nextIndex];
            nextRadio.checked = true;
            updateSelectedCard(nextRadio.value);
            
            e.preventDefault();
        }
    });
});
</script>

<?php require 'footer.php'; ?>