<?php
session_start();
require 'common.php';
require 'admin-header.php';
require 'admin-menu.php';

if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //0 = 現在のURL　1=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];


// 商品IDの取得と検証
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['product_edit_error'] = "無効な商品IDです。";
    header('Location: product-list.php');
    exit;
}

$product_id = intval($_GET['id']);

// 商品データを取得
$sql = $pdo->prepare("
    SELECT p.*, tax.tax
    FROM product p
    LEFT JOIN tax ON p.tax_id = tax.tax_id AND tax.tax_end_date IS NULL
    WHERE p.id = ?
");
$sql->execute([$product_id]);
$product = $sql->fetch();

if (!$product) {
    $_SESSION['product_edit_error'] = "指定された商品が見つかりません。";
    header('Location: product-list.php');
    exit;
}
// マスターデータ取得
function getMasterData($pdo, $kbn_id)
{
    $sql = $pdo->prepare('SELECT master_id, name FROM master WHERE kbn = ? ORDER BY master_id');
    $sql->execute([$kbn_id]);
    return $sql->fetchAll();
}

// 商品の関連マスターデータを取得
function getProductMasters($pdo, $product_id, $kbn_id)
{
    $sql = $pdo->prepare("
        SELECT master_id 
        FROM product_master_relation 
        WHERE product_id = ? AND kbn_id = ?
    ");
    $sql->execute([$product_id, $kbn_id]);
    return array_column($sql->fetchAll(), 'master_id');
}

$makers = getMasterData($pdo, 1);
$colors = getMasterData($pdo, 2);
$connections = getMasterData($pdo, 3);
$sensors = getMasterData($pdo, 5);
$shapes = getMasterData($pdo, 7);
$switches = getMasterData($pdo, 21);
$mcus = getMasterData($pdo, 22);
$charging_ports = getMasterData($pdo, 23);
$software = getMasterData($pdo, 24);
$materials = getMasterData($pdo, 18);
$surface_finishes = getMasterData($pdo, 19);

// 税率取得
$tax_sql = $pdo->prepare('SELECT tax_id, tax FROM tax WHERE tax_end_date IS NULL ORDER BY tax_id');
$tax_sql->execute();
$tax_rates = $tax_sql->fetchAll();

// 商品の関連データ取得
$product_makers = getProductMasters($pdo, $product_id, 1);
$product_colors = getProductMasters($pdo, $product_id, 2);
$product_connections = getProductMasters($pdo, $product_id, 3);
$product_sensors = getProductMasters($pdo, $product_id, 5);
$product_shapes = getProductMasters($pdo, $product_id, 7);
$product_switches = getProductMasters($pdo, $product_id, 21);
$product_mcus = getProductMasters($pdo, $product_id, 22);
$product_charging_ports = getProductMasters($pdo, $product_id, 23);
$product_software = getProductMasters($pdo, $product_id, 24);
$product_materials = getProductMasters($pdo, $product_id, 18);
$product_surface_finishes = getProductMasters($pdo, $product_id, 19);

// エラーメッセージの表示
if (isset($_SESSION['product_add_error'])) {
    $error_message = $_SESSION['product_add_error'];
    unset($_SESSION['product_add_error']);
}

// 成功メッセージの表示
if (isset($_SESSION['product_add_success'])) {
    $success_message = $_SESSION['product_add_success'];
    unset($_SESSION['product_add_success']);
}
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-edit"></i> 商品編集</h2>
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

    <form action="product-list-input-confirm.php" method="post" enctype="multipart/form-data" id="productEditForm">
        <input type="hidden" name="product_id" value="<?= $product_id ?>">

        <div class="edit-layout">
            <div class="main-content">
                <!-- 基本情報 -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> 基本情報</h3>
                        <div class="header-actions">
                            <span class="status-info">
                                登録日: <?= date('Y/m/d H:i', strtotime($product['created_day'])) ?>
                            </span>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label required">商品名</label>
                            <input type="text" name="name" class="admin-input"
                                value="<?= h($product['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">価格（円）</label>
                            <input type="number" name="price" class="admin-input"
                                value="<?= $product['price'] ?>" min="1" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">税率</label>
                            <select name="tax_id" class="admin-input" required>
                                <?php foreach ($tax_rates as $tax): ?>
                                    <option value="<?= $tax['tax_id'] ?>"
                                        <?= $product['tax_id'] == $tax['tax_id'] ? 'selected' : '' ?>>
                                        <?= ($tax['tax'] * 100) ?>%
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">在庫数</label>
                            <input type="number" name="stock_quantity" class="admin-input"
                                value="<?= $product['stock_quantity'] ?>" min="0" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">メーカー</label>
                            <select name="maker_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($makers as $maker): ?>
                                    <option value="<?= $maker['master_id'] ?>"
                                        <?= in_array($maker['master_id'], $product_makers) ? 'selected' : '' ?>>
                                        <?= h($maker['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">販売数量</label>
                            <input type="number" name="sales_quantity" class="admin-input"
                                value="<?= $product['sales_quantity'] ?>" min="0" readonly>
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                販売数量は自動計算されます
                            </div>
                        </div>
                    </div>

                    <div class="checkbox-grid">
                        <label class="checkbox-label">
                            <input type="checkbox" name="recommend" value="1"
                                <?= $product['recommend'] ? 'checked' : '' ?>>
                            <div class="checkbox-custom"></div>
                            <span>おすすめ商品として表示</span>
                        </label>

                        <label class="checkbox-label">
                            <input type="checkbox" name="on_sale" value="1"
                                <?= $product['on_sale'] ? 'checked' : '' ?>>
                            <div class="checkbox-custom"></div>
                            <span>セール商品として表示</span>
                        </label>

                        <label class="checkbox-label">
                            <input type="checkbox" name="for_gift" value="1"
                                <?= $product['for_gift'] ? 'checked' : '' ?>>
                            <div class="checkbox-custom"></div>
                            <span>ギフト対応可能</span>
                        </label>
                    </div>
                </div>

                <!-- 技術仕様 -->
                <div class="admin-card">
                    <h3><i class="fas fa-microchip"></i> 技術仕様</h3>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">センサー</label>
                            <select name="sensor_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($sensors as $sensor): ?>
                                    <option value="<?= $sensor['master_id'] ?>"
                                        <?= in_array($sensor['master_id'], $product_sensors) ? 'selected' : '' ?>>
                                        <?= h($sensor['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">最大DPI</label>
                            <input type="number" name="dpi_max" class="admin-input"
                                value="<?= $product['dpi_max'] ?>" min="100">
                        </div>

                        <div class="form-group">
                            <label class="form-label">ポーリングレート（Hz）</label>
                            <input type="number" name="polling_rate" class="admin-input"
                                value="<?= $product['polling_rate'] ?>" min="125">
                        </div>

                        <div class="form-group">
                            <label class="form-label">ボタン数</label>
                            <input type="number" name="button_count" class="admin-input"
                                value="<?= $product['button_count'] ?>" min="2">
                        </div>

                        <div class="form-group">
                            <label class="form-label">スイッチ</label>
                            <select name="switch_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($switches as $switch): ?>
                                    <option value="<?= $switch['master_id'] ?>"
                                        <?= in_array($switch['master_id'], $product_switches) ? 'selected' : '' ?>>
                                        <?= h($switch['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">MCU</label>
                            <select name="mcu_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($mcus as $mcu): ?>
                                    <option value="<?= $mcu['master_id'] ?>"
                                        <?= in_array($mcu['master_id'], $product_mcus) ? 'selected' : '' ?>>
                                        <?= h($mcu['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">LOD距離（mm）</label>
                            <input type="number" name="lod_distance_mm" class="admin-input"
                                value="<?= $product['lod_distance_mm'] ?>" step="0.1" min="0">
                        </div>

                        <div class="form-group">
                            <label class="form-label">デバウンス時間（ms）</label>
                            <input type="number" name="debounce_time_ms" class="admin-input"
                                value="<?= $product['debounce_time_ms'] ?>" min="0">
                        </div>

                        <div class="form-group">
                            <label class="form-label">クリック遅延（ms）</label>
                            <input type="number" name="click_delay_ms" class="admin-input"
                                value="<?= $product['click_delay_ms'] ?>" step="0.001" min="0">
                        </div>
                    </div>

                    <div class="checkbox-grid">
                        <label class="checkbox-label">
                            <input type="checkbox" name="lod_adjustable" value="1"
                                <?= $product['lod_adjustable'] ? 'checked' : '' ?>>
                            <div class="checkbox-custom"></div>
                            <span>LOD調整可能</span>
                        </label>

                        <label class="checkbox-label">
                            <input type="checkbox" name="debounce_adjustable" value="1"
                                <?= $product['debounce_adjustable'] ? 'checked' : '' ?>>
                            <div class="checkbox-custom"></div>
                            <span>デバウンス調整可能</span>
                        </label>

                        <label class="checkbox-label">
                            <input type="checkbox" name="motion_sync_support" value="1"
                                <?= $product['motion_sync_support'] ? 'checked' : '' ?>>
                            <div class="checkbox-custom"></div>
                            <span>Motion Sync対応</span>
                        </label>
                    </div>
                </div>

                <!-- 物理仕様 -->
                <div class="admin-card">
                    <h3><i class="fas fa-ruler-combined"></i> 物理仕様</h3>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">幅（mm）</label>
                            <input type="number" name="width" class="admin-input"
                                value="<?= $product['width'] ?>" min="30">
                        </div>

                        <div class="form-group">
                            <label class="form-label">奥行き（mm）</label>
                            <input type="number" name="depth" class="admin-input"
                                value="<?= $product['depth'] ?>" min="60">
                        </div>

                        <div class="form-group">
                            <label class="form-label">高さ（mm）</label>
                            <input type="number" name="height" class="admin-input"
                                value="<?= $product['height'] ?>" min="20">
                        </div>

                        <div class="form-group">
                            <label class="form-label required">重量（g）</label>
                            <input type="number" name="weight" class="admin-input"
                                value="<?= $product['weight'] ?>" min="20" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">ケーブル長（cm）</label>
                            <input type="number" name="cable_length" class="admin-input"
                                value="<?= $product['cable_length'] ?>" min="0">
                        </div>

                        <div class="form-group">
                            <label class="form-label">形状</label>
                            <select name="shape_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($shapes as $shape): ?>
                                    <option value="<?= $shape['master_id'] ?>"
                                        <?= in_array($shape['master_id'], $product_shapes) ? 'selected' : '' ?>>
                                        <?= h($shape['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 接続・バッテリー -->
                <div class="admin-card">
                    <h3><i class="fas fa-plug"></i> 接続・バッテリー</h3>

                    <div class="form-group">
                        <label class="form-label">接続方式</label>
                        <div class="checkbox-grid">
                            <?php foreach ($connections as $connection): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="connections[]" value="<?= $connection['master_id'] ?>"
                                        <?= in_array($connection['master_id'], $product_connections) ? 'checked' : '' ?>>
                                    <div class="checkbox-custom"></div>
                                    <span><?= h($connection['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">バッテリー容量（mAh）</label>
                            <input type="number" name="battery_capacity_mah" class="admin-input"
                                value="<?= $product['battery_capacity_mah'] ?>" min="0">
                        </div>

                        <div class="form-group">
                            <label class="form-label">バッテリー持続時間（時間）</label>
                            <input type="number" name="battery_life_hours" class="admin-input"
                                value="<?= $product['battery_life_hours'] ?>" min="0">
                        </div>

                        <div class="form-group">
                            <label class="form-label">充電端子</label>
                            <select name="charging_port_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($charging_ports as $port): ?>
                                    <option value="<?= $port['master_id'] ?>"
                                        <?= in_array($port['master_id'], $product_charging_ports) ? 'selected' : '' ?>>
                                        <?= h($port['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- デザイン・素材 -->
                <div class="admin-card">
                    <h3><i class="fas fa-palette"></i> デザイン・素材</h3>

                    <div class="form-group">
                        <label class="form-label">カラー</label>
                        <div class="checkbox-grid">
                            <?php foreach ($colors as $color): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="colors[]" value="<?= $color['master_id'] ?>"
                                        <?= in_array($color['master_id'], $product_colors) ? 'checked' : '' ?>>
                                    <div class="checkbox-custom"></div>
                                    <span><?= h($color['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">素材</label>
                            <select name="material_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value="<?= $material['master_id'] ?>"
                                        <?= in_array($material['master_id'], $product_materials) ? 'selected' : '' ?>>
                                        <?= h($material['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">表面仕上げ</label>
                            <select name="surface_finish_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($surface_finishes as $finish): ?>
                                    <option value="<?= $finish['master_id'] ?>"
                                        <?= in_array($finish['master_id'], $product_surface_finishes) ? 'selected' : '' ?>>
                                        <?= h($finish['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">ソフトウェア</label>
                            <select name="software_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($software as $sw): ?>
                                    <option value="<?= $sw['master_id'] ?>"
                                        <?= in_array($sw['master_id'], $product_software) ? 'selected' : '' ?>>
                                        <?= h($sw['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">サイズカテゴリー</label>
                            <select name="size_category" class="admin-input">
                                <option value="">選択してください</option>
                                <option value="S" <?= $product['size_category'] === 'S' ? 'selected' : '' ?>>S</option>
                                <option value="M" <?= $product['size_category'] === 'M' ? 'selected' : '' ?>>M</option>
                                <option value="L" <?= $product['size_category'] === 'L' ? 'selected' : '' ?>>L</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 商品画像 -->
                <div class="admin-card">
                    <h3><i class="fas fa-images"></i> 商品画像</h3>

                    <div class="image-upload-grid">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <div class="image-upload-item">
                                <label class="image-upload-label">
                                    <span class="image-label">画像<?= $i ?><?= $i === 1 ? '（メイン）' : '' ?></span>
                                    <input type="file" name="image<?= $i ?>" accept="image/jpeg,image/jpg" class="image-input">

                                    <!-- hiddenは常に出力 -->
                                    <input type="hidden" name="current_image<?= $i ?>" value="<?= !empty($product["image_name{$i}"]) ? htmlspecialchars($product["image_name{$i}"]) : '' ?>">

                                    <div class="image-preview <?= !empty($product["image_name{$i}"]) ? 'has-image' : '' ?>" id="preview<?= $i ?>">
                                        <?php if (!empty($product["image_name{$i}"])): ?>
                                            <img src="images/<?= $product["image_name{$i}"] ?>.jpg" alt="現在の画像">
                                            <div class="image-overlay">
                                                <span>クリックして変更</span>
                                            </div>
                                        <?php else: ?>
                                            <i class="fas fa-plus"></i>
                                            <span>JPEGファイルを選択</span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                                <?php if (!empty($product["image_name{$i}"])): ?>
                                    <button type="button" class="image-delete-btn" onclick="deleteImage(<?= $i ?>)">
                                        <i class="fas fa-trash"></i> 削除
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- 商品説明（detail.php 用） -->
                <div class="admin-card">
                    <h3><i class="fas fa-file-alt"></i> 商品説明（詳細ページ表示）</h3>

                    <div class="form-group full-width">
                        <label class="form-label">商品概要（短文）</label>
                        <textarea name="product_overview" class="admin-input" rows="4" placeholder="例: 近未来的なデザインと49gの軽量ボディ。競技レベルの精度と応答性を両立。"><?= h($product['product_overview'] ?? '') ?></textarea>
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i>
                            商品ページ冒頭の短い説明です（2〜4行推奨）。
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">商品詳細レビュー（段落は「■」で区切り）</label>
                        <textarea name="product_detailed_review" class="admin-input" rows="14" placeholder="『■ 見出し』で段落分割し、本文を続けて入力してください。"><?= h($product['product_detailed_review'] ?? '') ?></textarea>
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i>
                            detail.php では「■」を見出しとして解釈して段落表示します。
                        </div>
                    </div>
                </div>
            </div>

            <div class="sidebar-content">
                <!-- 商品情報サマリー -->
                <div class="admin-card summary-card">
                    <h3><i class="fas fa-info-circle"></i> 商品情報</h3>

                    <div class="summary-item">
                        <span class="summary-label">商品ID</span>
                        <span class="summary-value"><?= $product_id ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">現在価格</span>
                        <span class="summary-value">¥<?= number_format($product['price']) ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">税込価格</span>
                        <span class="summary-value">¥<?= number_format($product['price'] * (1 + $product['tax'])) ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">在庫数</span>
                        <span class="summary-value <?= $product['stock_quantity'] == 0 ? 'text-danger' : ($product['stock_quantity'] <= 10 ? 'text-warning' : 'text-success') ?>">
                            <?= $product['stock_quantity'] ?>個
                        </span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">販売数</span>
                        <span class="summary-value"><?= $product['sales_quantity'] ?>個</span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">状態</span>
                        <span class="summary-value">
                            <?php if ($product['recommend']): ?>
                                <span class="status-badge recommend">おすすめ</span>
                            <?php endif; ?>
                            <?php if ($product['on_sale']): ?>
                                <span class="status-badge sale">セール</span>
                            <?php endif; ?>
                            <?php if (!$product['recommend'] && !$product['on_sale']): ?>
                                <span class="status-badge regular">通常</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- クイックアクション -->
                <div class="admin-card">
                    <h3><i class="fas fa-bolt"></i> クイックアクション</h3>
                    <div class="action-menu">
                        <a href="detail.php?id=<?= $product_id ?>" target="_blank" class="action-item">
                            <i class="fas fa-external-link-alt"></i>
                            <span>商品ページを表示</span>
                        </a>

                        <a href="product-list-stock.php?id=<?= $product_id ?>" class="action-item">
                            <i class="fas fa-cubes"></i>
                            <span>在庫管理</span>
                        </a>

                        <button type="button" class="action-item" onclick="duplicateProduct()">
                            <i class="fas fa-copy"></i>
                            <span>商品を複製</span>
                        </button>

                        <button type="button" class="action-item danger" onclick="deleteProduct()">
                            <i class="fas fa-trash"></i>
                            <span>商品を削除</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- フォームアクション -->
        <div class="admin-card">
            <div class="form-actions">
                <a href="product-list.php" class="admin-btn admin-btn-outline">
                    <i class="fas fa-arrow-left"></i> 商品一覧に戻る
                </a>
                <button type="button" class="admin-btn admin-btn-secondary" onclick="previewChanges()">
                    <i class="fas fa-eye"></i> プレビュー
                </button>
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-save"></i> 変更を保存
                </button>
            </div>
        </div>
    </form>
</div>



<script>
    // 商品削除
    function deleteProduct() {
        if (confirm('この商品を削除してもよろしいですか？\n\n※この操作は取り消せません。関連する注文履歴なども削除されます。')) {
            if (confirm('本当に削除しますか？この操作は元に戻せません。')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'product-delete.php';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = <?= $product_id ?>;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    }

    // 商品複製
    function duplicateProduct() {
        if (confirm('この商品を複製して新しい商品として登録しますか？')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'product-duplicate.php';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'id';
            input.value = <?= $product_id ?>;

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // 画像削除
    function deleteImage(imageNum) {
        if (confirm(`画像${imageNum}を削除してもよろしいですか？`)) {
            const preview = document.getElementById(`preview${imageNum}`);
            preview.innerHTML = '<i class="fas fa-plus"></i><span>JPEGファイルを選択</span>';
            preview.classList.remove('has-image');

            // 削除フラグを追加
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `delete_image${imageNum}`;
            input.value = '1';
            document.getElementById('productEditForm').appendChild(input);

            // 削除ボタンを非表示
            const deleteBtn = preview.parentElement.querySelector('.image-delete-btn');
            if (deleteBtn) {
                deleteBtn.style.display = 'none';
            }
        }
    }

    // プレビュー機能
    function previewChanges() {
        const productName = document.querySelector('input[name="name"]').value;
        const productPrice = document.querySelector('input[name="price"]').value;

        // 新しいウィンドウでプレビューを開く
        const previewWindow = window.open('', 'preview', 'width=800,height=600,scrollbars=yes');
        previewWindow.document.write(`
        <html>
        <head>
            <title>商品プレビュー - ${productName}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .preview-header { border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
                .preview-name { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
                .preview-price { font-size: 20px; color: #e74c3c; }
            </style>
        </head>
        <body>
            <div class="preview-header">
                <div class="preview-name">${productName}</div>
                <div class="preview-price">¥${parseInt(productPrice).toLocaleString()}</div>
            </div>
            <p>※これは編集中の商品のプレビューです。実際の商品ページとは異なる場合があります。</p>
        </body>
        </html>
    `);
    }

    // 画像プレビュー
    document.addEventListener('DOMContentLoaded', function() {
        for (let i = 1; i <= 7; i++) {
            const input = document.querySelector(`input[name="image${i}"]`);
            const preview = document.getElementById(`preview${i}`);

            if (input && preview) {
                input.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.innerHTML = `<img src="${e.target.result}" alt="プレビュー"><div class="image-overlay"><span>クリックして変更</span></div>`;
                            preview.classList.add('has-image');

                            // 削除ボタンを追加
                            let deleteBtn = preview.parentElement.querySelector('.image-delete-btn');
                            if (!deleteBtn) {
                                deleteBtn = document.createElement('button');
                                deleteBtn.type = 'button';
                                deleteBtn.className = 'image-delete-btn';
                                deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                                deleteBtn.onclick = () => deleteImage(i);
                                preview.parentElement.appendChild(deleteBtn);
                            }
                            deleteBtn.style.display = 'flex';
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        }
    });

    // フォーム送信前の確認
    document.getElementById('productEditForm').addEventListener('submit', function(e) {
        if (!confirm('商品情報を更新してもよろしいですか？')) {
            e.preventDefault();
            return false;
        }
    });

    // リアルタイム価格更新
    document.querySelector('input[name="price"]').addEventListener('input', function() {
        const price = parseInt(this.value) || 0;
        const taxRate = <?= $product['tax'] ?>;
        const taxIncluded = Math.floor(price * (1 + taxRate));

        document.querySelector('.summary-item:nth-child(3) .summary-value').textContent = '¥' + price.toLocaleString();
        document.querySelector('.summary-item:nth-child(4) .summary-value').textContent = '¥' + taxIncluded.toLocaleString();
    });

    // リアルタイム在庫更新
    document.querySelector('input[name="stock_quantity"]').addEventListener('input', function() {
        const stock = parseInt(this.value) || 0;
        const stockElement = document.querySelector('.summary-item:nth-child(5) .summary-value');

        stockElement.textContent = stock + '個';
        stockElement.className = 'summary-value ' +
            (stock === 0 ? 'text-danger' : (stock <= 10 ? 'text-warning' : 'text-success'));
    });
</script>

<?php require 'admin-footer.php'; ?>