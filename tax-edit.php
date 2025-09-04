<?php session_start(); ?>
<?php
if (!isset($_SESSION['url']) || !is_array($_SESSION['url'])) {
    $_SESSION['url'] = [];
}
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //0 = 現在のURL　1=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];
?>
<?php require 'common.php'; ?>
<?php require 'admin-header.php'; ?>
<?php require 'admin-menu.php'; ?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fa-solid fa-money-check-dollar"></i> 税率設定</h2>
        <p class="page-description">消費税率の管理を行います</p>
    </div>

    <!-- 現在の税率設定 -->
    <div class="admin-card">
        <h3><i class="fas fa-percentage"></i> 現在の税率設定</h3>
        <p class="admin-text-muted">システムで使用される税率を管理します</p>

        <?php
        if (isset($_SESSION['error1'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error1']);
            echo '</div>';
        }

        if (isset($_SESSION['success'])) {
            echo '<div class="admin-alert admin-alert-success">';
            echo '<i class="fas fa-check-circle"></i>';
            echo $_SESSION['success'];
            echo '</div>';
        }
        ?>

        <?php
        // 現在の税率設定を取得
        $sql = $pdo->prepare('SELECT * FROM tax ORDER BY tax_id ASC');
        $sql->execute();
        $tax_rates = $sql->fetchAll();
        ?>

        <div class="tax-table-container">
            <table class="admin-table tax-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">税ID</th>
                        <th style="width: 100px;">税率</th>
                        <th style="width: 120px;">適用開始日</th>
                        <th style="width: 120px;">適用終了日</th>
                        <th style="width: 80px;">状態</th>
                        <th style="width: 150px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tax_rates) > 0): ?>
                        <?php foreach ($tax_rates as $row): ?>
                            <tr>
                                <form action="tax-edit-check.php" method="post">
                                    <input type="hidden" name="tax-id" value="<?= $row['tax_id'] ?>">
                                    <input type="hidden" name="ori-tax" value="<?= $row['tax'] ?>">
                                    <input type="hidden" name="ori-start" value="<?= $row['tax_start_date'] ?>">
                                    <input type="hidden" name="ori-end" value="<?= $row['tax_end_date'] ?>">

                                    <td class="tax-id-cell">
                                        <strong><?= $row['tax_id'] ?></strong>
                                    </td>

                                    <td class="tax-rate-cell">
                                        <div class="input-with-unit-small">
                                            <input type="text" name="tax-rate" class="admin-input rate-input-small"
                                                value="<?= ($row['tax'] * 100) ?>">
                                            <span class="input-unit-small">%</span>
                                        </div>
                                    </td>

                                    <td class="date-cell">
                                        <input type="date" name="start-date" class="admin-input date-input"
                                            value="<?= $row['tax_start_date'] ?>">
                                    </td>

                                    <td class="date-cell">
                                        <input type="date" name="end-date" class="admin-input date-input"
                                            value="<?= $row['tax_end_date'] ?>">
                                    </td>

                                    <td class="status-cell">
                                        <?php
                                        $start_date = new DateTime($row['tax_start_date']);
                                        $end_date = $row['tax_end_date'] ? new DateTime($row['tax_end_date']) : null;
                                        $now = new DateTime();

                                        if ($start_date > $now) {
                                            echo '<span class="tax-status tax-status-pending">予定</span>';
                                        } elseif ($end_date && $end_date < $now) {
                                            echo '<span class="tax-status tax-status-inactive">終了</span>';
                                        } else {
                                            echo '<span class="tax-status tax-status-active">適用中</span>';
                                        }
                                        ?>
                                    </td>

                                    <td class="actions-cell">
                                        <div class="tax-actions">
                                            <button type="submit" name="tax" value="tax-change" class="admin-btn admin-btn-primary admin-btn-sm">
                                                <i class="fas fa-save"></i> 変更
                                            </button>
                                            <button type="submit" name="tax" value="tax-delete" class="admin-btn admin-btn-danger admin-btn-sm"
                                                onclick="return confirm('この税率設定を削除しますか？\n削除すると元に戻せません。')">
                                                <i class="fas fa-trash"></i> 削除
                                            </button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-content">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <p>税率設定が見つかりませんでした。</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 税率の追加 -->
    <div class="admin-card">
        <h3><i class="fas fa-plus-circle"></i> 新しい税率の追加</h3>
        <p class="admin-text-muted">新しい税率設定を作成します</p>

        <?php
        if (isset($_SESSION['error2'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error2']);
            echo '</div>';
        }
        ?>

        <form action="tax-edit-check.php" method="post" autocomplete="off">
            <div class="add-tax-container">
                <table class="admin-table add-table">
                    <thead>
                        <tr>
                            <th>税率</th>
                            <th style="width: 140px;">適用開始日</th>
                            <th style="width: 140px;">適用終了日</th>
                            <th style="width: 100px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="input-with-unit">
                                    <input type="text" name="tax-rate" class="admin-input rate-input" placeholder="10">
                                    <span class="input-unit">%</span>
                                </div>
                            </td>
                            <td>
                                <input type="date" name="start-date" class="admin-input date-input" required>
                            </td>
                            <td>
                                <input type="date" name="end-date" class="admin-input date-input" placeholder="未設定で無期限">
                            </td>
                            <td class="add-action">
                                <button type="submit" name="tax" value="tax-add" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-plus"></i> 追加
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <!-- 税率について -->
    <div class="admin-card">
        <h3><i class="fas fa-info-circle"></i> 税率設定について</h3>
        <div class="tax-info-grid">
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="info-content">
                    <h4>適用期間</h4>
                    <p>税率は適用開始日から適用終了日まで有効です。終了日が未設定の場合は無期限で適用されます。</p>
                </div>
            </div>

            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="info-content">
                    <h4>複数税率</h4>
                    <p>商品によって異なる税率を適用できます。商品登録時に税IDを指定してください。</p>
                </div>
            </div>

            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="info-content">
                    <h4>注意事項</h4>
                    <p>税率変更は過去の注文には影響しません。変更前に十分確認してください。</p>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    // フォーム送信前のバリデーション
    document.addEventListener('DOMContentLoaded', function() {
        // 税率追加フォームのバリデーション
        const addForm = document.querySelector('form[action="tax-edit-check.php"]:last-of-type');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                const taxRate = this.querySelector('[name="tax-rate"]').value.trim();
                const startDate = this.querySelector('[name="start-date"]').value.trim();

                if (!taxRate || !startDate) {
                    e.preventDefault();
                    alert('税率と適用開始日は必須項目です。');
                    return false;
                }

                if (isNaN(parseFloat(taxRate)) || parseFloat(taxRate) < 0 || parseFloat(taxRate) > 100) {
                    e.preventDefault();
                    alert('税率は0から100の間の数値を入力してください。');
                    return false;
                }
            });
        }

        // 変更フォームの確認
        const changeForms = document.querySelectorAll('form[action="tax-edit-check.php"]:not(:last-of-type)');
        changeForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitButton = e.submitter;
                if (submitButton.value === 'tax-change') {
                    if (!confirm('この税率設定を変更しますか？')) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        });
    });

    // 数値入力制限
    document.addEventListener('DOMContentLoaded', function() {
        const rateInputs = document.querySelectorAll('.rate-input, .rate-input-small');

        rateInputs.forEach(input => {
            input.addEventListener('input', function() {
                // 数値と小数点のみ許可
                this.value = this.value.replace(/[^0-9.]/g, '');

                // 小数点が複数ないかチェック
                const parts = this.value.split('.');
                if (parts.length > 2) {
                    this.value = parts[0] + '.' + parts.slice(1).join('');
                }

                // 100を超えないようにチェック
                if (parseFloat(this.value) > 100) {
                    this.value = '100';
                }
            });

            input.addEventListener('blur', function() {
                if (this.value && !isNaN(this.value)) {
                    // 小数点以下2桁に制限
                    this.value = parseFloat(this.value).toFixed(2);

                    // .00の場合は整数表示
                    if (this.value.endsWith('.00')) {
                        this.value = parseInt(this.value);
                    }
                }
            });
        });
    });

    // テーブル内入力フィールドのEnterキー制御
    document.addEventListener('DOMContentLoaded', function() {
        const tableInputs = document.querySelectorAll('.rate-input-small, .date-input');
        tableInputs.forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const changeBtn = this.closest('form').querySelector('[value="tax-change"]');
                    if (changeBtn && confirm('この行の内容を変更しますか？')) {
                        changeBtn.click();
                    }
                }
            });
        });
    });

    // 日付入力の制御
    document.addEventListener('DOMContentLoaded', function() {
        const dateInputs = document.querySelectorAll('.date-input');

        dateInputs.forEach(input => {
            // 今日の日付を最小値として設定（新規追加の場合）
            if (input.closest('.add-table') && input.name === 'start-date') {
                const today = new Date().toISOString().split('T')[0];
                input.min = today;
            }
        });
    });
</script>

<?php
unset($_SESSION['error1']);
unset($_SESSION['error2']);
unset($_SESSION['success']);
require 'admin-footer.php';
?>