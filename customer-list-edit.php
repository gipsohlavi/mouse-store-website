<?php require 'common.php'; ?>
<?php require 'admin-header.php'; ?>
<?php require 'admin-menu.php'; ?>

<?php
$customer_id = $_GET['id'] ?? 0;
$sql = $pdo->prepare('SELECT * FROM customer WHERE id = ?');
$sql->bindParam(1, $customer_id);
$sql->execute();
$customer = $sql->fetch();

if (!$customer) {
    echo '<script>alert("指定された顧客が見つかりません。"); window.location.href="customer-list.php";</script>';
    exit;
}

// デフォルト配送先住所の取得
$address_sql = $pdo->prepare('SELECT * FROM shipping_addresses WHERE customer_id = ? AND is_default = 1 LIMIT 1');
$address_sql->bindParam(1, $customer_id);
$address_sql->execute();
$default_address = $address_sql->fetch();

// 購入履歴の取得
$history_sql = $pdo->prepare('SELECT COUNT(*) as purchase_count, 
    COALESCE(SUM(grand_total), 0) as total_spent,
    MAX(purchase_date) as last_purchase
    FROM purchase WHERE customer_id = ?');
$history_sql->bindParam(1, $customer_id);
$history_sql->execute();
$history = $history_sql->fetch();
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2>
            <i class="fas fa-user-edit"></i>
            顧客情報編集
        </h2>
        <p class="page-description">顧客ID: #<?= str_pad($customer['id'], 4, '0', STR_PAD_LEFT) ?> の情報を編集します</p>
    </div>

    <div class="edit-layout">
        <!-- 左側：基本情報 -->
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

            <!-- 基本情報編集フォーム -->
            <div class="admin-card">
                <h3><i class="fas fa-user"></i> 基本情報</h3>

                <form method="POST" action="customer-list-upd.php" class="customer-edit-form" id="customerForm">
                    <input type="hidden" name="id" value="<?= $customer['id'] ?>">

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
                                value="<?= htmlspecialchars($customer['name']) ?>"
                                required>
                            <div class="form-help">顧客の氏名を入力してください</div>
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
                                value="<?= htmlspecialchars($customer['login']) ?>"
                                required
                                pattern="[a-zA-Z0-9_-]+"
                                minlength="3"
                                maxlength="20">
                            <div class="form-help">英数字、アンダースコア、ハイフンのみ使用可能（3-20文字）</div>
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
                                    value="<?= htmlspecialchars($customer['password']) ?>"
                                    required
                                    minlength="6">
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-help">6文字以上で入力してください</div>
                        </div>

                        <!-- 保有ポイント -->
                        <div class="form-group">
                            <label class="form-label" for="point">
                                <i class="fas fa-coins"></i> 保有ポイント
                            </label>
                            <div class="point-input-group">
                                <input type="number"
                                    id="point"
                                    name="point"
                                    class="admin-input"
                                    value="<?= $customer['point'] ?>"
                                    min="0"
                                    max="999999">
                                <span class="input-unit">pt</span>
                            </div>
                            <div class="form-help">0以上999999以下で入力</div>
                        </div>
                    </div>

                    <!-- フォームアクション -->
                    <div class="form-actions">
                        <a href="customer-list.php" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-arrow-left"></i> 一覧に戻る
                        </a>
                        <button type="reset" class="admin-btn admin-btn-outline">
                            <i class="fas fa-undo"></i> リセット
                        </button>
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-save"></i> 保存
                        </button>
                    </div>
                </form>
            </div>

            <!-- 配送先住所管理 -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-map-marker-alt"></i> 配送先住所管理</h3>
                    <div class="header-actions">
                        <a href="shipping-address-add.php?customer_id=<?= $customer['id'] ?>"
                            class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-plus"></i> 住所追加
                        </a>
                    </div>
                </div>

                <?php if ($default_address): ?>
                    <div class="address-preview">
                        <div class="address-item default-address">
                            <div class="address-header">
                                <span class="address-name"><?= htmlspecialchars($default_address['address_name']) ?></span>
                                <span class="default-badge">デフォルト</span>
                            </div>
                            <div class="address-content">
                                <div class="recipient"><?= htmlspecialchars($default_address['recipient_name']) ?></div>
                                <div class="address-text">
                                    <?php if ($default_address['postal_code']): ?>
                                        〒<?= htmlspecialchars($default_address['postal_code']) ?><br>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($default_address['prefecture']) ?>
                                    <?= htmlspecialchars($default_address['city']) ?>
                                    <?= htmlspecialchars($default_address['address_line1']) ?>
                                    <?php if ($default_address['address_line2']): ?>
                                        <br><?= htmlspecialchars($default_address['address_line2']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="address-actions">
                                <a href="shipping-address-list.php?customer_id=<?= $customer['id'] ?>"
                                    class="admin-btn admin-btn-outline admin-btn-sm">
                                    <i class="fas fa-list"></i> 全住所管理
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-map-marker-alt"></i>
                        <p>配送先住所が登録されていません</p>
                        <a href="shipping-address-add.php?customer_id=<?= $customer['id'] ?>"
                            class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-plus"></i> 住所を追加
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 右側：サマリー情報 -->
        <div class="sidebar-content">
            <!-- 顧客サマリー -->
            <div class="admin-card summary-card">
                <h3><i class="fas fa-user-tag"></i> 顧客サマリー</h3>

                <div class="summary-item">
                    <div class="summary-label">顧客ID</div>
                    <div class="summary-value">#<?= str_pad($customer['id'], 4, '0', STR_PAD_LEFT) ?></div>
                </div>

                <div class="summary-item">
                    <div class="summary-label">現在のポイント</div>
                    <div class="summary-value points-highlight">
                        <?= number_format($customer['point']) ?> pt
                        <?php if ($customer['point'] >= 1000): ?>
                            <span class="status-badge vip">VIP顧客</span>
                        <?php elseif ($customer['point'] >= 100): ?>
                            <span class="status-badge regular">一般顧客</span>
                        <?php elseif ($customer['point'] > 0): ?>
                            <span class="status-badge new">新規顧客</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="summary-item">
                    <div class="summary-label">登録日</div>
                    <div class="summary-value">
                        <?= date('Y年m月d日', strtotime($customer['created_at'])) ?>
                    </div>
                </div>

                <?php if ($default_address): ?>
                    <div class="summary-item">
                        <div class="summary-label">デフォルト地域</div>
                        <div class="summary-value">
                            <?php
                            $regions = [
                                1 => '北海道',
                                2 => '東北',
                                3 => '関東・中部',
                                4 => '近畿',
                                5 => '中国・四国',
                                6 => '九州',
                                7 => '沖縄'
                            ];
                            echo $regions[$default_address['region_id']] ?? '未設定';
                            ?>
                            <?php if ($default_address['remote_island_check']): ?>
                                <span class="island-indicator">
                                    <i class="fas fa-island-tropical"></i> 離島
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 購入履歴サマリー -->
            <div class="admin-card">
                <h3><i class="fas fa-shopping-cart"></i> 購入履歴</h3>

                <div class="purchase-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($history['purchase_count']) ?></div>
                        <div class="stat-label">購入回数</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">¥<?= number_format($history['total_spent']) ?></div>
                        <div class="stat-label">総購入金額</div>
                    </div>
                </div>

                <?php if ($history['last_purchase']): ?>
                    <div class="last-purchase">
                        <div class="summary-label">最終購入日</div>
                        <div class="summary-value"><?= date('Y年m月d日', strtotime($history['last_purchase'])) ?></div>
                    </div>
                <?php endif; ?>

                <div class="purchase-actions">
                    <a href="purchase-history.php?customer_id=<?= $customer['id'] ?>"
                        class="admin-btn admin-btn-outline admin-btn-sm">
                        <i class="fas fa-history"></i> 購入履歴を見る
                    </a>
                </div>
            </div>

            <!-- 操作メニュー -->
            <div class="admin-card">
                <h3><i class="fas fa-tools"></i> その他の操作</h3>

                <div class="action-menu">
                    <a href="customer-list-detail.php?id=<?= $customer['id'] ?>"
                        class="action-item">
                        <i class="fas fa-eye"></i>
                        <span>詳細情報を表示</span>
                    </a>

                    <a href="shipping-address-list.php?customer_id=<?= $customer['id'] ?>"
                        class="action-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>配送先住所管理</span>
                    </a>

                    <button onclick="adjustPoints()" class="action-item">
                        <i class="fas fa-coins"></i>
                        <span>ポイント調整</span>
                    </button>

                    <a href="mailto:support@example.com?subject=顧客ID<?= $customer['id'] ?>について"
                        class="action-item">
                        <i class="fas fa-envelope"></i>
                        <span>サポートに連絡</span>
                    </a>

                    <button onclick="deleteCustomer(<?= $customer['id'] ?>, '<?= htmlspecialchars($customer['name'], ENT_QUOTES) ?>')"
                        class="action-item danger">
                        <i class="fas fa-trash"></i>
                        <span>顧客を削除</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* 住所プレビューのスタイル */
    .address-preview .address-item {
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        padding: 1rem;
        background: white;
    }

    .address-preview .default-address {
        border-color: var(--admin-primary);
        background: linear-gradient(135deg, #fef7ff 0%, #f3f4f6 100%);
    }

    .address-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .address-name {
        font-weight: 600;
        color: var(--admin-text);
    }

    .default-badge {
        background: var(--admin-primary);
        color: white;
        padding: 0.125rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .address-content {
        margin-bottom: 1rem;
    }

    .recipient {
        font-weight: 500;
        color: var(--admin-text);
        margin-bottom: 0.5rem;
    }

    .address-text {
        color: var(--admin-text-light);
        line-height: 1.5;
    }

    .address-actions {
        display: flex;
        gap: 0.5rem;
    }
</style>

<script>
    // パスワード表示/非表示切り替え
    function togglePasswordVisibility(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.nextElementSibling;
        const icon = button.querySelector('i');

        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'fas fa-eye-slash';
            button.title = 'パスワードを隠す';
        } else {
            field.type = 'password';
            icon.className = 'fas fa-eye';
            button.title = 'パスワードを表示';
        }
    }

    // ポイント調整機能
    function adjustPoints() {
        const currentPoints = <?= $customer['point'] ?>;
        const newPoints = prompt(`現在のポイント: ${currentPoints.toLocaleString()} pt\n\n新しいポイント数を入力してください:`, currentPoints);

        if (newPoints !== null && !isNaN(newPoints) && newPoints >= 0) {
            if (confirm(`ポイントを ${parseInt(newPoints).toLocaleString()} pt に変更しますか？`)) {
                // ポイント欄に反映
                document.getElementById('point').value = newPoints;
                document.getElementById('point').style.backgroundColor = '#fef3c7';
                setTimeout(() => {
                    document.getElementById('point').style.backgroundColor = '';
                }, 2000);
            }
        }
    }

    // 顧客削除確認
    function deleteCustomer(id, name) {
        if (confirm(`顧客「${name}」を削除してもよろしいですか？\n\nこの操作は取り消せません。関連する購入履歴なども削除されます。`)) {
            window.location.href = `customer-list-delete.php?id=${id}`;
        }
    }

    // フォームバリデーション
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('customerForm');

        form.addEventListener('submit', function(e) {
            const loginField = document.getElementById('login');
            const passwordField = document.getElementById('password');
            const nameField = document.getElementById('name');

            // 基本的なバリデーション
            if (!nameField.value.trim()) {
                alert('顧客名は必須です。');
                nameField.focus();
                e.preventDefault();
                return;
            }

            if (!loginField.value.trim()) {
                alert('ログインIDは必須です。');
                loginField.focus();
                e.preventDefault();
                return;
            }

            if (loginField.value.length < 3) {
                alert('ログインIDは3文字以上で入力してください。');
                loginField.focus();
                e.preventDefault();
                return;
            }

            if (!passwordField.value.trim()) {
                alert('パスワードは必須です。');
                passwordField.focus();
                e.preventDefault();
                return;
            }

            if (passwordField.value.length < 6) {
                alert('パスワードは6文字以上で入力してください。');
                passwordField.focus();
                e.preventDefault();
                return;
            }

            // 送信確認
            if (!confirm('変更を保存しますか？')) {
                e.preventDefault();
                return;
            }

            // 送信ボタンを無効化
            const submitBtn = this.querySelector('[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';
        });

        // ポイント入力の制限
        const pointField = document.getElementById('point');
        if (pointField) {
            pointField.addEventListener('input', function() {
                if (this.value < 0) this.value = 0;
                if (this.value > 999999) this.value = 999999;
            });
        }
    });

    // フォームの変更を追跡
    let formChanged = false;
    const formInputs = document.querySelectorAll('#customerForm input, #customerForm select, #customerForm textarea');

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
    document.getElementById('customerForm').addEventListener('submit', function() {
        formChanged = false;
    });
</script>

<?php require 'admin-footer.php'; ?>