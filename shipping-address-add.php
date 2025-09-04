<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>

<?php
// ログインチェック
if (!isset($_SESSION['customer'])) {
    header('Location: login-input.php');
    exit;
}

$customer_id = $_SESSION['customer']['id'];
$is_edit = isset($_GET['id']);
$address_data = [];

// 編集の場合、既存データを取得
if ($is_edit) {
    $address_id = (int)$_GET['id'];
    $sql = $pdo->prepare('SELECT * FROM shipping_addresses WHERE id = ? AND customer_id = ?');
    $sql->bindParam(1, $address_id);
    $sql->bindParam(2, $customer_id);
    $sql->execute();
    $address_data = $sql->fetch();
    
    if (!$address_data) {
        header('Location: shipping-address-list.php');
        exit;
    }
}

// 都道府県リスト
$prefectures = [
    '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
    '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
    '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
    '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
    '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
    '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
    '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
];
?>

<div class="address-form-container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-<?= $is_edit ? 'edit' : 'plus' ?>"></i>
            <?= $is_edit ? '配送先の編集' : '新しい配送先を追加' ?>
        </h1>
        <p class="page-description">
            <?= $is_edit ? '配送先情報を変更します' : '新しい配送先を登録します' ?>
        </p>
    </div>

    <form method="POST" action="shipping-address-output.php" class="address-form">
        <?php if ($is_edit): ?>
            <input type="hidden" name="address_id" value="<?= $address_data['id'] ?>">
        <?php endif; ?>
        
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-tag"></i>
                配送先情報
            </h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="address_name" class="form-label required">
                        <i class="fas fa-tag"></i>
                        配送先名
                    </label>
                    <input type="text" 
                           id="address_name" 
                           name="address_name" 
                           class="form-input"
                           value="<?= h($address_data['address_name'] ?? '') ?>"
                           placeholder="例：自宅、実家、会社など"
                           required>
                    <small class="form-help">配送先を区別するための名前をつけてください</small>
                </div>

                <div class="form-group">
                    <label for="recipient_name" class="form-label required">
                        <i class="fas fa-user"></i>
                        受取人名
                    </label>
                    <input type="text" 
                           id="recipient_name" 
                           name="recipient_name" 
                           class="form-input"
                           value="<?= h($address_data['recipient_name'] ?? $_SESSION['customer']['name'] ?? '') ?>"
                           placeholder="山田 太郎"
                           required>
                    <small class="form-help">商品を受け取る方の氏名を入力してください</small>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-map-marker-alt"></i>
                住所情報
            </h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="postal_code" class="form-label">
                        <i class="fas fa-mail-bulk"></i>
                        郵便番号
                        <span class="auto-fill-badge">自動入力対応</span>
                    </label>
                    <div class="postal-code-group">
                        <input type="text" 
                               id="postal_code" 
                               name="postal_code" 
                               class="form-input"
                               value="<?= h($address_data['postal_code'] ?? '') ?>"
                               placeholder="123-4567 または 1234567"
                               maxlength="8">
                        <button type="button" id="postal_search_btn" class="postal-search-btn">
                            <i class="fas fa-search"></i>
                            住所検索
                        </button>
                    </div>
                    <div id="postal_loading" class="postal-loading" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                        検索中...
                    </div>
                    <div id="postal_result" class="postal-result" style="display: none;"></div>
                    <small class="form-help">郵便番号を入力して「住所検索」をクリックすると、住所が自動入力されます</small>
                </div>

                <div class="form-group">
                    <label for="prefecture" class="form-label required">
                        <i class="fas fa-map"></i>
                        都道府県
                    </label>
                    <select id="prefecture" name="prefecture" class="form-select" required>
                        <option value="">都道府県を選択</option>
                        <?php foreach ($prefectures as $pref): ?>
                            <option value="<?= h($pref) ?>" 
                                    <?= ($address_data['prefecture'] ?? '') == $pref ? 'selected' : '' ?>>
                                <?= h($pref) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="city" class="form-label required">
                        <i class="fas fa-building"></i>
                        市区町村
                    </label>
                    <input type="text" 
                           id="city" 
                           name="city" 
                           class="form-input"
                           value="<?= h($address_data['city'] ?? '') ?>"
                           placeholder="例：渋谷区、横浜市中区"
                           required>
                </div>

                <div class="form-group full-width">
                    <label for="address_line1" class="form-label required">
                        <i class="fas fa-road"></i>
                        住所1（番地・町名）
                    </label>
                    <input type="text" 
                           id="address_line1" 
                           name="address_line1" 
                           class="form-input"
                           value="<?= h($address_data['address_line1'] ?? '') ?>"
                           placeholder="例：神南1-2-3"
                           required>
                </div>

                <div class="form-group full-width">
                    <label for="address_line2" class="form-label">
                        <i class="fas fa-home"></i>
                        住所2（建物名・部屋番号）
                    </label>
                    <input type="text" 
                           id="address_line2" 
                           name="address_line2" 
                           class="form-input"
                           value="<?= h($address_data['address_line2'] ?? '') ?>"
                           placeholder="例：KELOTビル 3階 301号室">
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">
                        <i class="fas fa-phone"></i>
                        電話番号
                    </label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           class="form-input"
                           value="<?= h($address_data['phone'] ?? '') ?>"
                           placeholder="例：090-1234-5678">
                    <small class="form-help">配送時の連絡用（任意）</small>
                </div>
            </div>
            
            <!-- 配送料情報表示エリア -->
            <div id="shipping_fee_info" class="shipping-fee-info" style="display: none;">
                <div class="fee-card">
                    <h4>
                        <i class="fas fa-truck"></i>
                        配送料金情報
                    </h4>
                    <div class="fee-details">
                        <div class="fee-row">
                            <span class="fee-label">配送地域:</span>
                            <span id="region_name" class="fee-value">-</span>
                        </div>
                        <div class="fee-row">
                            <span class="fee-label">基本配送料:</span>
                            <span id="shipping_fee" class="fee-value">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-cog"></i>
                配送オプション
            </h2>
            
            <div class="form-options">
                <div class="option-group">
                    <label class="checkbox-label">
                        <input type="checkbox" 
                               name="is_default" 
                               value="1"
                               <?= ($address_data['is_default'] ?? false) ? 'checked' : '' ?>>
                        <span class="checkbox-custom"></span>
                        <div class="option-content">
                            <strong>デフォルト配送先に設定</strong>
                            <small>購入時に自動的に選択される配送先になります</small>
                        </div>
                    </label>
                </div>

                <div class="option-group">
                    <label class="checkbox-label">
                        <input type="checkbox" 
                               name="remote_island_check" 
                               value="1"
                               id="remote_island_check"
                               <?= ($address_data['remote_island_check'] ?? false) ? 'checked' : '' ?>>
                        <span class="checkbox-custom"></span>
                        <div class="option-content">
                            <strong>離島への配送</strong>
                            <small>離島・遠隔地への配送の場合はチェックしてください（追加料金が発生する場合があります）</small>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" onclick="history.back()" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                キャンセル
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-<?= $is_edit ? 'save' : 'plus' ?>"></i>
                <?= $is_edit ? '変更を保存' : '配送先を追加' ?>
            </button>
        </div>
    </form>
