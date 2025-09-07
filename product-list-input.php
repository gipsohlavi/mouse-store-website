<?php
session_start();
require 'common.php';
require 'admin-header.php';
require 'admin-menu.php';

if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //0 = 現在のURL　1=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];


// マスターデータ取得
function getMasterData($pdo, $kbn_id)
{
    $sql = $pdo->prepare('SELECT master_id, name FROM master WHERE kbn = ? ORDER BY master_id');
    $sql->execute([$kbn_id]);
    return $sql->fetchAll();
}

$makers = getMasterData($pdo, 1);       // メーカー
$colors = getMasterData($pdo, 2);       // 色
$connections = getMasterData($pdo, 3);  // 接続方式
$sensors = getMasterData($pdo, 5);      // センサー
$shapes = getMasterData($pdo, 7);       // 形状
$switches = getMasterData($pdo, 21);    // スイッチ
$mcus = getMasterData($pdo, 22);        // MCU
$charging_ports = getMasterData($pdo, 23); // 充電端子
$software = getMasterData($pdo, 24);    // ソフトウェア
$materials = getMasterData($pdo, 18);   // 素材
$surface_finishes = getMasterData($pdo, 19); // 表面仕上げ

// 税率取得（本日時点で有効な税率）
$tax_sql = $pdo->prepare('SELECT tax_id, tax FROM tax 
    WHERE tax_start_date <= CURDATE() 
      AND (tax_end_date IS NULL OR tax_end_date > CURDATE())
    ORDER BY tax_id');
$tax_sql->execute();
$tax_rates = $tax_sql->fetchAll();

// エラーメッセージの表示
if (isset($_SESSION['product_add_error'])) {
    $error_message = $_SESSION['product_add_error'];
    unset($_SESSION['product_add_error']);
}

// 入力値の復元
$form_data = isset($_SESSION['product_add_data']) ? $_SESSION['product_add_data'] : [];
unset($_SESSION['product_add_data']);
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-plus-circle"></i> 新商品追加</h2>
        <p class="page-description">新しい商品を登録します</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="admin-alert admin-alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= h($error_message) ?>
        </div>
    <?php endif; ?>

    <form action="product-list-input-confirm.php" method="post" enctype="multipart/form-data" id="productForm">
        <div class="add-layout">
            <div class="main-content">
                <!-- 基本情報 -->
                <div class="admin-card">
                    <h3><i class="fas fa-info-circle"></i> 基本情報</h3>

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label required">商品名</label>
                            <input type="text" name="name" class="admin-input"
                                value="<?= h($form_data['name'] ?? '') ?>"
                                placeholder="例: GravaStar Mercury X Pro" required>
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                お客様に表示される商品名を入力してください
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">価格（円）</label>
                            <input type="number" name="price" class="admin-input"
                                value="<?= h($form_data['price'] ?? '') ?>"
                                min="1" placeholder="例: 19800" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">税率</label>
                            <select name="tax_id" class="admin-input" required>
                                <option value="">選択してください</option>
                                <?php foreach ($tax_rates as $tax): ?>
                                    <option value="<?= $tax['tax_id'] ?>"
                                        <?= ($form_data['tax_id'] ?? '') == $tax['tax_id'] ? 'selected' : '' ?>>
                                        <?= ($tax['tax'] * 100) ?>%
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">初期在庫数</label>
                            <input type="number" name="stock_quantity" class="admin-input"
                                value="<?= h($form_data['stock_quantity'] ?? '') ?>"
                                min="0" placeholder="例: 50" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">メーカー</label>
                            <select name="maker_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($makers as $maker): ?>
                                    <option value="<?= $maker['master_id'] ?>"
                                        <?= ($form_data['maker_id'] ?? '') == $maker['master_id'] ? 'selected' : '' ?>>
                                        <?= h($maker['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="recommend" value="1"
                                <?= isset($form_data['recommend']) ? 'checked' : '' ?>>
                            <div class="checkbox-custom"></div>
                            <span>おすすめ商品として表示</span>
                        </label>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="on_sale" value="1"
                                <?= isset($form_data['on_sale']) ? 'checked' : '' ?>>
                            <div class="checkbox-custom"></div>
                            <span>セール商品として表示</span>
                        </label>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="for_gift" value="1"
                                <?= isset($form_data['for_gift']) ? 'checked' : '' ?>>
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
                                        <?= ($form_data['sensor_id'] ?? '') == $sensor['master_id'] ? 'selected' : '' ?>>
                                        <?= h($sensor['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">最大DPI</label>
                            <input type="number" name="dpi_max" class="admin-input"
                                value="<?= h($form_data['dpi_max'] ?? '') ?>"
                                min="100" placeholder="例: 32000">
                        </div>

                        <div class="form-group">
                            <label class="form-label">ポーリングレート（Hz）</label>
                            <input type="number" name="polling_rate" class="admin-input"
                                value="<?= h($form_data['polling_rate'] ?? '') ?>"
                                min="125" placeholder="例: 8000">
                        </div>

                        <div class="form-group">
                            <label class="form-label">ボタン数</label>
                            <input type="number" name="button_count" class="admin-input"
                                value="<?= h($form_data['button_count'] ?? '3') ?>"
                                min="3">
                        </div>

                        <div class="form-group">
                            <label class="form-label">スイッチ</label>
                            <select name="switch_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($switches as $switch): ?>
                                    <option value="<?= $switch['master_id'] ?>"
                                        <?= ($form_data['switch_id'] ?? '') == $switch['master_id'] ? 'selected' : '' ?>>
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
                                        <?= ($form_data['mcu_id'] ?? '') == $mcu['master_id'] ? 'selected' : '' ?>>
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
                                value="<?= h($form_data['lod_distance_mm'] ?? '') ?>"
                                step="0.1" min="0" placeholder="例: 1.0">
                        </div>

                        <div class="form-group">
                            <label class="form-label">デバウンス時間（ms）</label>
                            <input type="number" name="debounce_time_ms" class="admin-input"
                                value="<?= h($form_data['debounce_time_ms'] ?? '') ?>"
                                min="0" placeholder="例: 2">
                        </div>

                        <div class="form-group">
                            <label class="form-label">クリック遅延（ms）</label>
                            <input type="number" name="click_delay_ms" class="admin-input"
                                value="<?= h($form_data['click_delay_ms'] ?? '') ?>"
                                step="0.001" min="0" placeholder="例: 0.769">
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="lod_adjustable" value="1"
                                <?= isset($form_data['lod_adjustable']) ? 'checked' : '' ?>>
                            <div class="checkbox-custom"></div>
                            <span>LOD調整可能</span>
                        </label>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="debounce_adjustable" value="1"
                                <?= isset($form_data['debounce_adjustable']) ? 'checked' : '' ?>>
                            <div class="checkbox-custom"></div>
                            <span>デバウンス調整可能</span>
                        </label>
                    </div>

                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="motion_sync_support" value="1"
                                <?= isset($form_data['motion_sync_support']) ? 'checked' : '' ?>>
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
                                value="<?= h($form_data['width'] ?? '') ?>"
                                min="30" placeholder="例: 63">
                        </div>

                        <div class="form-group">
                            <label class="form-label">奥行き（mm）</label>
                            <input type="number" name="depth" class="admin-input"
                                value="<?= h($form_data['depth'] ?? '') ?>"
                                min="60" placeholder="例: 124">
                        </div>

                        <div class="form-group">
                            <label class="form-label">高さ（mm）</label>
                            <input type="number" name="height" class="admin-input"
                                value="<?= h($form_data['height'] ?? '') ?>"
                                min="20" placeholder="例: 40">
                        </div>

                        <div class="form-group">
                            <label class="form-label required">重量（g）</label>
                            <input type="number" name="weight" class="admin-input"
                                value="<?= h($form_data['weight'] ?? '') ?>"
                                min="20" placeholder="例: 49" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">ケーブル長（cm）</label>
                            <input type="number" name="cable_length" class="admin-input"
                                value="<?= h($form_data['cable_length'] ?? '') ?>"
                                min="0" placeholder="例: 180">
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                ワイヤレスの場合は0を入力
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">形状</label>
                            <select name="shape_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($shapes as $shape): ?>
                                    <option value="<?= $shape['master_id'] ?>"
                                        <?= ($form_data['shape_id'] ?? '') == $shape['master_id'] ? 'selected' : '' ?>>
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
                                        <?= in_array($connection['master_id'], $form_data['connections'] ?? []) ? 'checked' : '' ?>>
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
                                value="<?= h($form_data['battery_capacity_mah'] ?? '') ?>"
                                min="0" placeholder="例: 400">
                        </div>

                        <div class="form-group">
                            <label class="form-label">バッテリー持続時間（時間）</label>
                            <input type="number" name="battery_life_hours" class="admin-input"
                                value="<?= h($form_data['battery_life_hours'] ?? '') ?>"
                                min="0" placeholder="例: 18">
                        </div>

                        <div class="form-group">
                            <label class="form-label">充電端子</label>
                            <select name="charging_port_id" class="admin-input">
                                <option value="">選択してください</option>
                                <?php foreach ($charging_ports as $port): ?>
                                    <option value="<?= $port['master_id'] ?>"
                                        <?= ($form_data['charging_port_id'] ?? '') == $port['master_id'] ? 'selected' : '' ?>>
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
                                        <?= in_array($color['master_id'], $form_data['colors'] ?? []) ? 'checked' : '' ?>>
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
                                        <?= ($form_data['material_id'] ?? '') == $material['master_id'] ? 'selected' : '' ?>>
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
                                        <?= ($form_data['surface_finish_id'] ?? '') == $finish['master_id'] ? 'selected' : '' ?>>
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
                                        <?= ($form_data['software_id'] ?? '') == $sw['master_id'] ? 'selected' : '' ?>>
                                        <?= h($sw['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">サイズカテゴリー</label>
                            <select name="size_category" class="admin-input">
                                <option value="">選択してください</option>
                                <option value="S" <?= ($form_data['size_category'] ?? '') === 'S' ? 'selected' : '' ?>>S</option>
                                <option value="M" <?= ($form_data['size_category'] ?? '') === 'M' ? 'selected' : '' ?>>M</option>
                                <option value="L" <?= ($form_data['size_category'] ?? '') === 'L' ? 'selected' : '' ?>>L</option>
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
                                    <div class="image-preview" id="preview<?= $i ?>">
                                        <i class="fas fa-plus"></i>
                                        <span>JPEGファイルを選択</span>
                                    </div>
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="form-help">
                        <i class="fas fa-info-circle"></i>
                        画像1はメイン画像として使用されます。JPEGファイルのみ対応（推奨サイズ: 800x800px）
                    </div>
                </div>

                <!-- 商品説明（detail.php 用） -->
                <div class="admin-card">
                    <h3><i class="fas fa-file-alt"></i> 商品説明（詳細ページ表示）</h3>

                    <div class="form-group full-width">
                        <label class="form-label">商品概要（短文）</label>
                        <textarea name="product_overview" class="admin-input" rows="4" placeholder="例: 近未来的なデザインと49gの軽量ボディ。競技レベルの精度と応答性を両立した万能マウスです。"><?= h($form_data['product_overview'] ?? '') ?></textarea>
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i>
                            商品ページの冒頭に表示される短い説明です。2〜4行を目安に入力してください。
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">商品詳細レビュー（段落は「■」で区切り）</label>
                        <textarea name="product_detailed_review" class="admin-input" rows="14" placeholder="例:
■ シェル
マグネシウム合金フレームで高い剛性。
■ コーティング
滑りにくいマット仕様で長時間使用にも最適。
■ 性能
32000DPI / 8KHz対応でプロレベルの精度。"><?= h($form_data['product_detailed_review'] ?? '') ?></textarea>
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i>
                            detail.php では「■」で段落を分割して見出しとして表示します。太字見出しを付けたい箇所の先頭に半角の「■」を入れてください。
                        </div>
                    </div>
                </div>
            </div>

            <div class="sidebar-content">
                <!-- 入力ガイド -->
                <div class="admin-card">
                    <h3><i class="fas fa-question-circle"></i> 入力ガイド</h3>
                    <div class="guide-section">
                        <div class="guide-item">
                            <i class="fas fa-asterisk"></i>
                            <div>
                                <strong>必須項目</strong>
                                <p>赤いアスタリスク（*）が付いている項目は必須入力です。</p>
                            </div>
                        </div>

                        <div class="guide-item">
                            <i class="fas fa-image"></i>
                            <div>
                                <strong>商品画像</strong>
                                <p>メイン画像（画像1）は必ず設定してください。最大7枚まで登録可能です。</p>
                            </div>
                        </div>

                        <div class="guide-item">
                            <i class="fas fa-tags"></i>
                            <div>
                                <strong>マスターデータ</strong>
                                <p>メーカーや色などの選択肢が不足している場合は、システム管理者にお問い合わせください。</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- プレビュー -->
                <div class="admin-card">
                    <h3><i class="fas fa-eye"></i> プレビュー</h3>
                    <div id="productPreview">
                        <div class="preview-image">
                            <img id="previewMainImage" src="images/no-image.jpg" alt="商品プレビュー">
                        </div>
                        <div class="preview-info">
                            <div class="preview-name" id="previewName">商品名を入力してください</div>
                            <div class="preview-price" id="previewPrice">¥0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- フォームアクション -->
        <div class="admin-card">
            <div class="form-actions">
                <a href="product-list.php" class="admin-btn admin-btn-outline">
                    <i class="fas fa-arrow-left"></i> キャンセル
                </a>
                <button type="button" class="admin-btn admin-btn-secondary" onclick="saveDraft()">
                    <i class="fas fa-save"></i> 下書き保存
                </button>
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus"></i> 商品を追加
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    // フォーム入力のリアルタイムプレビュー
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.querySelector('input[name="name"]');
        const priceInput = document.querySelector('input[name="price"]');
        const image1Input = document.querySelector('input[name="image1"]');

        // 商品名プレビュー
        nameInput.addEventListener('input', function() {
            const previewName = document.getElementById('previewName');
            previewName.textContent = this.value || '商品名を入力してください';
        });

        // 価格プレビュー
        priceInput.addEventListener('input', function() {
            const previewPrice = document.getElementById('previewPrice');
            const price = parseInt(this.value) || 0;
            previewPrice.textContent = '¥' + price.toLocaleString();
        });

        // 画像プレビュー
        for (let i = 1; i <= 7; i++) {
            const input = document.querySelector(`input[name="image${i}"]`);
            const preview = document.getElementById(`preview${i}`);

            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // ファイルサイズチェック (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('ファイルサイズが大きすぎます（5MB以下にしてください）');
                        this.value = '';
                        return;
                    }

                    // ファイル形式チェック
                    if (!file.type.match(/^image\/(jpeg|jpg)$/)) {
                        alert('JPEGファイルのみ対応しています');
                        this.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="プレビュー">`;
                        preview.classList.add('has-image');

                        // メイン画像の場合はプレビューも更新
                        if (i === 1) {
                            document.getElementById('previewMainImage').src = e.target.result;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });

    // 下書き保存
    function saveDraft() {
        const formData = new FormData(document.getElementById('productForm'));
        formData.append('action', 'draft');

        // ボタンを無効化してローディング表示
        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';

        fetch('product-list-confirm.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('下書きを保存しました。');
                } else {
                    alert('下書きの保存に失敗しました: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('下書きの保存中にエラーが発生しました。');
            })
            .finally(() => {
                // ボタンを元に戻す
                button.disabled = false;
                button.innerHTML = originalText;
            });
    }

    // フォーム送信前の確認とバリデーション
    document.getElementById('productForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // 必須項目チェック
        const requiredFields = this.querySelectorAll('[required]');
        let hasError = false;
        let firstErrorField = null;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = '#dc2626';
                field.style.boxShadow = '0 0 0 2px rgba(220, 38, 38, 0.1)';
                hasError = true;
                if (!firstErrorField) {
                    firstErrorField = field;
                }
            } else {
                field.style.borderColor = '';
                field.style.boxShadow = '';
            }
        });

        if (hasError) {
            alert('必須項目を入力してください。');
            firstErrorField.focus();
            return false;
        }

        // メイン画像の確認
        const image1 = document.querySelector('input[name="image1"]');
        if (!image1.files.length) {
            alert('メイン画像（画像1）を選択してください。');
            image1.focus();
            return false;
        }

        // 価格の妥当性チェック
        const price = parseInt(document.querySelector('input[name="price"]').value);
        if (price < 1 || price > 9999999) {
            alert('価格は1円以上999万円以下で入力してください。');
            document.querySelector('input[name="price"]').focus();
            return false;
        }

        // 在庫数の妥当性チェック
        const stock = parseInt(document.querySelector('input[name="stock_quantity"]').value);
        if (stock < 0) {
            alert('在庫数は0以上で入力してください。');
            document.querySelector('input[name="stock_quantity"]').focus();
            return false;
        }

        // 最終確認
        if (!confirm('商品を追加してもよろしいですか？\n\n※登録後も編集可能です。')) {
            return false;
        }

        // 送信ボタンを無効化してローディング表示
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 追加中...';

        // フォームを送信
        this.submit();
    });

    // リアルタイムバリデーション
    document.addEventListener('DOMContentLoaded', function() {
        // 価格入力時の検証
        const priceInput = document.querySelector('input[name="price"]');
        priceInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value && (value < 1 || value > 9999999)) {
                this.style.borderColor = '#dc2626';
                this.setCustomValidity('価格は1円以上999万円以下で入力してください');
            } else {
                this.style.borderColor = '';
                this.setCustomValidity('');
            }
        });

        // 重量入力時の検証
        const weightInput = document.querySelector('input[name="weight"]');
        weightInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value && value < 20) {
                this.style.borderColor = '#d97706';
                this.title = '20g未満のマウスは珍しいです。正しい値か確認してください。';
            } else {
                this.style.borderColor = '';
                this.title = '';
            }
        });

        // DPI入力時の検証
        const dpiInput = document.querySelector('input[name="dpi_max"]');
        dpiInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value && value > 50000) {
                this.style.borderColor = '#d97706';
                this.title = '50,000 DPIを超えるマウスは珍しいです。正しい値か確認してください。';
            } else {
                this.style.borderColor = '';
                this.title = '';
            }
        });

        // 商品名の文字数チェック
        const nameInput = document.querySelector('input[name="name"]');
        nameInput.addEventListener('input', function() {
            const length = this.value.length;
            if (length > 200) {
                this.style.borderColor = '#dc2626';
                this.setCustomValidity('商品名は200文字以内で入力してください');
            } else {
                this.style.borderColor = '';
                this.setCustomValidity('');
            }
        });
    });

    // ページ離脱時の確認
    let hasUnsavedChanges = false;

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('productForm');
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            input.addEventListener('change', function() {
                hasUnsavedChanges = true;
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '入力内容が保存されていません。ページを離れてもよろしいですか？';
            }
        });

        // フォーム送信時は警告を無効化
        form.addEventListener('submit', function() {
            hasUnsavedChanges = false;
        });
    });
</script>

<?php require 'admin-footer.php'; ?>