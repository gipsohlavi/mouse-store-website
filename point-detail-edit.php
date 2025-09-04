<?php
// point-detail-edit.php - キャンペーン詳細設定
session_start();
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0];
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];
require 'common.php';
require 'admin-header.php';
require 'admin-menu.php';

// セッションデータ処理（元のロジック保持）
if (isset($_REQUEST['pcid'])) {
    $_SESSION['cp-data'][0] = $_REQUEST['pcid'];
}
if (isset($_REQUEST['pcid'])) {
    $_SESSION['cp-data'][1] = $_REQUEST['ori-cname'];
}
if (isset($_REQUEST['pcid'])) {
    $_SESSION['cp-data'][2] = $_REQUEST['ori-cprate'];
}
if (isset($_REQUEST['pcid'])) {
    $_SESSION['cp-data'][3] = $_REQUEST['ori-start'];
}
if (isset($_REQUEST['pcid'])) {
    $_SESSION['cp-data'][4] = $_REQUEST['ori-end'];
}
if (isset($_REQUEST['pcid'])) {
    $_SESSION['cp-data'][5] = $_REQUEST['ori-priority'];
}
if (isset($_REQUEST['pcid'])) {
    $_SESSION['cp-data'][6] = $_REQUEST['ins-date'];
}
if (isset($_REQUEST['pcid'])) {
    $_SESSION['cp-data'][7] = $_REQUEST['upd-date'];
}
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-cog"></i> キャンペーン詳細設定</h2>
        <p class="page-description">キャンペーンID: <?= $_SESSION['cp-data'][0] ?> の詳細設定と対象商品管理</p>
    </div>

    <!-- キャンペーン基本情報 -->
    <div class="admin-card">
        <h3><i class="fas fa-info-circle"></i> キャンペーン基本情報</h3>

        <?php
        if (isset($_SESSION['error2-2'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error2-2']);
            echo '</div>';
        }
        ?>

        <form action="point-edit-check.php" method="post" autocomplete="off">
            <input type="hidden" name="pcid" value="<?= $_SESSION['cp-data'][0] ?>">
            <input type="hidden" name="ori-cname" value="<?= $_SESSION['cp-data'][1] ?>">
            <input type="hidden" name="ori-cprate" value="<?= $_SESSION['cp-data'][2] ?>">
            <input type="hidden" name="ori-start" value="<?= $_SESSION['cp-data'][3] ?>">
            <input type="hidden" name="ori-end" value="<?= $_SESSION['cp-data'][4] ?>">
            <input type="hidden" name="ori-priority" value="<?= $_SESSION['cp-data'][5] ?>">

            <div class="campaign-detail-grid">
                <div class="detail-section">
                    <div class="admin-form-group">
                        <label class="admin-form-label">ID</label>
                        <div class="read-only-field"><?= $_SESSION['cp-data'][0] ?></div>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">キャンペーン名</label>
                        <input type="text" name="cname" class="admin-input" value="<?= h($_SESSION['cp-data'][1]) ?>">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">ポイント付与率 (%)</label>
                        <input type="number" name="cprate" class="admin-input" style="width: 100px;"
                            value="<?= ($_SESSION['cp-data'][2] * 100) ?>" min="0" max="100" step="0.1">
                    </div>
                </div>

                <div class="detail-section">
                    <div class="admin-form-group">
                        <label class="admin-form-label">開始日時</label>
                        <input type="datetime-local" name="start" class="admin-input"
                            value="<?= date('Y-m-d\TH:i', strtotime($_SESSION['cp-data'][3])) ?>">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">終了日時</label>
                        <input type="datetime-local" name="end" class="admin-input"
                            value="<?= date('Y-m-d\TH:i', strtotime($_SESSION['cp-data'][4])) ?>">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">優先度</label>
                        <input type="number" name="priority" class="admin-input" style="width: 100px;"
                            value="<?= $_SESSION['cp-data'][5] ?>" min="0">
                    </div>
                </div>
            </div>

            <div class="meta-info">
                <div class="meta-item">
                    <span class="meta-label">登録日:</span>
                    <span class="meta-value"><?= $_SESSION['cp-data'][6] ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">更新日:</span>
                    <span class="meta-value"><?= $_SESSION['cp-data'][7] ?></span>
                </div>
            </div>

            <div class="admin-btn-group">
                <button type="submit" name="point" value="pc-change" class="admin-btn admin-btn-primary">
                    <i class="fas fa-save"></i> 変更
                </button>
                <button type="submit" name="point" value="pc-del" class="admin-btn admin-btn-danger"
                    onclick="return confirm('このキャンペーンを削除しますか？削除すると復元できません。')">
                    <i class="fas fa-trash"></i> 削除
                </button>
            </div>
        </form>
    </div>

    <!-- 該当商品 -->
    <div class="admin-card">
        <h3><i class="fas fa-shopping-bag"></i> 該当商品</h3>
        <p class="admin-text-muted">このキャンペーンが適用される商品一覧</p>

        <?php
        // キャンペーンに該当する商品情報を取得
        $sql = $pdo->prepare('SELECT ct.id AS ctid, p.id, p.name, p.price FROM point_campaign pc 
                            INNER JOIN campaign_target ct ON ct.point_campaign_id = pc.point_campaign_id 
                            INNER JOIN product p ON p.id = ct.target_id 
                            WHERE pc.point_campaign_id = ? 
                            AND ct.del_kbn = 0');
        $sql->bindParam(1, $_SESSION['cp-data'][0]);
        $sql->execute();
        $products = $sql->fetchAll();
        ?>

        <?php if (count($products) > 0): ?>
            <div class="product-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">商品ID</th>
                            <th style="width: 100px;">画像</th>
                            <th>商品名</th>
                            <th style="width: 120px;">価格</th>
                            <th style="width: 100px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $row): ?>
                            <tr>
                                <td class="admin-text-center"><strong><?= $row['id'] ?></strong></td>
                                <td class="admin-text-center">
                                    <?php
                                    $image_path = "images/{$row['id']}.jpg";
                                    if (!file_exists($image_path)) {
                                        $image_path = "images/no-image.jpg";
                                    }
                                    ?>
                                    <img src="<?= $image_path ?>" alt="<?= h($row['name']) ?>"
                                        style="width: 60px; height: 60px; object-fit: contain; border-radius: 4px; background: #f9fafb;">
                                </td>
                                <td>
                                    <a href="detail.php?id=<?= $row['id'] ?>" target="_blank" class="product-link">
                                        <?= h($row['name']) ?>
                                    </a>
                                </td>
                                <td class="admin-text-right"><strong>¥<?= number_format($row['price']) ?></strong></td>
                                <td class="admin-text-center">
                                    <form action="point-edit-check.php" method="post" style="display: inline;">
                                        <input type="hidden" name="ctid" value="<?= $row['ctid'] ?>">
                                        <button type="submit" name="point" value="pcitem-del"
                                            class="admin-btn admin-btn-danger admin-btn-sm"
                                            onclick="return confirm('この商品をキャンペーン対象から削除しますか？')" formaction="point-edit-update.php">
                                            <i class="fas fa-times"></i> 削除
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="admin-text-center admin-text-muted" style="padding: 2rem;">
                <i class="fas fa-info-circle"></i> このキャンペーンには対象商品が設定されていません
            </div>
        <?php endif; ?>

        <div class="product-actions">
            <form action="campaign-target-edit.php" method="post" autocomplete="off">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus"></i> 対象商品を追加
                </button>
            </form>
        </div>
    </div>

    <!-- ナビゲーション -->
    <div class="admin-card">
        <div class="navigation-actions">
            <a href="point-edit.php" class="admin-btn admin-btn-secondary">
                <i class="fas fa-arrow-left"></i> キャンペーン一覧に戻る
            </a>
        </div>
    </div>
</div>



<script>
    // フォーム送信前のバリデーション
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[action="point-edit-check.php"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                const action = e.submitter.value;
                if (action === 'pc-change') {
                    const start = this.querySelector('[name="start"]').value;
                    const end = this.querySelector('[name="end"]').value;
                    const cprate = this.querySelector('[name="cprate"]').value;

                    if (new Date(start) >= new Date(end)) {
                        e.preventDefault();
                        alert('開始日は終了日より前に設定してください。');
                        return false;
                    }

                    if (parseFloat(cprate) < 0) {
                        e.preventDefault();
                        alert('付与率は0以上を入力してください。');
                        return false;
                    }
                }
            });
        }
    });
</script>

<?php
unset($_SESSION['error2']);
unset($_SESSION['error2-2']);
require 'admin-footer.php';
?>