</div>

<style>
.address-form-container {
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

.address-form {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.form-section {
    padding: 30px;
    border-bottom: 1px solid #f3f4f6;
}

.form-section:last-of-type {
    border-bottom: none;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.form-label.required::after {
    content: '*';
    color: #ef4444;
    margin-left: 4px;
}

.auto-fill-badge {
    background: #dbeafe;
    color: #1d4ed8;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 8px;
}

.postal-code-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.postal-search-btn {
    background: #2563eb;
    color: white;
    border: none;
    padding: 12px 16px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s;
    white-space: nowrap;
}

.postal-search-btn:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.postal-search-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
}

.postal-loading {
    color: #2563eb;
    font-size: 0.9rem;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.postal-result {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    padding: 12px;
    margin-top: 8px;
    color: #15803d;
    font-size: 0.9rem;
}

.shipping-fee-info {
    margin-top: 25px;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 200px;
    }
}

.fee-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
}

.fee-card h4 {
    color: #1f2937;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.fee-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.fee-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.fee-label {
    color: #6b7280;
}

.fee-value {
    font-weight: 600;
    color: #1f2937;
}

.form-input, .form-select {
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-help {
    color: #6b7280;
    font-size: 0.8rem;
    margin-top: 5px;
}

.form-options {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.option-group {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    background: white;
    position: relative;
    transition: all 0.3s ease;
    flex-shrink: 0;
    margin-top: 2px;
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
    background: #2563eb;
    border-color: #2563eb;
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 6px;
    width: 6px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.option-content {
    flex: 1;
}

.option-content strong {
    display: block;
    color: #1f2937;
    margin-bottom: 5px;
}

.option-content small {
    color: #6b7280;
    line-height: 1.4;
}

.form-actions {
    display: flex;
    gap: 20px;
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

/* レスポンシブ */
@media (max-width: 768px) {
    .address-form-container {
        padding: 15px;
    }
    
    .form-section {
        padding: 25px 20px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .postal-code-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-actions {
        flex-direction: column;
        padding: 25px 20px;
    }
    
    .page-title {
        font-size: 1.5rem;
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .form-actions {
        gap: 15px;
    }
    
    .btn {
        justify-content: center;
        padding: 15px 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const postalCodeInput = document.getElementById('postal_code');
    const searchButton = document.getElementById('postal_search_btn');
    const loadingDiv = document.getElementById('postal_loading');
    const resultDiv = document.getElementById('postal_result');
    const shippingFeeInfo = document.getElementById('shipping_fee_info');
    
    // 郵便番号の自動フォーマット
    postalCodeInput.addEventListener('input', function() {
        let value = this.value.replace(/[^\d]/g, ''); // 数字以外を削除
        
        if (value.length > 3) {
            value = value.substring(0, 3) + '-' + value.substring(3, 7);
        }
        
        this.value = value;
        
        // 結果をクリア
        hideResults();
    });
    
    // Enterキーで検索
    postalCodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchPostalCode();
        }
    });
    
    // 検索ボタンクリック
    searchButton.addEventListener('click', searchPostalCode);
    
    function searchPostalCode() {
        const postalCode = postalCodeInput.value.replace(/[^\d]/g, '');
        
        if (postalCode.length !== 7) {
            showError('郵便番号は7桁の数字で入力してください');
            return;
        }
        
        // UI状態を更新
        searchButton.disabled = true;
        loadingDiv.style.display = 'flex';
        hideResults();
        
        // APIリクエスト
        const formData = new FormData();
        formData.append('postcode', postalCode);
        
        fetch('postcode-api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fillAddressFields(data);
                showSuccess('住所を自動入力しました');
                showShippingFeeInfo(data.region);
            } else {
                showError(data.error || '住所の取得に失敗しました');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('通信エラーが発生しました');
        })
        .finally(() => {
            searchButton.disabled = false;
            loadingDiv.style.display = 'none';
        });
    }
    
    function fillAddressFields(data) {
        // 郵便番号をフォーマット
        postalCodeInput.value = data.postcode_formatted;
        
        // 都道府県を選択
        const prefectureSelect = document.getElementById('prefecture');
        prefectureSelect.value = data.address.prefecture;
        
        // 市区町村を入力
        const cityInput = document.getElementById('city');
        cityInput.value = data.address.city;
        
        // 住所1に町名を入力（既存の値があれば結合）
        const addressLine1Input = document.getElementById('address_line1');
        const currentValue = addressLine1Input.value.trim();
        const newTown = data.address.town;
        
        if (newTown && !currentValue.includes(newTown)) {
            addressLine1Input.value = newTown + (currentValue ? ' ' + currentValue : '');
        }
        
        // フォーカスを住所1に移動
        addressLine1Input.focus();
        addressLine1Input.setSelectionRange(addressLine1Input.value.length, addressLine1Input.value.length);
    }
    
    function showShippingFeeInfo(regionData) {
        document.getElementById('region_name').textContent = regionData.region_name;
        document.getElementById('shipping_fee').textContent = '¥' + regionData.shipping_fee_formatted;
        shippingFeeInfo.style.display = 'block';
    }
    
    function showSuccess(message) {
        resultDiv.className = 'postal-result';
        resultDiv.innerHTML = `
            <i class="fas fa-check-circle"></i>
            ${message}
        `;
        resultDiv.style.display = 'block';
        
        // 3秒後に非表示
        setTimeout(() => {
            resultDiv.style.display = 'none';
        }, 3000);
    }
    
    function showError(message) {
        resultDiv.className = 'postal-result error';
        resultDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i>
            ${message}
        `;
        resultDiv.style.display = 'block';
        resultDiv.style.background = '#fef2f2';
        resultDiv.style.borderColor = '#fecaca';
        resultDiv.style.color = '#dc2626';
        
        // 5秒後に非表示
        setTimeout(() => {
            resultDiv.style.display = 'none';
        }, 5000);
    }
    
    function hideResults() {
        resultDiv.style.display = 'none';
        shippingFeeInfo.style.display = 'none';
    }
    
    // フォームバリデーション
    document.querySelector('.address-form').addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = '#ef4444';
                isValid = false;
            } else {
                field.style.borderColor = '#e5e7eb';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('必須項目をすべて入力してください。');
        }
    });
});
</script>

<?php require 'footer.php'; ?>