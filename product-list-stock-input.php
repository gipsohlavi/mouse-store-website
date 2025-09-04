<?php
session_start();
require 'common.php';
require 'admin-header.php';
require 'admin-menu.php';

// 商品IDの取得と検証
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['stock_error'] = "無効な商品IDです。";
    header('Location: product-list.php');
    exit;
}

$product_id = intval($_GET['id']);

// 商品データを取得
$sql = $pdo->prepare("SELECT * FROM product WHERE id = ?");
$sql->execute([$product_id]);
$product = $sql->fetch();

if (!$product) {
    $_SESSION['stock_error'] = "指定された商品が見つかりません。";
    header('Location: product-list.php');
    exit;
}

// エラーメッセージの表示
if (isset($_SESSION['stock_error'])) {
    $error_message = $_SESSION['stock_error'];
    unset($_SESSION['stock_error']);
}

// 成功メッセージの表示
if (isset($_SESSION['stock_success'])) {
    $success_message = $_SESSION['stock_success'];
    unset($_SESSION['stock_success']);
}
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-cubes"></i> 在庫管理</h2>
        <p class="page-description">商品ID: <?= $product_id ?> - <?= h($product['name']) ?></p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="admin-alert admin-alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= h($error_message) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success_message)): ?>
        <div class="admin-alert admin-alert-success">
            <i class="fas fa-check-circle"></i>
            <?= h($success_message) ?>
        </div>
    <?php endif; ?>

    <div class="stock-layout">
        <div class="main-content">
            <!-- 現在の在庫状況 -->
            <div class="admin-card">
                <h3><i class="fas fa-info-circle"></i> 現在の在庫状況</h3>

                <div class="stock-status-display">
                    <div class="stock-info">
                        <div class="stock-number" id="currentStock">
                            <?= $product['stock_quantity'] ?>
                        </div>
                        <div class="stock-unit">個</div>
                    </div>

                    <div class="stock-status-badge">
                        <?php if ($product['stock_quantity'] > 10): ?>
                            <span class="status-badge status-good">
                                <i class="fas fa-check-circle"></i> 在庫十分
                            </span>
                        <?php elseif ($product['stock_quantity'] > 0): ?>
                            <span class="status-badge status-warning">
                                <i class="fas fa-exclamation-triangle"></i> 在庫少
                            </span>
                        <?php else: ?>
                            <span class="status-badge status-danger">
                                <i class="fas fa-times-circle"></i> 在庫切れ
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="stock-details">
                    <div class="detail-item">
                        <span class="detail-label">販売数量:</span>
                        <span class="detail-value"><?= $product['sales_quantity'] ?>個</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">最終更新:</span>
                        <span class="detail-value"><?= date('Y/m/d H:i', strtotime($product['updated_day'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- 在庫調整 -->
            <div class="admin-card">
                <h3><i class="fas fa-plus-minus"></i> 在庫調整</h3>

                <form action="product-stock.php" method="post" class="stock-adjust-form">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <input type="hidden" name="action" value="adjust">

                    <div class="adjust-section">
                        <div class="form-group">
                            <label class="form-label required">調整数</label>
                            <div class="adjustment-controls">
                                <button type="button" class="adjust-btn minus" onclick="adjustValue(-1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" name="adjustment" id="adjustmentInput" class="adjustment-input"
                                    value="0" min="-<?= $product['stock_quantity'] ?>">
                                <button type="button" class="adjust-btn plus" onclick="adjustValue(1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                プラス値で入庫、マイナス値で出庫
                            </div>
                        </div>

                        <div class="quick-adjust">
                            <span class="quick-label">クイック調整:</span>
                            <button type="button" class="quick-btn" onclick="setAdjustment(10)">+10</button>
                            <button type="button" class="quick-btn" onclick="setAdjustment(50)">+50</button>
                            <button type="button" class="quick-btn" onclick="setAdjustment(100)">+100</button>
                            <button type="button" class="quick-btn danger" onclick="setAdjustment(-10)">-10</button>
                            <button type="button" class="quick-btn danger" onclick="setAdjustment(-50)">-50</button>
                        </div>

                        <div class="form-group">
                            <label class="form-label">調整理由</label>
                            <select name="reason" class="admin-input">
                                <option value="入荷">入荷</option>
                                <option value="返品">返品</option>
                                <option value="損傷">損傷・破損</option>
                                <option value="棚卸調整">棚卸調整</option>
                                <option value="その他">その他</option>
                            </select>
                        </div>

                        <div class="preview-result">
                            <div class="preview-item">
                                <span class="preview-label">現在の在庫:</span>
                                <span class="preview-current"><?= $product['stock_quantity'] ?>個</span>
                            </div>
                            <div class="preview-arrow">→</div>
                            <div class="preview-item">
                                <span class="preview-label">調整後の在庫:</span>
                                <span class="preview-new" id="previewStock"><?= $product['stock_quantity'] ?>個</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="admin-btn admin-btn-primary" id="adjustBtn">
                                <i class="fas fa-save"></i> 在庫を調整
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- 在庫直接設定 -->
            <div class="admin-card">
                <h3><i class="fas fa-edit"></i> 在庫数直接設定</h3>

                <form action="product-stock.php" method="post" class="stock-set-form">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <input type="hidden" name="action" value="set">

                    <div class="set-section">
                        <div class="form-group">
                            <label class="form-label required">新しい在庫数</label>
                            <input type="number" name="new_stock" class="admin-input"
                                value="<?= $product['stock_quantity'] ?>" min="0" required>
                            <div class="form-help">
                                <i class="fas fa-exclamation-triangle"></i>
                                この操作は在庫数を直接書き換えます。慎重に行ってください。
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="admin-btn admin-btn-secondary"
                                onclick="return confirm('在庫数を直接設定してもよろしいですか？')">
                                <i class="fas fa-edit"></i> 在庫数を設定
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="sidebar-content">
            <!-- 商品情報 -->
            <div class="admin-card">
                <h3><i class="fas fa-box"></i> 商品情報</h3>

                <div class="product-summary">
                    <?php
                    $image_path = "images/{$product['image_name1']}.jpg";
                    if (!file_exists($image_path)) {
                        $image_path = "images/no-image.jpg";
                    }
                    ?>
                    <img src="<?= $image_path ?>" alt="<?= h($product['name']) ?>" class="product-image">

                    <div class="product-info">
                        <div class="product-name"><?= h($product['name']) ?></div>
                        <div class="product-price">¥<?= number_format($product['price']) ?></div>
                    </div>
                </div>

                <div class="product-stats">
                    <div class="stat-item">
                        <span class="stat-label">商品ID:</span>
                        <span class="stat-value"><?= $product_id ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">販売数量:</span>
                        <span class="stat-value"><?= $product['sales_quantity'] ?>個</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">登録日:</span>
                        <span class="stat-value"><?= date('Y/m/d', strtotime($product['created_day'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- アクションメニュー -->
            <div class="admin-card">
                <h3><i class="fas fa-bolt"></i> アクション</h3>
                <div class="action-menu">
                    <a href="product-edit.php?id=<?= $product_id ?>" class="action-item">
                        <i class="fas fa-edit"></i>
                        <span>商品を編集</span>
                    </a>

                    <a href="detail.php?id=<?= $product_id ?>" target="_blank" class="action-item">
                        <i class="fas fa-external-link-alt"></i>
                        <span>商品ページを表示</span>
                    </a>

                    <a href="product-list.php" class="action-item">
                        <i class="fas fa-list"></i>
                        <span>商品一覧に戻る</span>
                    </a>
                </div>
            </div>

            <!-- 在庫アラート設定 -->
            <div class="admin-card">
                <h3><i class="fas fa-bell"></i> 在庫アラート</h3>

                <div class="alert-settings">
                    <div class="alert-item">
                        <div class="alert-label">在庫少アラート:</div>
                        <div class="alert-value">10個以下</div>
                    </div>
                    <div class="alert-item">
                        <div class="alert-label">在庫切れアラート:</div>
                        <div class="alert-value">0個</div>
                    </div>
                </div>

                <div class="alert-status">
                    <?php if ($product['stock_quantity'] == 0): ?>
                        <div class="alert-message danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            在庫切れです！早急に入荷してください。
                        </div>
                    <?php elseif ($product['stock_quantity'] <= 10): ?>
                        <div class="alert-message warning">
                            <i class="fas fa-exclamation-circle"></i>
                            在庫が少なくなっています。
                        </div>
                    <?php else: ?>
                        <div class="alert-message good">
                            <i class="fas fa-check-circle"></i>
                            在庫は十分にあります。
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const currentStock = <?= $product['stock_quantity'] ?>;

    function adjustValue(amount) {
        const input = document.getElementById('adjustmentInput');
        const newValue = parseInt(input.value) + amount;
        const minValue = -currentStock;

        if (newValue >= minValue) {
            input.value = newValue;
            updatePreview();
        }
    }

    function setAdjustment(value) {
        const input = document.getElementById('adjustmentInput');
        const minValue = -currentStock;

        if (value >= minValue) {
            input.value = value;
            updatePreview();
        }
    }

    function updatePreview() {
        const adjustment = parseInt(document.getElementById('adjustmentInput').value) || 0;
        const newStock = currentStock + adjustment;
        const previewElement = document.getElementById('previewStock');

        previewElement.textContent = newStock + '個';

        // 色を変更
        if (newStock < 0) {
            previewElement.style.color = 'var(--admin-danger)';
        } else if (newStock === 0) {
            previewElement.style.color = 'var(--admin-danger)';
        } else if (newStock <= 10) {
            previewElement.style.color = 'var(--admin-warning)';
        } else {
            previewElement.style.color = 'var(--admin-success)';
        }

        // ボタンの有効/無効を切り替え
        const adjustBtn = document.getElementById('adjustBtn');
        adjustBtn.disabled = newStock < 0;
    }

    // 初期化
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('adjustmentInput').addEventListener('input', updatePreview);
        updatePreview();
    });
</script>

<?php require 'admin-footer.php'; ?>