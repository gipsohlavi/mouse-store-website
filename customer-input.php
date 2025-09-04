<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>

<?php
// エラーメッセージとフォームデータの取得
$errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];

// セッションからエラーデータを即座にクリア（重要！リダイレクトループを防ぐ）
unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);

// 更新かどうかをチェック
$is_update = isset($_SESSION['customer']);

// デフォルト値の設定
if ($is_update) {
    // 更新の場合：セッションとデータベースから現在の情報を取得
    $customer_id = $_SESSION['customer']['id'];
    $name = $form_data['name'] ?? $_SESSION['customer']['name'];
    $login = $form_data['login'] ?? $_SESSION['customer']['login'];
    
    // 住所情報をデータベースから取得
    $address_sql = $pdo->prepare('
        SELECT postal_code, prefecture, city, address_line1, address_line2, remote_island_check
        FROM shipping_addresses 
        WHERE customer_id = ? AND is_default = 1
        LIMIT 1
    ');
    $address_sql->execute([$customer_id]);
    $address_data = $address_sql->fetch() ?: [];
    
    $postcode = $form_data['postcode'] ?? ($address_data['postal_code'] ?? '');
    $prefecture = $form_data['prefecture'] ?? ($address_data['prefecture'] ?? '');
    $city = $form_data['city'] ?? ($address_data['city'] ?? '');
    $address_line1 = $form_data['address_line1'] ?? ($address_data['address_line1'] ?? '');
    $address_line2 = $form_data['address_line2'] ?? ($address_data['address_line2'] ?? '');
    $remote_island_check = isset($form_data['remote_island_check']) ? $form_data['remote_island_check'] : ($address_data['remote_island_check'] ?? 0);
} else {
    // 新規登録の場合
    $name = $form_data['name'] ?? '';
    $postcode = $form_data['postcode'] ?? '';
    $prefecture = $form_data['prefecture'] ?? '';
    $city = $form_data['city'] ?? '';
    $address_line1 = $form_data['address_line1'] ?? '';
    $address_line2 = $form_data['address_line2'] ?? '';
    $login = $form_data['login'] ?? '';
    $remote_island_check = isset($form_data['remote_island_check']) ? $form_data['remote_island_check'] : 0;
}

$password = $form_data['password'] ?? '';

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

// 地域マスターの取得
$region_sql = $pdo->prepare('SELECT DISTINCT region_id, m.name as region_name 
                            FROM region r 
                            JOIN master m ON m.master_id = r.region_id AND m.kbn = 11 
                            ORDER BY r.region_id');
$region_sql->execute();
$regions = $region_sql->fetchAll();
?>

<div class="registration-page">
    <div class="registration-container">
        <!-- エラーメッセージの表示 -->
        <?php if (!empty($errors)): ?>
            <div class="error-alert">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="alert-content">
                    <h4>入力内容に問題があります</h4>
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?= h($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <div class="registration-header">
            <div class="header-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1 class="page-title">
                <?php if ($is_update): ?>
                    会員情報更新
                <?php else: ?>
                    新規会員登録
                <?php endif; ?>
            </h1>
            <p class="page-subtitle">
                <?php if ($is_update): ?>
                    登録情報を更新してください
                <?php else: ?>
                    KELOTへようこそ。アカウントを作成してお得にお買い物を始めましょう
                <?php endif; ?>
            </p>
        </div>

        <div class="form-container">
            <form action="customer-output.php" method="post" class="registration-form" id="customerForm">
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user"></i>
                        基本情報
                    </h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name" class="form-label required">お名前</label>
                            <input type="text" id="name" name="name" class="form-input" required 
                                   value="<?= h($name) ?>" placeholder="山田 太郎">
                            <small class="form-help">姓名を入力してください</small>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        配送先情報
                    </h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="postcode" class="form-label required">郵便番号</label>
                            <div class="postcode-group">
                                <input type="text" id="postcode" name="postcode" class="form-input" required 
                                       value="<?= h($postcode) ?>" placeholder="123-4567" maxlength="8" 
                                       pattern="[0-9]{3}-[0-9]{4}">
                                <button type="button" id="postcodeSearch" class="postcode-btn">
                                    <i class="fas fa-search"></i>
                                    <span>住所検索</span>
                                </button>
                            </div>
                            <small class="form-help">ハイフン付きで入力してください（例：123-4567）</small>
                            <div id="postcodeResult" class="postcode-result"></div>
                        </div>

                        <div class="form-group">
                            <label for="prefecture" class="form-label required">都道府県</label>
                            <select id="prefecture" name="prefecture" class="form-input" required>
                                <option value="">都道府県を選択</option>
                                <?php foreach ($prefectures as $pref): ?>
                                    <option value="<?= h($pref) ?>" <?= $prefecture == $pref ? 'selected' : '' ?>>
                                        <?= h($pref) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="city" class="form-label required">市区町村</label>
                            <input type="text" id="city" name="city" class="form-input" required 
                                   value="<?= h($city) ?>" placeholder="渋谷区、横浜市中区など">
                            <small class="form-help">市区町村名を入力してください</small>
                        </div>

                        <div class="form-group full-width">
                            <label for="address_line1" class="form-label required">番地・町名</label>
                            <input type="text" id="address_line1" name="address_line1" class="form-input" required 
                                   value="<?= h($address_line1) ?>" placeholder="神南1-2-3">
                            <small class="form-help">番地や町名を入力してください</small>
                        </div>

                        <div class="form-group full-width">
                            <label for="address_line2" class="form-label">建物名・部屋番号</label>
                            <input type="text" id="address_line2" name="address_line2" class="form-input" 
                                   value="<?= h($address_line2) ?>" placeholder="KELOTビル 3階 301号室">
                            <small class="form-help">建物名や部屋番号がある場合は入力してください（任意）</small>
                        </div>

                        <div class="form-group full-width">
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="remote_island_check" value="1" 
                                           <?= $remote_island_check ? 'checked' : '' ?> class="checkbox-input">
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-text">
                                        離島・一部地域への配送
                                        <small class="checkbox-help">該当する場合は追加配送料がかかります</small>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- 地域情報表示 -->
                        <div class="form-group full-width">
                            <div id="regionInfo" class="region-info" style="display: none;">
                                <div class="info-card">
                                    <i class="fas fa-truck"></i>
                                    <div class="info-content">
                                        <strong id="regionName">地域名</strong>
                                        <span id="shippingFee">配送料: 計算中...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-key"></i>
                        ログイン情報
                    </h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="login" class="form-label required">ログイン名</label>
                            <input type="text" id="login" name="login" class="form-input" required 
                                   value="<?= h($login) ?>" placeholder="user123" pattern="[a-zA-Z0-9]+" 
                                   minlength="3">
                            <small class="form-help">
                                <?php if ($is_update): ?>
                                    変更する場合は新しいログイン名を入力
                                <?php else: ?>
                                    半角英数字3文字以上で入力してください
                                <?php endif; ?>
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label required">パスワード</label>
                            <div class="password-group">
                                <input type="password" id="password" name="password" class="form-input" required 
                                       value="<?= h($password) ?>" placeholder="8文字以上" minlength="8">
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="passwordToggleIcon"></i>
                                </button>
                            </div>
                            <small class="form-help">8文字以上の英数字を組み合わせてください</small>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="<?= $is_update ? 'customer-profile.php' : 'login-input.php' ?>" class="btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        <?= $is_update ? 'キャンセル' : 'ログインに戻る' ?>
                    </a>
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class="fas fa-check"></i>
                        <?= $is_update ? '情報を更新' : 'アカウント作成' ?>
                    </button>
                </div>
            </form>
        </div>

        <?php if (!$is_update): ?>
            <div class="login-prompt">
                <p>既にアカウントをお持ちの方は <a href="login-input.php" class="login-link">ログイン</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* エラーアラートのスタイル */
.error-alert {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    gap: 15px;
    position: relative;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-icon {
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

.alert-content h4 {
    color: #991b1b;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 10px;
}

.error-list {
    margin: 0;
    padding-left: 20px;
    color: #dc2626;
}

.error-list li {
    margin-bottom: 5px;
    line-height: 1.4;
}

.alert-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    color: #991b1b;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.3s;
}

.alert-close:hover {
    background: #fee2e2;
    color: #7f1d1d;
}

/* 既存のスタイル */
.registration-page {
    min-height: calc(100vh - 160px);
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 40px 20px;
}

.registration-container { max-width: 900px; margin: 0 auto; }
.registration-header { text-align: center; margin-bottom: 40px; }
.header-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #2563eb, #1d4ed8); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: white; font-size: 2rem; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3); }
.page-title { font-size: 2rem; font-weight: 700; color: #1f2937; margin-bottom: 10px; }
.page-subtitle { color: #6b7280; font-size: 1.1rem; max-width: 600px; margin: 0 auto; line-height: 1.6; }
.form-container { background: white; border-radius: 16px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08); overflow: hidden; }
.registration-form { padding: 40px; }
.form-section { margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px solid #f3f4f6; }
.form-section:last-child { border-bottom: none; margin-bottom: 0; }
.section-title { display: flex; align-items: center; gap: 10px; font-size: 1.25rem; font-weight: 600; color: #1f2937; margin-bottom: 20px; }
.section-title i { color: #2563eb; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.form-group { display: flex; flex-direction: column; }
.form-group.full-width { grid-column: 1 / -1; }
.form-label { display: flex; align-items: center; gap: 5px; font-weight: 500; color: #1f2937; margin-bottom: 8px; font-size: 0.9rem; }
.form-label.required::after { content: '*'; color: #ef4444; margin-left: 2px; }
.form-input { padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: white; }
.form-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); transform: translateY(-1px); }
.form-help { color: #6b7280; font-size: 0.8rem; margin-top: 5px; display: flex; align-items: center; gap: 5px; }
.form-actions { display: flex; gap: 15px; justify-content: flex-end; padding-top: 30px; border-top: 1px solid #f3f4f6; }
.btn-outline { padding: 12px 24px; background: white; color: #4b5563; border: 1px solid #d1d5db; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; }
.btn-outline:hover { background: #f9fafb; border-color: #9ca3af; color: #1f2937; }
.btn-primary { padding: 12px 24px; background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; }
.btn-primary:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
.login-prompt { text-align: center; margin-top: 30px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); }
.login-link { color: #2563eb; text-decoration: none; font-weight: 500; transition: color 0.3s; }
.login-link:hover { color: #1d4ed8; }

/* 郵便番号検索関連のスタイルも含める */
.region-info { margin-top: 15px; }
.info-card { background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 15px; display: flex; align-items: center; gap: 12px; }
.info-card i { color: #2563eb; font-size: 1.25rem; }
.info-content { display: flex; flex-direction: column; gap: 5px; }
.info-content strong { color: #1e40af; font-weight: 600; }
.info-content span { color: #1e40af; font-size: 0.9rem; }
.postcode-group { display: flex; gap: 8px; align-items: stretch; }
.postcode-group .form-input { flex: 1; }
.postcode-btn { padding: 12px 16px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; align-items: center; gap: 6px; white-space: nowrap; min-width: 100px; justify-content: center; }
.postcode-btn:hover { background: #1d4ed8; transform: translateY(-1px); }
.postcode-btn:disabled { background: #9ca3af; cursor: not-allowed; transform: none; }
.postcode-result { margin-top: 10px; display: none; }
.postcode-message { padding: 12px 16px; border-radius: 8px; display: flex; align-items: flex-start; gap: 10px; font-size: 0.9rem; line-height: 1.4; animation: slideIn 0.3s ease-out; }
.postcode-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.postcode-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.postcode-info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
.postcode-message i { flex-shrink: 0; margin-top: 1px; }

/* チェックボックスのスタイル */
.checkbox-group { margin-top: 10px; }
.checkbox-label { display: flex; align-items: flex-start; gap: 12px; cursor: pointer; padding: 15px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.checkbox-label:hover { background: #f3f4f6; border-color: #d1d5db; }
.checkbox-input { display: none; }
.checkbox-custom { width: 20px; height: 20px; border: 2px solid #d1d5db; border-radius: 4px; background: white; flex-shrink: 0; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; margin-top: 2px; }
.checkbox-input:checked + .checkbox-custom { background: #2563eb; border-color: #2563eb; }
.checkbox-input:checked + .checkbox-custom::after { content: '✓'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 12px; font-weight: bold; }
.checkbox-text { font-weight: 500; color: #1f2937; line-height: 1.4; }
.checkbox-help { display: block; color: #6b7280; font-size: 0.8rem; font-weight: normal; margin-top: 2px; }

.password-group { display: flex; position: relative; }
.password-group .form-input { flex: 1; padding-right: 50px; }
.password-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6b7280; cursor: pointer; padding: 4px; border-radius: 4px; }
.password-toggle:hover { color: #1f2937; background: #f3f4f6; }

@media (max-width: 768px) {
    .registration-page { padding: 20px 10px; }
    .registration-form { padding: 30px 20px; }
    .form-grid { grid-template-columns: 1fr; }
    .form-actions { flex-direction: column-reverse; }
    .postcode-group { flex-direction: column; }
    .postcode-btn { min-width: auto; }
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const postcodeInput = document.getElementById('postcode');
    const searchBtn = document.getElementById('postcodeSearch');
    const resultDiv = document.getElementById('postcodeResult');
    const prefectureSelect = document.getElementById('prefecture');
    const cityInput = document.getElementById('city');
    const regionInfo = document.getElementById('regionInfo');
    
    // 郵便番号の入力制限とフォーマット
    postcodeInput.addEventListener('input', function() {
        let value = this.value.replace(/[^0-9]/g, '');
        if (value.length > 3) {
            value = value.slice(0, 3) + '-' + value.slice(3, 7);
        }
        this.value = value;
        
        // 7桁入力されたら自動で検索
        if (value.replace('-', '').length === 7) {
            searchAddress();
        } else {
            hideRegionInfo();
        }
    });
    
    // 住所検索ボタン
    searchBtn.addEventListener('click', function() {
        searchAddress();
    });
    
    // Enterキーでも検索
    postcodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchAddress();
        }
    });
    
    // 都道府県変更時の地域情報更新
    prefectureSelect.addEventListener('change', function() {
        updateRegionInfo(this.value);
    });
    
    function searchAddress() {
        const postcode = postcodeInput.value.replace('-', '');
        
        if (!/^\d{7}$/.test(postcode)) {
            showResult('error', '郵便番号は7桁の数字で入力してください（例：1600023）');
            return;
        }
        
        // ローディング状態
        searchBtn.disabled = true;
        searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>検索中...</span>';
        showResult('info', '住所を検索しています...');
        
        // AJAX リクエスト
        fetch('postcode-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `postcode=${encodeURIComponent(postcode)}`
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API response:', data);
            
            if (data.success) {
                // 成功時の処理
                const address = data.address;
                const region = data.region;
                
                // フォームに自動入力
                prefectureSelect.value = address.prefecture;
                cityInput.value = address.city;
                
                // 地域情報を表示
                showRegionInfo(region.region_name, region.shipping_fee_formatted);
                
                // 成功メッセージ
                showResult('success', `
                    <strong>住所が見つかりました</strong><br>
                    ${data.postcode_formatted} ${address.prefecture}${address.city}<br>
                    配送地域: ${region.region_name} (配送料: ¥${region.shipping_fee_formatted})
                `);
                
            } else {
                showResult('error', data.error || '住所の取得に失敗しました');
                hideRegionInfo();
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showResult('error', 'ネットワークエラーが発生しました。API接続を確認してください。');
            hideRegionInfo();
        })
        .finally(() => {
            // ローディング状態を解除
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i><span>住所検索</span>';
        });
    }
    
    function updateRegionInfo(prefecture) {
        if (prefecture) {
            // 都道府県から地域情報を取得
            fetch('get-region-info.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `prefecture=${encodeURIComponent(prefecture)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showRegionInfo(data.region_name, data.shipping_fee_formatted);
                }
            })
            .catch(error => {
                console.error('Region info error:', error);
                hideRegionInfo();
            });
        } else {
            hideRegionInfo();
        }
    }
    
    function showRegionInfo(regionName, shippingFee) {
        document.getElementById('regionName').textContent = regionName;
        document.getElementById('shippingFee').textContent = `配送料: ¥${shippingFee}`;
        regionInfo.style.display = 'block';
    }
    
    function hideRegionInfo() {
        regionInfo.style.display = 'none';
    }
    
    function showResult(type, message) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle'
        };
        
        resultDiv.innerHTML = `
            <div class="postcode-message postcode-${type}">
                <i class="${icons[type]}"></i>
                <span>${message}</span>
            </div>
        `;
        resultDiv.style.display = 'block';
        
        // 3秒後にinfo/successメッセージを自動で隠す
        if (type !== 'error') {
            setTimeout(() => {
                resultDiv.style.display = 'none';
            }, 5000);
        }
    }
    
    // ページ読み込み時に都道府県が設定されていれば地域情報を表示
    if (prefectureSelect.value) {
        updateRegionInfo(prefectureSelect.value);
    }
});

function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = document.getElementById(inputId + 'ToggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// フォームバリデーション
document.getElementById('customerForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const requiredFields = this.querySelectorAll('[required]');
    let isValid = true;
    
    // 必須フィールドのチェック
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#ef4444';
            isValid = false;
        } else {
            field.style.borderColor = '#d1d5db';
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('必須項目をすべて入力してください。');
        return false;
    }
    
    // 二重送信防止
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 処理中...';
    
    // 5秒後に再度有効化（エラーの場合を考慮）
    setTimeout(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check"></i> <?= $is_update ? "情報を更新" : "アカウント作成" ?>';
    }, 5000);
    
    return true;
});
</script>

<?php require 'footer.php' ?>