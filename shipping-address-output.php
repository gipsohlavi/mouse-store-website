<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php
// ログインチェック
if (!isset($_SESSION['customer'])) {
    header('Location: login-input.php');
    exit;
}

$customer_id = $_SESSION['customer']['id'];
$is_edit = !empty($_POST['address_id']);
$success = false;
$error_message = '';

// POSTデータを変数に格納（表示用）
$address_name = '';
$recipient_name = '';
$prefecture = '';
$city = '';
$address_line1 = '';
$address_line2 = '';
$postal_code = '';
$phone = '';
$is_default = false;
$remote_island_check = false;

try {
    // 入力データの検証
    $address_name = trim($_POST['address_name']);
    $recipient_name = trim($_POST['recipient_name']);
    $prefecture = $_POST['prefecture'];
    $city = trim($_POST['city']);
    $address_line1 = trim($_POST['address_line1']);
    $address_line2 = trim($_POST['address_line2']);
    $postal_code = trim($_POST['postal_code']);
    $phone = trim($_POST['phone']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $remote_island_check = isset($_POST['remote_island_check']) ? 1 : 0;

    // 必須項目チェック
    if (empty($address_name) || empty($recipient_name) || empty($prefecture) || empty($city) || empty($address_line1)) {
        throw new Exception('必須項目が入力されていません。');
    }

    // 配送先名の重複チェック（同一顧客内）
    $duplicate_check_sql = $pdo->prepare('SELECT id FROM shipping_addresses WHERE customer_id = ? AND address_name = ?' . ($is_edit ? ' AND id != ?' : ''));
    $duplicate_check_sql->bindParam(1, $customer_id);
    $duplicate_check_sql->bindParam(2, $address_name);
    if ($is_edit) {
        $address_id = (int)$_POST['address_id'];
        $duplicate_check_sql->bindParam(3, $address_id);
    }
    $duplicate_check_sql->execute();
    
    if ($duplicate_check_sql->fetch()) {
        throw new Exception('同じ配送先名が既に登録されています。別の名前を選択してください。');
    }

    // 地域IDの取得
    $region_id = 1; // デフォルト値
    $sql = $pdo->prepare('SELECT region_id FROM region WHERE prefectures_id = ?');
    $sql->bindParam(1, $prefecture);
    $sql->execute();
    $region_row = $sql->fetch();
    if ($region_row) {
        $region_id = $region_row['region_id'];
    }

    $pdo->beginTransaction();

    if ($is_edit) {
        // 編集処理
        $address_id = (int)$_POST['address_id'];
        
        // 権限チェック
        $sql = $pdo->prepare('SELECT customer_id, is_default FROM shipping_addresses WHERE id = ?');
        $sql->bindParam(1, $address_id);
        $sql->execute();
        $owner = $sql->fetch();
        
        if (!$owner || $owner['customer_id'] != $customer_id) {
            throw new Exception('この配送先を編集する権限がありません。');
        }

        // デフォルト配送先の処理
        if ($is_default) {
            // 他の配送先のデフォルトを解除
            $sql = $pdo->prepare('UPDATE shipping_addresses SET is_default = 0 WHERE customer_id = ? AND id != ?');
            $sql->bindParam(1, $customer_id);
            $sql->bindParam(2, $address_id);
            $sql->execute();
        } else {
            // 現在のデフォルト配送先を非デフォルトにしようとしている場合のチェック
            if ($owner['is_default']) {
                // 他にデフォルト配送先があるかチェック
                $sql = $pdo->prepare('SELECT COUNT(*) FROM shipping_addresses WHERE customer_id = ? AND id != ?');
                $sql->bindParam(1, $customer_id);
                $sql->bindParam(2, $address_id);
                $sql->execute();
                $other_count = $sql->fetchColumn();
                
                if ($other_count > 0) {
                    // 他に配送先がある場合は、最も古い配送先をデフォルトにする
                    $sql = $pdo->prepare('UPDATE shipping_addresses SET is_default = 1 WHERE customer_id = ? AND id != ? ORDER BY created_at LIMIT 1');
                    $sql->bindParam(1, $customer_id);
                    $sql->bindParam(2, $address_id);
                    $sql->execute();
                } else {
                    // 他に配送先がない場合は、デフォルトを維持
                    $is_default = 1;
                }
            }
        }

        // 配送先情報の更新
        $sql = $pdo->prepare('
            UPDATE shipping_addresses SET 
                address_name = ?, 
                recipient_name = ?, 
                postal_code = ?, 
                prefecture = ?, 
                city = ?, 
                address_line1 = ?, 
                address_line2 = ?, 
                phone = ?, 
                is_default = ?, 
                remote_island_check = ?, 
                region_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND customer_id = ?
        ');
        
        $sql->execute([
            $address_name, $recipient_name, $postal_code, $prefecture, $city,
            $address_line1, $address_line2, $phone, $is_default, $remote_island_check,
            $region_id, $address_id, $customer_id
        ]);
        
    } else {
        // 新規追加処理
        
        // デフォルト配送先の処理
        if ($is_default) {
            // 他の配送先のデフォルトを解除
            $sql = $pdo->prepare('UPDATE shipping_addresses SET is_default = 0 WHERE customer_id = ?');
            $sql->bindParam(1, $customer_id);
            $sql->execute();
        } else {
            // 最初の配送先の場合は自動的にデフォルトにする
            $sql = $pdo->prepare('SELECT COUNT(*) FROM shipping_addresses WHERE customer_id = ?');
            $sql->bindParam(1, $customer_id);
            $sql->execute();
            $count = $sql->fetchColumn();
            
            if ($count == 0) {
                $is_default = 1;
            }
        }

        // 新規配送先の挿入
        $sql = $pdo->prepare('
            INSERT INTO shipping_addresses (
                customer_id, address_name, recipient_name, postal_code, 
                prefecture, city, address_line1, address_line2, phone, 
                is_default, remote_island_check, region_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $sql->execute([
            $customer_id, $address_name, $recipient_name, $postal_code, $prefecture,
            $city, $address_line1, $address_line2, $phone, $is_default,
            $remote_island_check, $region_id
        ]);
    }

    $pdo->commit();
    $success = true;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error_message = $e->getMessage();
}
?>

<?php require 'header.php'; ?>

<div class="result-page">
    <div class="result-container">
        <?php if ($success): ?>
            <!-- 成功パターン -->
            <div class="result-header success">
                <div class="result-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="result-title">
                    <?= $is_edit ? '配送先の更新完了' : '配送先の追加完了' ?>
                </h1>
                <p class="result-subtitle">
                    <?= $is_edit ? '配送先情報が正常に更新されました' : '新しい配送先が正常に追加されました' ?>
                </p>
            </div>

            <div class="result-content">
                <div class="address-preview">
                    <h3>保存された配送先情報</h3>
                    <div class="preview-card">
                        <div class="preview-header">
                            <h4><?= h($address_name) ?></h4>
                            <?php if ($is_default): ?>
                                <span class="default-badge">
                                    <i class="fas fa-star"></i>
                                    デフォルト
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="preview-content">
                            <div class="preview-row">
                                <i class="fas fa-user"></i>
                                <span><?= h($recipient_name) ?></span>
                            </div>
                            
                            <?php if ($phone): ?>
                                <div class="preview-row">
                                    <i class="fas fa-phone"></i>
                                    <span><?= h($phone) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="preview-row">
                                <i class="fas fa-map-marker-alt"></i>
                                <div class="address-text">
                                    <?php if ($postal_code): ?>
                                        <div>〒<?= h($postal_code) ?></div>
                                    <?php endif; ?>
                                    <div><?= h($prefecture) ?><?= h($city) ?></div>
                                    <div><?= h($address_line1) ?></div>
                                    <?php if ($address_line2): ?>
                                        <div><?= h($address_line2) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($remote_island_check): ?>
                                <div class="preview-row">
                                    <i class="fas fa-island-tropical"></i>
                                    <span>離島配送</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="next-actions">
                    <h3>次のアクション</h3>
                    <div class="action-cards">
                        <div class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-list"></i>
                            </div>
                            <div class="action-content">
                                <h4>配送先一覧</h4>
                                <p>登録されている配送先を確認・管理</p>
                                <a href="shipping-address-list.php" class="action-link">
                                    一覧を見る <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="action-content">
                                <h4>お買い物</h4>
                                <p>新しい配送先でお買い物を始める</p>
                                <a href="product.php" class="action-link">
                                    商品を見る <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-actions">
                    <a href="shipping-address-list.php" class="btn btn-primary">
                        <i class="fas fa-list"></i>
                        配送先一覧に戻る
                    </a>
                    <a href="shipping-address-add.php" class="btn btn-outline">
                        <i class="fas fa-plus"></i>
                        別の配送先を追加
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- エラーパターン -->
            <div class="result-header error">
                <div class="result-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1 class="result-title">処理に失敗しました</h1>
                <p class="result-subtitle">配送先の保存中にエラーが発生しました</p>
            </div>

            <div class="result-content">
                <div class="error-section">
                    <div class="error-message">
                        <div class="error-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="error-content">
                            <h3>エラー内容</h3>
                            <p><?= h($error_message) ?></p>
                        </div>
                    </div>

                    <div class="error-help">
                        <h4>解決方法</h4>
                        <ul>
                            <li>必須項目がすべて入力されているか確認してください</li>
                            <li>入力内容に不正な文字が含まれていないか確認してください</li>
                            <li>問題が続く場合は、カスタマーサポートにお問い合わせください</li>
                        </ul>
                    </div>
                </div>

                <div class="error-actions">
                    <button onclick="history.back()" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i>
                        前の画面に戻る
                    </button>
                    <a href="shipping-address-list.php" class="btn btn-outline">
                        <i class="fas fa-list"></i>
                        配送先一覧
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.result-page {
    min-height: calc(100vh - 160px);
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 40px 20px;
}

.result-container {
    max-width: 800px;
    margin: 0 auto;
}

.result-header {
    text-align: center;
    margin-bottom: 40px;
}

.result-header.success {
    color: #10b981;
}

.result-header.error {
    color: #ef4444;
}

.result-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 2.5rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.result-header.success .result-icon {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.result-header.error .result-icon {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.result-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 10px;
}

.result-subtitle {
    font-size: 1.1rem;
    color: #6b7280;
}

.result-content {
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.address-preview {
    padding: 30px;
    border-bottom: 1px solid #f3f4f6;
}

.address-preview h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 20px;
}

.preview-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.preview-header h4 {
    font-size: 1.1rem;
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
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.preview-row {
    display: flex;
    gap: 12px;
    margin-bottom: 10px;
}

.preview-row i {
    color: #6b7280;
    width: 20px;
    flex-shrink: 0;
    margin-top: 2px;
}

.address-text {
    flex: 1;
    line-height: 1.5;
}

.next-actions {
    padding: 30px;
    border-bottom: 1px solid #f3f4f6;
}

.next-actions h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 20px;
}

.action-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.action-card {
    display: flex;
    gap: 15px;
    padding: 20px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.action-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.action-content h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.action-content p {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.action-link {
    color: #2563eb;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: color 0.3s;
}

.action-link:hover {
    color: #1d4ed8;
}

.main-actions {
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

/* エラースタイル */
.error-section {
    padding: 30px;
}

.error-message {
    display: flex;
    gap: 20px;
    padding: 20px;
    background: #fef2f2;
    border-radius: 12px;
    border-left: 4px solid #ef4444;
    margin-bottom: 25px;
}

.error-message .error-icon {
    width: 50px;
    height: 50px;
    background: #ef4444;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.error-content h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 10px;
}

.error-content p {
    color: #991b1b;
    line-height: 1.5;
}

.error-help {
    background: #fffbeb;
    padding: 20px;
    border-radius: 12px;
    border-left: 4px solid #f59e0b;
}

.error-help h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 15px;
}

.error-help ul {
    margin: 0 0 0 20px;
    color: #92400e;
}

.error-help li {
    margin-bottom: 8px;
    line-height: 1.5;
}

.error-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    padding: 25px 30px;
    background: #f8fafc;
    border-top: 1px solid #f3f4f6;
}

/* レスポンシブ */
@media (max-width: 768px) {
    .result-page {
        padding: 20px 10px;
    }
    
    .main-actions, .error-actions {
        flex-direction: column;
        padding: 25px 20px;
    }
    
    .action-cards {
        grid-template-columns: 1fr;
    }
    
    .result-title {
        font-size: 1.5rem;
    }
}
</style>

<?php require 'footer.php'; ?>