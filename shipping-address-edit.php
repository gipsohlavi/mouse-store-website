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

// 編集対象のIDチェック
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: shipping-address-list.php');
    exit;
}

$address_id = (int)$_GET['id'];

// 既存データを取得
$sql = $pdo->prepare('SELECT * FROM shipping_addresses WHERE id = ? AND customer_id = ?');
$sql->bindParam(1, $address_id);
$sql->bindParam(2, $customer_id);
$sql->execute();
$address_data = $sql->fetch();

if (!$address_data) {
    header('Location: shipping-address-list.php');
    exit;
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

<div class="address-edit-container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-edit"></i>
            配送先の編集
        </h1>
        <p class="page-description">
            「<?= h($address_data['address_name']) ?>」の配送先情報を編集します
        </p>
        <div class="breadcrumb">
            <a href="shipping-address-list.php">
                <i class="fas fa-list"></i>
                配送先一覧
            </a>
            <i class="fas fa-chevron-right"></i>
            <span>編集</span>
        </div>
    </div>

    <form method="POST" action="shipping-address-output.php" class="address-form" id="editForm">
        <input type="hidden" name="address_id" value="<?= $address_data['id'] ?>">
        
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
                           value="<?= h($address_data['address_name']) ?>"
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
                           value="<?= h($address_data['recipient_name']) ?>"
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
                    </label>
                    <input type="text" 
                           id="postal_code" 
                           name="postal_code" 
                           class="form-input"
                           value="<?= h($address_data['postal_code']) ?>"
                           placeholder="123-4567"
                           pattern="[0-9]{3}-[0-9]{4}">
                    <small class="form-help">ハイフン付きで入力してください（例：123-4567）</small>
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
                                    <?= $address_data['prefecture'] == $pref ? 'selected' : '' ?>>
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
                           value="<?= h($address_data['city']) ?>"
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
                           value="<?= h($address_data['address_line1']) ?>"
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
                           value="<?= h($address_data['address_line2']) ?>"
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
                           value="<?= h($address_data['phone']) ?>"
                           placeholder="例：090-1234-5678">
                    <small class="form-help">配送時の連絡用（任意）</small>
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
                               <?= $address_data['is_default'] ? 'checked' : '' ?>>
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
                               <?= $address_data['remote_island_check'] ? 'checked' : '' ?>>
                        <span class="checkbox-custom"></span>
                        <div class="option-content">
                            <strong>離島への配送</strong>
                            <small>離島・遠隔地への配送の場合はチェックしてください</small>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- 変更履歴表示 -->
        <div class="form-section info-section">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i>
                配送先情報
            </h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <label>登録日時</label>
                    <span><?= date('Y年m月d日 H:i', strtotime($address_data['created_at'])) ?></span>
                </div>
                
                <div class="info-item">
                    <label>最終更新</label>
                    <span><?= date('Y年m月d日 H:i', strtotime($address_data['updated_at'])) ?></span>
                </div>
                
                <?php if ($address_data['is_default']): ?>
                    <div class="info-item">
                        <label>ステータス</label>
                        <span class="status-badge default">
                            <i class="fas fa-star"></i>
                            デフォルト配送先
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <div class="action-group">
                <a href="shipping-address-list.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    一覧に戻る
                </a>
                <button type="button" onclick="resetForm()" class="btn btn-secondary">
                    <i class="fas fa-undo"></i>
                    リセット
                </button>
            </div>
            
            <div class="action-group">
                <?php if (!$address_data['is_default']): ?>
                    <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        削除
                    </button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    変更を保存
                </button>
            </div>
        </div>
    </form>
</div>

<!-- 削除確認モーダル -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>配送先削除の確認</h3>
            <button class="modal-close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h4>この配送先を削除しますか？</h4>
            <div class="delete-preview">
                <strong><?= h($address_data['address_name']) ?></strong>
                <p><?= h($address_data['recipient_name']) ?></p>
                <p><?= h($address_data['prefecture']) ?><?= h($address_data['city']) ?><?= h($address_data['address_line1']) ?></p>
            </div>
            <p class="warning-text">削除した配送先は元に戻せません。</p>
        </div>
        <div class="modal-footer">
            <button onclick="closeDeleteModal()" class="btn btn-outline">キャンセル</button>
            <button onclick="executeDelete()" class="btn btn-danger">削除する</button>
        </div>
    </div>
</div>

<style>
.address-edit-container {
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
    margin-bottom: 15px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #6b7280;
    font-size: 0.9rem;
}

.breadcrumb a {
    color: #2563eb;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: color 0.3s;
}

.breadcrumb a:hover {
    color: #1d4ed8;
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

.info-section {
    background: #f8fafc;
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

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-item label {
    font-weight: 500;
    color: #6b7280;
    font-size: 0.9rem;
}

.info-item span {
    color: #1f2937;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-badge.default {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 30px;
    background: #f8fafc;
}

.action-group {
    display: flex;
    gap: 15px;
}

.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
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

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* モーダル */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 16px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 30px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    margin: 0;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    cursor: pointer;
    color: #6b7280;
    font-size: 1.25rem;
    padding: 5px;
}

.modal-body {
    padding: 30px;
    text-align: center;
}

.warning-icon {
    font-size: 3rem;
    color: #f59e0b;
    margin-bottom: 20px;
}

.modal-body h4 {
    color: #1f2937;
    margin-bottom: 20px;
}

.delete-preview {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 0;
    text-align: left;
}

.delete-preview strong {
    color: #991b1b;
    display: block;
    margin-bottom: 5px;
}

.delete-preview p {
    color: #7f1d1d;
    margin: 3px 0;
    font-size: 0.9rem;
}

.warning-text {
    color: #ef4444;
    font-size: 0.9rem;
    font-weight: 500;
    margin-top: 15px;
}

.modal-footer {
    display: flex;
    gap: 15px;
    justify-content: center;
    padding: 25px 30px;
    border-top: 1px solid #e5e7eb;
}

/* レスポンシブ */
@media (max-width: 768px) {
    .address-edit-container {
        padding: 15px;
    }
    
    .form-section {
        padding: 25px 20px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 20px;
        padding: 25px 20px;
    }
    
    .action-group {
        width: 100%;
        justify-content: center;
    }
    
    .page-title {
        font-size: 1.5rem;
        flex-direction: column;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .btn {
        justify-content: center;
        padding: 15px 20px;
    }
    
    .action-group {
        flex-direction: column;
    }
}
</style>

<script>
// フォームの初期データを保存
const initialFormData = new FormData(document.getElementById('editForm'));

// リセット機能
function resetForm() {
    if (confirm('入力内容をリセットしますか？未保存の変更は失われます。')) {
        // 各フィールドを初期値に戻す
        document.getElementById('address_name').value = "<?= h($address_data['address_name']) ?>";
        document.getElementById('recipient_name').value = "<?= h($address_data['recipient_name']) ?>";
        document.getElementById('postal_code').value = "<?= h($address_data['postal_code']) ?>";
        document.getElementById('prefecture').value = "<?= h($address_data['prefecture']) ?>";
        document.getElementById('city').value = "<?= h($address_data['city']) ?>";
        document.getElementById('address_line1').value = "<?= h($address_data['address_line1']) ?>";
        document.getElementById('address_line2').value = "<?= h($address_data['address_line2']) ?>";
        document.getElementById('phone').value = "<?= h($address_data['phone']) ?>";
        document.querySelector('input[name="is_default"]').checked = <?= $address_data['is_default'] ? 'true' : 'false' ?>;
        document.querySelector('input[name="remote_island_check"]').checked = <?= $address_data['remote_island_check'] ? 'true' : 'false' ?>;
        
        // エラー状態をクリア
        document.querySelectorAll('.form-input, .form-select').forEach(field => {
            field.style.borderColor = '#e5e7eb';
        });
    }
}

// 削除確認
function confirmDelete() {
    document.getElementById('deleteModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
}

function executeDelete() {
    window.location.href = 'shipping-address-delete.php?id=<?= $address_data['id'] ?>';
}

// フォームバリデーション
document.getElementById('editForm').addEventListener('submit', function(e) {
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
        return false;
    }
    
    // 変更確認
    const currentData = new FormData(this);
    let hasChanges = false;
    
    for (let [key, value] of currentData.entries()) {
        if (key !== 'address_id' && initialFormData.get(key) !== value) {
            hasChanges = true;
            break;
        }
    }
    
    if (hasChanges) {
        return confirm('変更を保存しますか？');
    }
});

// 郵便番号から住所を自動入力（簡易版）
document.getElementById('postal_code').addEventListener('blur', function() {
    const postalCode = this.value.replace('-', '');
    if (postalCode.length === 7) {
        // 実際の実装では郵便番号APIを使用
        console.log('郵便番号検索:', postalCode);
    }
});

// モーダル外クリックで閉じる
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// ページ離脱時の確認
window.addEventListener('beforeunload', function(e) {
    const currentData = new FormData(document.getElementById('editForm'));
    let hasChanges = false;
    
    for (let [key, value] of currentData.entries()) {
        if (key !== 'address_id' && initialFormData.get(key) !== value) {
            hasChanges = true;
            break;
        }
    }
    
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

<?php require 'footer.php'; ?>