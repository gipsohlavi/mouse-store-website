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

// 配送先一覧取得
$sql = $pdo->prepare('SELECT * FROM shipping_addresses WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC');
$sql->bindParam(1, $customer_id);
$sql->execute();
$addresses = $sql->fetchAll();
?>

<div class="address-management-container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-map-marker-alt"></i>
            配送先住所の管理
        </h1>
        <p class="page-description">登録された配送先住所の確認・編集・削除ができます</p>
    </div>

    <div class="address-list-section">
        <div class="section-header">
            <h2>登録済み配送先</h2>
            <a href="shipping-address-add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                新しい配送先を追加
            </a>
        </div>

        <?php if (empty($addresses)): ?>
            <div class="empty-addresses">
                <div class="empty-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3>配送先が登録されていません</h3>
                <p>配送先を追加して、お買い物をより便利にしましょう</p>
                <a href="shipping-address-add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    最初の配送先を追加
                </a>
            </div>
        <?php else: ?>
            <div class="address-grid">
                <?php foreach ($addresses as $address): ?>
                    <div class="address-card <?= $address['is_default'] ? 'default-address' : '' ?>">
                        <?php if ($address['is_default']): ?>
                            <div class="default-badge">
                                <i class="fas fa-star"></i>
                                デフォルト
                            </div>
                        <?php endif; ?>
                        
                        <div class="address-header">
                            <h3 class="address-name"><?= h($address['address_name']) ?></h3>
                            <div class="address-actions">
                                <a href="shipping-address-edit.php?id=<?= $address['id'] ?>" class="action-btn edit-btn" title="編集">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (!$address['is_default']): ?>
                                    <button onclick="deleteAddress(<?= $address['id'] ?>)" class="action-btn delete-btn" title="削除">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="address-content">
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
                        
                        <div class="address-footer">
                            <?php if (!$address['is_default']): ?>
                                <button onclick="setDefaultAddress(<?= $address['id'] ?>)" class="btn btn-outline btn-sm">
                                    <i class="fas fa-star"></i>
                                    デフォルトに設定
                                </button>
                            <?php endif; ?>
                            
                            <div class="address-meta">
                                <small>登録日: <?= date('Y/m/d', strtotime($address['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 削除確認モーダル -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>配送先の削除確認</h3>
            <button class="modal-close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <p>この配送先を削除してもよろしいですか？</p>
            <p class="warning-text">削除した配送先は元に戻せません。</p>
        </div>
        <div class="modal-footer">
            <button onclick="closeDeleteModal()" class="btn btn-outline">キャンセル</button>
            <button onclick="confirmDelete()" class="btn btn-danger">削除する</button>
        </div>
    </div>
</div>

<style>
.address-management-container {
    max-width: 1200px;
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

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.section-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
}

.address-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 25px;
}

.address-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    padding: 25px;
    position: relative;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.address-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.default-address {
    border-color: #10b981;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
}

.default-badge {
    position: absolute;
    top: -1px;
    right: -1px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 8px 15px;
    border-radius: 0 14px 0 14px;
    font-size: 0.875rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.address-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.address-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.address-actions {
    display: flex;
    gap: 10px;
}

.action-btn {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    text-decoration: none;
}

.edit-btn {
    background: #f0f9ff;
    color: #2563eb;
}

.edit-btn:hover {
    background: #2563eb;
    color: white;
}

.delete-btn {
    background: #fef2f2;
    color: #ef4444;
}

.delete-btn:hover {
    background: #ef4444;
    color: white;
}

.address-content {
    space-y: 15px;
}

.recipient-info, .phone-info, .address-info {
    display: flex;
    gap: 12px;
    margin-bottom: 15px;
}

.recipient-info i, .phone-info i {
    color: #6b7280;
    width: 20px;
    flex-shrink: 0;
    margin-top: 2px;
}

.address-info i {
    color: #ef4444;
    width: 20px;
    flex-shrink: 0;
    margin-top: 2px;
}

.address-text {
    flex: 1;
    line-height: 1.5;
}

.remote-island-notice {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fef3c7;
    color: #92400e;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    margin-top: 15px;
}

.address-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #f3f4f6;
}

.address-meta small {
    color: #9ca3af;
    font-size: 0.8rem;
}

.empty-addresses {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 16px;
    border: 2px dashed #d1d5db;
}

.empty-icon {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 20px;
}

.empty-addresses h3 {
    font-size: 1.5rem;
    color: #1f2937;
    margin-bottom: 10px;
}

.empty-addresses p {
    color: #6b7280;
    margin-bottom: 30px;
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

.modal-close {
    background: none;
    border: none;
    cursor: pointer;
    color: #6b7280;
    font-size: 1.25rem;
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

.warning-text {
    color: #ef4444;
    font-size: 0.9rem;
    margin-top: 10px;
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
    .address-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        gap: 20px;
    }
    
    .address-footer {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
}
</style>

<script>
let addressToDelete = null;

function deleteAddress(id) {
    addressToDelete = id;
    document.getElementById('deleteModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    addressToDelete = null;
}

function confirmDelete() {
    if (addressToDelete) {
        window.location.href = `shipping-address-delete.php?id=${addressToDelete}`;
    }
}

function setDefaultAddress(id) {
    if (confirm('この配送先をデフォルトに設定しますか？')) {
        window.location.href = `shipping-address-default.php?id=${id}`;
    }
}

// モーダル外クリックで閉じる
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php require 'footer.php'; ?>