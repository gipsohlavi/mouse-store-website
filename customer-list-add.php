<?php require 'common.php'; ?>
<?php require 'admin-header.php'; ?>
<?php require 'admin-menu.php'; ?>

<?php
// フォームデータの保持（エラー時の再表示用）
$form_data = [
    'name' => $_POST['name'] ?? '',
    'login' => $_POST['login'] ?? '',
    'password' => $_POST['password'] ?? '',
    'point' => $_POST['point'] ?? 0
];
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-user-plus"></i> 新規顧客登録</h2>
        <p class="page-description">新しい顧客の情報を登録します</p>
    </div>

    <div class="add-layout">
        <!-- 左側：入力フォーム -->
        <div class="main-content">
            <!-- エラーメッセージ表示 -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="admin-alert admin-alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="admin-alert admin-alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $_SESSION['success'] ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- 入力フォーム -->
            <div class="admin-card">
                <h3><i class="fas fa-user-edit"></i> 顧客情報入力</h3>

                <form method="POST" action="customer-add-process.php" class="customer-add-form" id="addCustomerForm">
                    <div class="form-grid">
                        <!-- 顧客名 -->
                        <div class="form-group full-width">
                            <label class="form-label required" for="name">
                                <i class="fas fa-user"></i> 顧客名
                            </label>
                            <input type="text"
                                id="name"
                                name="name"
                                class="admin-input"
                                value="<?= htmlspecialchars($form_data['name']) ?>"
                                required
                                maxlength="100"
                                placeholder="山田 太郎">
                            <div class="form-help">顧客の氏名を入力してください（最大100文字）</div>
                        </div>

                        <!-- ログインID -->
                        <div class="form-group">
                            <label class="form-label required" for="login">
                                <i class="fas fa-user-circle"></i> ログインID
                            </label>
                            <input type="text"
                                id="login"
                                name="login"
                                class="admin-input"
                                value="<?= htmlspecialchars($form_data['login']) ?>"
                                required
                                pattern="[a-zA-Z0-9_-]+"
                                minlength="3"
                                maxlength="20"
                                placeholder="yamada_taro">
                            <div class="form-help">英数字、アンダースコア、ハイフンのみ使用可能（3-20文字）</div>
                            <div class="validation-status" id="loginValidation"></div>
                        </div>

                        <!-- パスワード -->
                        <div class="form-group">
                            <label class="form-label required" for="password">
                                <i class="fas fa-lock"></i> パスワード
                            </label>
                            <div class="password-input-group">
                                <input type="password"
                                    id="password"
                                    name="password"
                                    class="admin-input"
                                    value="<?= htmlspecialchars($form_data['password']) ?>"
                                    required
                                    minlength="6"
                                    maxlength="50"
                                    placeholder="6文字以上で入力">
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                            <div class="form-help">6文字以上50文字以下で入力してください</div>
                        </div>

                        <!-- 初期ポイント -->
                        <div class="form-group">
                            <label class="form-label" for="point">
                                <i class="fas fa-coins"></i> 初期ポイント
                            </label>
                            <div class="point-input-group">
                                <input type="number"
                                    id="point"
                                    name="point"
                                    class="admin-input"
                                    value="<?= $form_data['point'] ?>"
                                    min="0"
                                    max="999999"
                                    placeholder="0">
                                <span class="input-unit">pt</span>
                            </div>
                            <div class="form-help">初期付与ポイント（0以上999999以下）</div>
                        </div>
                    </div>

                    <!-- 住所入力セクション -->
                    <div class="address-section">
                        <h4><i class="fas fa-map-marker-alt"></i> 初期配送先住所（オプション）</h4>
                        <p class="section-description">後から住所を追加することもできます</p>

                        <div class="form-grid">
                            <!-- 住所名 -->
                            <div class="form-group">
                                <label class="form-label" for="address_name">
                                    <i class="fas fa-tag"></i> 住所名
                                </label>
                                <input type="text"
                                    id="address_name"
                                    name="address_name"
                                    class="admin-input"
                                    placeholder="自宅"
                                    maxlength="100">
                                <div class="form-help">「自宅」「会社」など（未入力の場合「自宅」になります）</div>
                            </div>

                            <!-- 受取人名 -->
                            <div class="form-group">
                                <label class="form-label" for="recipient_name">
                                    <i class="fas fa-user"></i> 受取人名
                                </label>
                                <input type="text"
                                    id="recipient_name"
                                    name="recipient_name"
                                    class="admin-input"
                                    placeholder="山田 太郎"
                                    maxlength="100">
                                <div class="form-help">受取人の氏名（未入力の場合、顧客名と同じになります）</div>
                            </div>

                            <!-- 郵便番号 -->
                            <div class="form-group">
                                <label class="form-label" for="postal_code">
                                    <i class="fas fa-map-pin"></i> 郵便番号
                                </label>
                                <input type="text"
                                    id="postal_code"
                                    name="postal_code"
                                    class="admin-input"
                                    placeholder="123-4567"
                                    pattern="[0-9]{3}-[0-9]{4}"
                                    maxlength="8">
                                <div class="form-help">ハイフン付きで入力（例：123-4567）</div>
                                <div class="address-lookup" style="margin-top: 0.5rem;">
                                    <button type="button" class="admin-btn admin-btn-outline admin-btn-sm" onclick="lookupAddress()">
                                        <i class="fas fa-search"></i> 住所検索
                                    </button>
                                </div>
                            </div>

                            <!-- 都道府県 -->
                            <div class="form-group">
                                <label class="form-label" for="prefecture">
                                    <i class="fas fa-map"></i> 都道府県
                                </label>
                                <input type="text"
                                    id="prefecture"
                                    name="prefecture"
                                    class="admin-input"
                                    placeholder="東京都"
                                    maxlength="20">
                            </div>

                            <!-- 市区町村 -->
                            <div class="form-group">
                                <label class="form-label" for="city">
                                    <i class="fas fa-building"></i> 市区町村
                                </label>
                                <input type="text"
                                    id="city"
                                    name="city"
                                    class="admin-input"
                                    placeholder="新宿区"
                                    maxlength="100">
                            </div>

                            <!-- 住所1 -->
                            <div class="form-group full-width">
                                <label class="form-label" for="address_line1">
                                    <i class="fas fa-home"></i> 住所1
                                </label>
                                <input type="text"
                                    id="address_line1"
                                    name="address_line1"
                                    class="admin-input"
                                    placeholder="西新宿2-8-1"
                                    maxlength="200">
                                <div class="form-help">町名・番地を入力してください</div>
                            </div>

                            <!-- 住所2 -->
                            <div class="form-group full-width">
                                <label class="form-label" for="address_line2">
                                    <i class="fas fa-building"></i> 住所2（建物名・部屋番号など）
                                </label>
                                <input type="text"
                                    id="address_line2"
                                    name="address_line2"
                                    class="admin-input"
                                    placeholder="○○ビル3階"
                                    maxlength="200">
                            </div>

                            <!-- 電話番号 -->
                            <div class="form-group">
                                <label class="form-label" for="phone">
                                    <i class="fas fa-phone"></i> 電話番号
                                </label>
                                <input type="tel"
                                    id="phone"
                                    name="phone"
                                    class="admin-input"
                                    placeholder="03-1234-5678"
                                    maxlength="20">
                            </div>

                            <!-- 地域選択 -->
                            <div class="form-group">
                                <label class="form-label" for="region_id">
                                    <i class="fas fa-map-marker-alt"></i> 地域
                                </label>
                                <select id="region_id" name="region_id" class="admin-input">
                                    <option value="">未設定</option>
                                    <option value="1">北海道</option>
                                    <option value="2">東北</option>
                                    <option value="3" selected>関東・中部</option>
                                    <option value="4">近畿</option>
                                    <option value="5">中国・四国</option>
                                    <option value="6">九州</option>
                                    <option value="7">沖縄</option>
                                </select>
                                <div class="form-help">配送料金計算に使用されます</div>
                            </div>

                            <!-- 離島判別 -->
                            <div class="form-group">
                                <label class="form-label" for="remote_island_check">
                                    <i class="fas fa-island-tropical"></i> 離島区分
                                </label>
                                <div class="checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox"
                                            id="remote_island_check"
                                            name="remote_island_check"
                                            value="1">
                                        <span class="checkbox-custom"></span>
                                        離島に該当する
                                    </label>
                                </div>
                                <div class="form-help">離島配送料金が適用されます</div>
                            </div>
                        </div>
                    </div>

                    <!-- フォームアクション -->
                    <div class="form-actions">
                        <a href="customer-list.php" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-arrow-left"></i> 顧客一覧に戻る
                        </a>
                        <button type="reset" class="admin-btn admin-btn-outline" onclick="resetForm()">
                            <i class="fas fa-undo"></i> リセット
                        </button>
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-user-plus"></i> 顧客を登録
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 右側：ヘルプ・ガイド -->
        <div class="sidebar-content">
            <!-- 入力ガイド -->
            <div class="admin-card">
                <h3><i class="fas fa-question-circle"></i> 入力ガイド</h3>

                <div class="guide-section">
                    <div class="guide-item">
                        <i class="fas fa-user"></i>
                        <div>
                            <strong>顧客名</strong>
                            <p>姓名をフルネームで入力してください。漢字、ひらがな、カタカナ、英字が使用可能です。</p>
                        </div>
                    </div>

                    <div class="guide-item">
                        <i class="fas fa-user-circle"></i>
                        <div>
                            <strong>ログインID</strong>
                            <p>3-20文字の英数字。アンダースコア（_）とハイフン（-）も使用可能。重複チェックが行われます。</p>
                        </div>
                    </div>

                    <div class="guide-item">
                        <i class="fas fa-lock"></i>
                        <div>
                            <strong>パスワード</strong>
                            <p>6文字以上を推奨。英数字組み合わせでセキュリティを向上させましょう。</p>
                        </div>
                    </div>

                    <div class="guide-item">
                        <i class="fas fa-coins"></i>
                        <div>
                            <strong>初期ポイント</strong>
                            <p>新規登録特典やキャンペーンポイントがある場合に設定します。</p>
                        </div>
                    </div>

                    <div class="guide-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>配送先住所</strong>
                            <p>住所は後から追加・編集できます。複数の配送先を登録することも可能です。</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 注意事項 -->
            <div class="admin-card">
                <h3><i class="fas fa-exclamation-triangle"></i> 注意事項</h3>

                <div class="notice-list">
                    <div class="notice-item">
                        <i class="fas fa-info-circle"></i>
                        <span>ログインIDは登録後の変更が困難です。慎重に入力してください。</span>
                    </div>

                    <div class="notice-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>パスワードは暗号化されて保存されます。</span>
                    </div>

                    <div class="notice-item">
                        <i class="fas fa-map-pin"></i>
                        <span>住所は登録後に配送先住所管理画面から追加・編集できます。</span>
                    </div>

                    <div class="notice-item">
                        <i class="fas fa-database"></i>
                        <span>登録完了後、顧客には自動で会員IDが発番されます。</span>
                    </div>
                </div>
            </div>

            <!-- 最近の登録顧客 -->
            <div class="admin-card">
                <h3><i class="fas fa-clock"></i> 最近の登録</h3>

                <?php
                $recent_sql = $pdo->prepare('SELECT id, name, login, point FROM customer ORDER BY id DESC LIMIT 5');
                $recent_sql->execute();
                $recent_customers = $recent_sql->fetchAll();
                ?>

                <?php if (count($recent_customers) > 0): ?>
                    <div class="recent-list">
                        <?php foreach ($recent_customers as $recent): ?>
                            <div class="recent-item">
                                <div class="recent-info">
                                    <div class="recent-name"><?= htmlspecialchars($recent['name']) ?></div>
                                    <div class="recent-details">ID: #<?= str_pad($recent['id'], 4, '0', STR_PAD_LEFT) ?> | <?= number_format($recent['point']) ?>pt</div>
                                </div>
                                <a href="customer-list-detail.php?id=<?= $recent['id'] ?>"
                                    class="admin-btn admin-btn-outline admin-btn-xs">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>まだ顧客が登録されていません</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* レイアウト */
    .add-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }

    .main-content {
        min-width: 0;
    }

    .sidebar-content {
        min-width: 0;
    }

    /* フォームスタイル */
    .customer-add-form {
        margin-top: 1rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
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
        gap: 0.5rem;
        font-weight: 500;
        color: var(--admin-primary);
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .form-label.required::after {
        content: '*';
        color: var(--admin-danger);
        margin-left: 0.25rem;
    }

    .admin-input {
        width: 100%;
        transition: all 0.2s ease;
    }

    .admin-input:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .form-help {
        font-size: 0.75rem;
        color: var(--admin-text-light);
        margin-top: 0.25rem;
        line-height: 1.4;
    }

    /* 住所セクション */
    .address-section {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid var(--admin-border);
    }

    .address-section h4 {
        color: var(--admin-text);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-description {
        color: var(--admin-text-light);
        margin-bottom: 1.5rem;
        font-size: 0.875rem;
    }

    /* 特殊な入力フィールド */
    .password-input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    .password-toggle-btn {
        position: absolute;
        right: 0.75rem;
        background: none;
        border: none;
        color: var(--admin-text-light);
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .password-toggle-btn:hover {
        background: var(--admin-bg);
        color: var(--admin-primary);
    }

    .point-input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-unit {
        position: absolute;
        right: 0.75rem;
        color: var(--admin-text-light);
        font-size: 0.875rem;
        pointer-events: none;
    }

    .checkbox-group {
        margin-top: 0.5rem;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        font-weight: 400;
        transition: all 0.2s ease;
    }

    .checkbox-label:hover {
        color: var(--admin-primary);
    }

    .checkbox-custom {
        width: 18px;
        height: 18px;
        border: 2px solid var(--admin-border);
        border-radius: 3px;
        position: relative;
        transition: all 0.2s ease;
    }

    input[type="checkbox"]:checked+.checkbox-custom {
        background: var(--admin-primary);
        border-color: var(--admin-primary);
        transform: scale(1.1);
    }

    input[type="checkbox"]:checked+.checkbox-custom::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: 12px;
        font-weight: bold;
    }

    input[type="checkbox"] {
        display: none;
    }

    /* バリデーション表示 */
    .validation-status {
        margin-top: 0.5rem;
        padding: 0.5rem;
        border-radius: 4px;
        font-size: 0.8125rem;
        display: none;
    }

    .validation-status.valid {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
        display: block;
    }

    .validation-status.invalid {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
        display: block;
    }

    .validation-status.checking {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
        display: block;
    }

    .password-strength {
        margin-top: 0.5rem;
        height: 4px;
        background: #e5e7eb;
        border-radius: 2px;
        overflow: hidden;
    }

    .password-strength-bar {
        height: 100%;
        transition: all 0.3s ease;
        border-radius: 2px;
    }

    .strength-weak {
        background: #ef4444;
        width: 25%;
    }

    .strength-fair {
        background: #f59e0b;
        width: 50%;
    }

    .strength-good {
        background: #3b82f6;
        width: 75%;
    }

    .strength-strong {
        background: #10b981;
        width: 100%;
    }

    /* フォームアクション */
    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        padding-top: 2rem;
        border-top: 1px solid var(--admin-border);
    }

    .admin-btn-outline {
        background: transparent;
        color: var(--admin-text);
        border: 1px solid var(--admin-border);
    }

    .admin-btn-outline:hover {
        background: var(--admin-bg);
        border-color: var(--admin-primary);
        color: var(--admin-primary);
    }

    /* ガイドセクション */
    .guide-section {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .guide-item {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .guide-item>i {
        color: var(--admin-primary);
        width: 20px;
        margin-top: 0.25rem;
        flex-shrink: 0;
    }

    .guide-item strong {
        color: var(--admin-text);
        font-size: 0.875rem;
        display: block;
        margin-bottom: 0.25rem;
    }

    .guide-item p {
        color: var(--admin-text-light);
        font-size: 0.8125rem;
        line-height: 1.4;
        margin: 0;
    }

    /* 注意事項 */
    .notice-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .notice-item {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
        padding: 0.75rem;
        background: #fef3c7;
        border: 1px solid #f59e0b;
        border-radius: 6px;
        color: #92400e;
    }

    .notice-item>i {
        color: #f59e0b;
        width: 16px;
        margin-top: 0.125rem;
        flex-shrink: 0;
    }

    .notice-item span {
        font-size: 0.8125rem;
        line-height: 1.4;
    }

    /* 最近の登録 */
    .recent-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .recent-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        background: var(--admin-bg);
        border-radius: 6px;
    }

    .recent-info {
        flex: 1;
        min-width: 0;
    }

    .recent-name {
        font-weight: 500;
        color: var(--admin-text);
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }

    .recent-details {
        font-size: 0.75rem;
        color: var(--admin-text-light);
    }

    /* 空状態 */
    .empty-state {
        text-align: center;
        padding: 2rem 1rem;
        color: var(--admin-text-light);
    }

    .empty-state i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: var(--admin-secondary);
    }

    /* ボタンサイズ */
    .admin-btn-xs {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        min-width: 30px;
    }

    /* レスポンシブ対応 */
    @media (max-width: 1200px) {
        .add-layout {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .form-actions {
            flex-direction: column;
        }

        .guide-item {
            flex-direction: column;
            gap: 0.5rem;
        }

        .guide-item>i {
            align-self: flex-start;
        }
    }
</style>

<script>
    // ログインID重複チェック
    let checkTimeout;
    const loginInput = document.getElementById('login');
    const validationDiv = document.getElementById('loginValidation');

    loginInput.addEventListener('input', function() {
        clearTimeout(checkTimeout);
        const loginId = this.value.trim();

        if (loginId.length < 3) {
            validationDiv.className = 'validation-status';
            validationDiv.style.display = 'none';
            return;
        }

        validationDiv.className = 'validation-status checking';
        validationDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> チェック中...';

        checkTimeout = setTimeout(() => {
            // Ajax呼び出しをシミュレート（実際の実装では適切なエンドポイントを呼び出す）
            fetch('check-login-id.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `login_id=${encodeURIComponent(loginId)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        validationDiv.className = 'validation-status valid';
                        validationDiv.innerHTML = '<i class="fas fa-check"></i> このログインIDは使用可能です';
                    } else {
                        validationDiv.className = 'validation-status invalid';
                        validationDiv.innerHTML = '<i class="fas fa-times"></i> このログインIDは既に使用されています';
                    }
                })
                .catch(() => {
                    validationDiv.className = 'validation-status';
                    validationDiv.style.display = 'none';
                });
        }, 500);
    });

    // パスワード強度チェック
    const passwordInput = document.getElementById('password');
    const strengthDiv = document.getElementById('passwordStrength');

    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);

        strengthDiv.innerHTML = '<div class="password-strength-bar ' + strength.class + '"></div>';
    });

    function calculatePasswordStrength(password) {
        let score = 0;

        if (password.length >= 6) score++;
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;

        if (score <= 2) return {
            class: 'strength-weak',
            text: '弱い'
        };
        if (score <= 3) return {
            class: 'strength-fair',
            text: 'やや弱い'
        };
        if (score <= 4) return {
            class: 'strength-good',
            text: '良い'
        };
        return {
            class: 'strength-strong',
            text: '強い'
        };
    }

    // パスワード表示切り替え
    function togglePasswordVisibility(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.nextElementSibling;
        const icon = button.querySelector('i');

        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            field.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }

    // 住所検索機能
    function lookupAddress() {
        const postcode = document.getElementById('postal_code').value.replace(/[^0-9]/g, '');

        if (postcode.length !== 7) {
            alert('正しい郵便番号を入力してください（7桁の数字）');
            return;
        }

        // 実際の住所検索APIを呼び出す
        fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${postcode}`)
            .then(response => response.json())
            .then(data => {
                if (data.results && data.results.length > 0) {
                    const result = data.results[0];
                    document.getElementById('prefecture').value = result.address1;
                    document.getElementById('city').value = result.address2;
                    document.getElementById('address_line1').value = result.address3;

                    // 地域も自動設定（都道府県から推定）
                    const prefecture = result.address1;
                    const regionMap = {
                        '北海道': 1,
                        '青森県': 2,
                        '岩手県': 2,
                        '宮城県': 2,
                        '秋田県': 2,
                        '山形県': 2,
                        '福島県': 2,
                        '茨城県': 3,
                        '栃木県': 3,
                        '群馬県': 3,
                        '埼玉県': 3,
                        '千葉県': 3,
                        '東京都': 3,
                        '神奈川県': 3,
                        '新潟県': 3,
                        '富山県': 3,
                        '石川県': 3,
                        '福井県': 3,
                        '山梨県': 3,
                        '長野県': 3,
                        '岐阜県': 3,
                        '静岡県': 3,
                        '愛知県': 3,
                        '三重県': 3,
                        '滋賀県': 4,
                        '京都府': 4,
                        '大阪府': 4,
                        '兵庫県': 4,
                        '奈良県': 4,
                        '和歌山県': 4,
                        '鳥取県': 5,
                        '島根県': 5,
                        '岡山県': 5,
                        '広島県': 5,
                        '山口県': 5,
                        '徳島県': 5,
                        '香川県': 5,
                        '愛媛県': 5,
                        '高知県': 5,
                        '福岡県': 6,
                        '佐賀県': 6,
                        '長崎県': 6,
                        '熊本県': 6,
                        '大分県': 6,
                        '宮崎県': 6,
                        '鹿児島県': 6,
                        '沖縄県': 7
                    };

                    if (regionMap[prefecture]) {
                        document.getElementById('region_id').value = regionMap[prefecture];
                    }
                } else {
                    alert('住所が見つかりませんでした。手動で入力してください。');
                }
            })
            .catch(error => {
                console.error('住所検索エラー:', error);
                alert('住所検索でエラーが発生しました。手動で入力してください。');
            });
    }

    // 郵便番号の自動フォーマット
    document.getElementById('postal_code').addEventListener('input', function() {
        let value = this.value.replace(/[^0-9]/g, '');
        if (value.length > 3) {
            value = value.slice(0, 3) + '-' + value.slice(3, 7);
        }
        this.value = value;
    });

    // ポイント入力の制限
    document.getElementById('point').addEventListener('input', function() {
        if (this.value < 0) this.value = 0;
        if (this.value > 999999) this.value = 999999;
    });

    // フォームバリデーション
    document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const login = document.getElementById('login').value.trim();
        const password = document.getElementById('password').value.trim();

        // 基本的なバリデーション
        if (!name) {
            alert('顧客名は必須です。');
            document.getElementById('name').focus();
            e.preventDefault();
            return;
        }

        if (!login) {
            alert('ログインIDは必須です。');
            document.getElementById('login').focus();
            e.preventDefault();
            return;
        }

        if (login.length < 3) {
            alert('ログインIDは3文字以上で入力してください。');
            document.getElementById('login').focus();
            e.preventDefault();
            return;
        }

        if (!/^[a-zA-Z0-9_-]+$/.test(login)) {
            alert('ログインIDは英数字、アンダースコア、ハイフンのみ使用可能です。');
            document.getElementById('login').focus();
            e.preventDefault();
            return;
        }

        if (!password) {
            alert('パスワードは必須です。');
            document.getElementById('password').focus();
            e.preventDefault();
            return;
        }

        if (password.length < 6) {
            alert('パスワードは6文字以上で入力してください。');
            document.getElementById('password').focus();
            e.preventDefault();
            return;
        }

        // ログインIDの重複チェック
        const validationStatus = document.getElementById('loginValidation');
        if (validationStatus.classList.contains('invalid')) {
            alert('このログインIDは既に使用されています。別のIDを入力してください。');
            document.getElementById('login').focus();
            e.preventDefault();
            return;
        }

        // 確認ダイアログ
        if (!confirm('入力した情報で顧客を登録しますか？')) {
            e.preventDefault();
            return;
        }

        // 送信ボタンを無効化（重複送信防止）
        const submitBtn = this.querySelector('[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 登録中...';
    });

    // リセット機能
    function resetForm() {
        if (confirm('入力した内容をすべてリセットしますか？')) {
            document.getElementById('addCustomerForm').reset();
            document.getElementById('loginValidation').style.display = 'none';
            document.getElementById('passwordStrength').innerHTML = '';
        }
    }

    // フォームの変更を追跡
    let formChanged = false;
    const formInputs = document.querySelectorAll('#addCustomerForm input, #addCustomerForm select, #addCustomerForm textarea');

    formInputs.forEach(input => {
        input.addEventListener('change', () => {
            formChanged = true;
        });
    });

    // ページ離脱時の確認
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });

    // フォーム送信時は確認を無効化
    document.getElementById('addCustomerForm').addEventListener('submit', function() {
        formChanged = false;
    });

    // 入力フィールドのエンハンスメント
    document.addEventListener('DOMContentLoaded', function() {
        // 名前フィールドの文字数カウンター
        const nameInput = document.getElementById('name');
        const nameHelp = nameInput.nextElementSibling;

        nameInput.addEventListener('input', function() {
            const remaining = 100 - this.value.length;
            nameHelp.textContent = `顧客の氏名を入力してください（残り${remaining}文字）`;

            if (remaining < 0) {
                nameHelp.style.color = 'var(--admin-danger)';
            } else if (remaining < 20) {
                nameHelp.style.color = 'var(--admin-warning)';
            } else {
                nameHelp.style.color = 'var(--admin-text-light)';
            }
        });

        // 受取人名の自動入力
        document.getElementById('name').addEventListener('blur', function() {
            const recipientField = document.getElementById('recipient_name');
            if (!recipientField.value && this.value) {
                recipientField.value = this.value;
            }
        });
    });
</script>

<?php require 'admin-footer.php'; ?>