<?php session_start(); ?>
<?php require 'common.php'; ?>

<?php
$errors = [];
$is_update = isset($_SESSION['customer']);
$customer_id = $is_update ? $_SESSION['customer']['id'] : null;

// 入力データの取得とバリデーション
$name = trim($_POST['name'] ?? '');
$postcode = trim($_POST['postcode'] ?? '');
$prefecture = $_POST['prefecture'] ?? '';
$city = trim($_POST['city'] ?? '');
$address_line1 = trim($_POST['address_line1'] ?? '');
$address_line2 = trim($_POST['address_line2'] ?? '');
$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');
$remote_island_check = isset($_POST['remote_island_check']) ? 1 : 0;

try {
    // バリデーション
    if (empty($name)) {
        $errors[] = 'お名前を入力してください。';
    }
    
    if (empty($postcode)) {
        $errors[] = '郵便番号を入力してください。';
    } elseif (!preg_match('/^\d{3}-\d{4}$/', $postcode)) {
        $errors[] = '郵便番号の形式が正しくありません（例：123-4567）。';
    }
    
    if (empty($prefecture)) {
        $errors[] = '都道府県を選択してください。';
    }
    
    if (empty($city)) {
        $errors[] = '市区町村を入力してください。';
    }
    
    if (empty($address_line1)) {
        $errors[] = '番地・町名を入力してください。';
    }
    
    if (empty($login)) {
        $errors[] = 'ログイン名を入力してください。';
    } elseif (strlen($login) < 3) {
        $errors[] = 'ログイン名は3文字以上で入力してください。';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $login)) {
        $errors[] = 'ログイン名は半角英数字のみで入力してください。';
    }
    
    if (empty($password)) {
        $errors[] = 'パスワードを入力してください。';
    } elseif (strlen($password) < 8) {
        $errors[] = 'パスワードは8文字以上で入力してください。';
    }

    // ログイン名の重複チェック
    $login_check_sql = $pdo->prepare('SELECT id FROM customer WHERE login = ?' . ($is_update ? ' AND id != ?' : ''));
    $login_check_sql->bindParam(1, $login);
    if ($is_update) {
        $login_check_sql->bindParam(2, $customer_id);
    }
    $login_check_sql->execute();
    
    if ($login_check_sql->fetch()) {
        $errors[] = 'このログイン名は既に使用されています。別のログイン名を入力してください。';
    }
    
    // エラーがある場合は入力画面に戻る
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: customer-input.php');
        exit;
    }
    
    // 地域IDの取得
    $region_id = 3; // デフォルト値（関東・中部）
    $region_sql = $pdo->prepare('
        SELECT DISTINCT r.region_id 
        FROM region r 
        JOIN master m ON m.master_id = r.prefectures_id AND m.kbn = 12 
        WHERE m.name = ?
        LIMIT 1
    ');
    $region_sql->bindParam(1, $prefecture);
    $region_sql->execute();
    $region_row = $region_sql->fetch();
    if ($region_row) {
        $region_id = $region_row['region_id'];
    }
    
    $pdo->beginTransaction();
    
    if ($is_update) {
        // 既存顧客の更新処理
        $update_sql = $pdo->prepare('
            UPDATE customer 
            SET name = ?, login = ?, password = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $update_sql->execute([$name, $login, $password, $customer_id]);
        
        // デフォルト住所の更新
        $address_sql = $pdo->prepare('
            UPDATE shipping_addresses 
            SET recipient_name = ?, 
                postal_code = ?, 
                prefecture = ?, 
                city = ?, 
                address_line1 = ?, 
                address_line2 = ?, 
                remote_island_check = ?, 
                region_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE customer_id = ? AND is_default = 1
        ');
        $address_sql->execute([
            $name, $postcode, $prefecture, $city, $address_line1, 
            $address_line2, $remote_island_check, $region_id, $customer_id
        ]);
        
        // セッション情報も更新
        $_SESSION['customer']['name'] = $name;
        $_SESSION['customer']['login'] = $login;
        
        $success_message = '会員情報を更新しました。';
        $redirect_url = 'customer-profile.php';
        
    } else {
        // 新規顧客の登録処理
        $insert_sql = $pdo->prepare('
            INSERT INTO customer (name, login, password, point, created_at, updated_at) 
            VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');
        $insert_sql->execute([$name, $login, $password]);
        $new_customer_id = $pdo->lastInsertId();
        
        // デフォルト住所をshipping_addressesに登録
        $address_insert_sql = $pdo->prepare('
            INSERT INTO shipping_addresses (
                customer_id, address_name, recipient_name, postal_code, 
                prefecture, city, address_line1, address_line2, 
                is_default, remote_island_check, region_id, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');
        $address_insert_sql->execute([
            $new_customer_id, '自宅', $name, $postcode, $prefecture, 
            $city, $address_line1, $address_line2, $remote_island_check, $region_id
        ]);
        
        // セッションに顧客情報を保存
        $_SESSION['customer'] = [
            'id' => $new_customer_id,
            'name' => $name,
            'login' => $login,
            'point' => 0
        ];
        
        // 登録完了データをセッションに保存
        $_SESSION['registration_complete'] = [
            'customer_id' => $new_customer_id,
            'name' => $name,
            'login' => $login,
            'address' => $prefecture . $city . $address_line1
        ];
        
        $success_message = '会員登録が完了しました。';
        $redirect_url = null; // 新規登録の場合はリダイレクトしない
    }
    
    $pdo->commit();
    
    if ($is_update) {
        // 更新の場合のみリダイレクト
        $_SESSION['success_message'] = $success_message;
        header("Location: $redirect_url");
        exit;
    }
    // 新規登録の場合は成功画面を表示
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // エラーログ出力
    error_log("Customer registration/update error: " . $e->getMessage());
    
    // ユーザーフレンドリーなエラーメッセージ
    $errors[] = 'システムエラーが発生しました。しばらく時間をおいてから再度お試しください。';
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: customer-input.php');
    exit;
}

// 新規登録成功の場合の表示
?>
<?php require 'header.php'; ?>

<div class="success-page">
    <div class="success-container">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="success-title">会員登録完了！</h1>
            <p class="success-subtitle">KELOTへの会員登録が正常に完了しました</p>
        </div>
        
        <div class="registration-info">
            <div class="info-card">
                <h3>登録情報</h3>
                <dl class="info-list">
                    <dt>お名前</dt>
                    <dd><?= h($name) ?></dd>
                    
                    <dt>ログインID</dt>
                    <dd><?= h($login) ?></dd>
                    
                    <dt>配送先住所</dt>
                    <dd>
                        〒<?= h($postcode) ?><br>
                        <?= h($prefecture . $city . $address_line1) ?>
                        <?php if ($address_line2): ?>
                            <br><?= h($address_line2) ?>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>
        
        <div class="success-actions">
            <a href="login-input.php" class="btn-primary">
                <i class="fas fa-sign-in-alt"></i>
                ログインページへ
            </a>
            <a href="index.php" class="btn-outline">
                <i class="fas fa-home"></i>
                ホームへ戻る
            </a>
        </div>
    </div>
</div>

<style>
.success-page {
    min-height: calc(100vh - 160px);
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    padding: 40px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.success-container {
    max-width: 600px;
    width: 100%;
    text-align: center;
}

.success-header {
    margin-bottom: 40px;
}

.success-icon {
    width: 100px;
    height: 100px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 3rem;
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
    animation: successPulse 2s ease-in-out infinite;
}

@keyframes successPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.success-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #065f46;
    margin-bottom: 10px;
}

.success-subtitle {
    color: #047857;
    font-size: 1.2rem;
    line-height: 1.6;
}

.registration-info {
    margin-bottom: 40px;
}

.info-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    text-align: left;
}

.info-card h3 {
    color: #065f46;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 20px;
    text-align: center;
}

.info-list {
    display: grid;
    gap: 15px;
}

.info-list dt {
    font-weight: 600;
    color: #374151;
    margin-bottom: 5px;
}

.info-list dd {
    color: #6b7280;
    line-height: 1.6;
    margin: 0;
    padding-left: 10px;
    border-left: 3px solid #10b981;
}

.success-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-primary, .btn-outline {
    padding: 15px 30px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    min-width: 160px;
    justify-content: center;
}

.btn-primary {
    background: #10b981;
    color: white;
    border: 2px solid #10b981;
}

.btn-primary:hover {
    background: #059669;
    border-color: #059669;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-outline {
    background: white;
    color: #6b7280;
    border: 2px solid #d1d5db;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
    color: #1f2937;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .success-page {
        padding: 20px 10px;
    }
    
    .success-icon {
        width: 80px;
        height: 80px;
        font-size: 2.5rem;
    }
    
    .success-title {
        font-size: 2rem;
    }
    
    .success-actions {
        flex-direction: column;
    }
    
    .btn-primary, .btn-outline {
        width: 100%;
    }
}
</style>

<?php require 'footer.php'; ?>
?>