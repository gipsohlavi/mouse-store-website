<?php session_start(); ?>
<?php
if (!isset($_SESSION['url']) || !is_array($_SESSION['url'])) {
    $_SESSION['url'] = [];
}
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0];
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];
?>
<?php
unset($_SESSION['postage']);
unset($_SESSION['post-terms']);
unset($_SESSION['pos-region']);
unset($_SESSION['post-island']);
?>
<?php require 'common.php'; ?>
<?php require 'admin-header.php'; ?>
<?php require 'admin-menu.php'; ?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-truck"></i> 配送料設定</h2>
        <p class="page-description">地域別配送料、離島配送料、配送料無料条件を管理します</p>
    </div>

    <!-- 地域ごとの配送料 -->
    <div class="admin-card">
        <h3><i class="fas fa-map-marked-alt"></i> 地域ごとの配送料</h3>

        <?php
        if (isset($_SESSION['error1'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error1']);
            echo '</div>';
        }
        ?>

        <?php
        $region_id = 11;
        $sql = $pdo->prepare('SELECT p.region_id, m.name, p.postage_fee FROM postage p 
                            INNER JOIN master m ON p.region_id = m.master_id AND kbn = ? 
                            WHERE start_date <= ? ');
        $sql->bindParam(1, $region_id);
        $sql->bindParam(2, $today);
        $sql->execute();
        ?>

        <form action="postage-edit-check.php" method="post" class="regional-postage-form">
            <div class="postage-table-container">
                <table class="admin-table postage-table">
                    <thead>
                        <tr>
                            <th style="width: 200px;">地域</th>
                            <th style="width: 150px;">配送料（税込）</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $count = 0;
                        foreach ($sql as $row) {
                            echo '<tr>';
                            echo '<input type="hidden" name="ori-postage-num' . $count . '" value="' . $row['postage_fee'] . '">';
                            echo '<input type="hidden" name="region-id' . $count . '" value="' . $row['region_id'] . '">';
                            echo '<input type="hidden" name="postage-name' . $count . '" value="' . htmlspecialchars($row['name']) . '">';

                            echo '<td class="region-name">';
                            echo '<i class="fas fa-map-marker-alt region-icon"></i>';
                            echo htmlspecialchars($row['name']);
                            echo '</td>';

                            echo '<td class="postage-amount">';
                            echo '<div class="amount-input-group">';
                            echo '<input type="text" name="postage-num' . $count . '" class="admin-input amount-input" value="' . number_format($row['postage_fee']) . '">';
                            echo '<span class="currency-unit">円</span>';
                            echo '</div>';
                            echo '</td>';

                            echo '</tr>';
                            $count++;
                        }
                        echo '<input type="hidden" name="count" value="' . $count . '">';
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="form-actions">
                <button type="submit" name="postage" value="pos-region" class="admin-btn admin-btn-primary">
                    <i class="fas fa-save"></i> 地域別配送料を更新
                </button>
            </div>
        </form>
    </div>

    <!-- 離島配送料 -->
    <div class="admin-card">
        <h3><i class="fas fa-island-tropical"></i> 離島配送料</h3>

        <?php
        if (isset($_SESSION['error2'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error2']);
            echo '</div>';
        }
        ?>

        <form action="postage-edit-check.php" method="post" class="island-postage-form">
            <?php
            $sql = $pdo->query('SELECT remote_island_fee FROM postage_remote_island 
                                WHERE end_date IS NULL ');
            $data = $sql->fetch();

            echo '<input type="hidden" name="ori-post-island" value="' . $data['remote_island_fee'] . '">';
            ?>

            <div class="island-setting">
                <div class="setting-description">
                    <p>離島および一部地域への追加配送料を設定します。通常の地域別配送料に加算されます。</p>
                </div>

                <div class="island-amount-setting">
                    <label class="form-label">追加配送料（税込）</label>
                    <div class="amount-input-group large">
                        <input type="text" name="post-island" class="admin-input amount-input-large"
                            value="<?= number_format($data['remote_island_fee']) ?>">
                        <span class="currency-unit">円</span>
                    </div>
                    <div class="form-help">通常配送料に追加される金額です</div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="postage" value="pos-island" class="admin-btn admin-btn-primary">
                        <i class="fas fa-save"></i> 離島配送料を変更
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- 現在の配送料無料条件 -->
    <div class="admin-card">
        <h3><i class="fas fa-shipping-fast"></i> 現在適用中の配送料無料条件</h3>

        <div class="current-free-condition">
            <?php
            $sql = $pdo->prepare('SELECT postage_fee_free FROM postage_free 
                                WHERE (start_date <= ? 
                                AND end_date > ?
                                AND del_kbn = 0 ) 
                                OR end_date IS NULL 
                                ORDER BY postage_fee_free_id DESC LIMIT 1');
            $sql->bindParam(1, $today);
            $sql->bindParam(2, $today);
            $sql->execute();
            $data = $sql->fetch();
            ?>

            <div class="current-condition-display">
                <div class="condition-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <div class="condition-info">
                    <div class="condition-amount">¥<?= number_format($data['postage_fee_free']) ?></div>
                    <div class="condition-text">以上のお買い上げで配送料無料</div>
                </div>
                <div class="condition-status">
                    <span class="status-badge active">適用中</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 基本配送料無料条件 -->
    <div class="admin-card">
        <h3><i class="fas fa-cog"></i> 基本配送料無料条件</h3>

        <?php
        if (isset($_SESSION['error3'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error3']);
            echo '</div>';
        }
        ?>

        <form action="postage-edit-check.php" method="post" class="basic-terms-form">
            <?php
            $id = 1;
            $sql = $pdo->prepare('SELECT * FROM postage_free 
                                WHERE postage_fee_free_id = ?');
            $sql->bindParam(1, $id);
            $sql->execute();
            $data = $sql->fetch();
            echo '<input type="hidden" name="ori-post-terms" value="' . $data['postage_fee_free'] . '">';
            ?>

            <div class="basic-terms-setting">
                <div class="setting-description">
                    <p>通常時の配送料無料条件を設定します。キャンペーン期間外に適用される基本条件です。</p>
                </div>

                <div class="terms-amount-setting">
                    <label class="form-label">無料条件金額（税込）</label>
                    <div class="amount-input-group large">
                        <input type="text" name="post-terms" class="admin-input amount-input-large"
                            value="<?= number_format($data['postage_fee_free']) ?>">
                        <span class="currency-unit">円以上</span>
                    </div>
                    <div class="form-help">この金額以上のご注文で配送料が無料になります</div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="postage" value="posterms" class="admin-btn admin-btn-primary">
                        <i class="fas fa-save"></i> 基本無料条件を変更
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- キャンペーン配送料無料条件 -->
    <div class="admin-card">
        <h3><i class="fas fa-bullhorn"></i> 適用中・適用予定のキャンペーン配送料無料条件</h3>

        <?php
        if (isset($_SESSION['error4'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error4']);
            echo '</div>';
        }
        ?>

        <?php
        $sql = $pdo->prepare('SELECT * FROM postage_free 
                            WHERE postage_fee_free_id != ?
                            AND del_kbn = 0 
                            AND ((start_date <= ? AND end_date > ?) 
                            OR start_date > ? )
                            ORDER BY start_date ASC');
        $sql->bindParam(1, $id);
        $sql->bindParam(2, $today);
        $sql->bindParam(3, $today);
        $sql->bindParam(4, $today);
        $sql->execute();
        $campaigns = $sql->fetchAll();
        ?>

        <?php if (count($campaigns) > 0): ?>
            <div class="campaign-table-container">
                <table class="admin-table campaign-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 120px;">無料条件<br>（～円以上）</th>
                            <th style="width: 140px;">開始時期</th>
                            <th style="width: 140px;">終了時期</th>
                            <th style="width: 80px;">状態</th>
                            <th style="width: 160px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($campaigns as $row) {
                            $start_date = new DateTime($row['start_date']);
                            $end_date = new DateTime($row['end_date']);
                            $now = new DateTime();

                            if ($start_date > $now) {
                                $status = '<span class="status-badge pending">予定</span>';
                            } elseif ($end_date < $now) {
                                $status = '<span class="status-badge inactive">終了</span>';
                            } else {
                                $status = '<span class="status-badge active">実施中</span>';
                            }

                            echo '<tr>';
                            echo '<form action="postage-edit-check.php" method="post" class="campaign-form">';
                            echo '<input type="hidden" name="ori-post-terms" value="' . $row['postage_fee_free'] . '">';
                            echo '<input type="hidden" name="ori-post-start" value="' . $row['start_date'] . '">';
                            echo '<input type="hidden" name="ori-post-end" value="' . $row['end_date'] . '">';
                            echo '<input type="hidden" name="post-id" value="' . $row['postage_fee_free_id'] . '">';

                            echo '<td class="campaign-id"><strong>' . $row['postage_fee_free_id'] . '</strong></td>';

                            echo '<td>';
                            echo '<div class="amount-input-group small">';
                            echo '<input type="text" name="post-terms" class="admin-input amount-input-small" value="' . number_format($row['postage_fee_free']) . '">';
                            echo '<span class="currency-unit-small">円</span>';
                            echo '</div>';
                            echo '</td>';

                            echo '<td>';
                            echo '<input type="text" name="post-start" class="admin-input datepicker date-input" value="' . date('Y/m/d H:i', strtotime($row['start_date'])) . '">';
                            echo '</td>';

                            echo '<td>';
                            echo '<input type="text" name="post-end" class="admin-input datepicker date-input" value="' . date('Y/m/d H:i', strtotime($row['end_date'])) . '">';
                            echo '</td>';

                            echo '<td class="status-cell">' . $status . '</td>';

                            echo '<td class="actions-cell">';
                            echo '<div class="campaign-actions">';
                            echo '<button type="submit" name="postage" value="posterms-change" class="admin-btn admin-btn-primary admin-btn-sm">';
                            echo '<i class="fas fa-save"></i> 変更';
                            echo '</button>';
                            echo '<button type="submit" name="postage" value="posterms-del" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm(\'このキャンペーンを削除しますか？\')">';
                            echo '<i class="fas fa-trash"></i> 削除';
                            echo '</button>';
                            echo '</div>';
                            echo '</td>';

                            echo '</form>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-info-circle"></i>
                現在、適用中または予定のキャンペーンはありません
            </div>
        <?php endif; ?>
    </div>

    <!-- キャンペーン追加 -->
    <div class="admin-card">
        <h3><i class="fas fa-plus-circle"></i> キャンペーン配送料無料条件の追加</h3>

        <?php
        if (isset($_SESSION['error5'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error5']);
            echo '</div>';
        }
        if (isset($_SESSION['error6'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error6']);
            echo '</div>';
        }
        ?>

        <form action="postage-edit-check.php" method="post" autocomplete="off" class="add-campaign-form">
            <div class="add-campaign-container">
                <table class="admin-table add-table">
                    <thead>
                        <tr>
                            <th>無料条件<br>（～円以上）</th>
                            <th style="width: 140px;">開始時期</th>
                            <th style="width: 140px;">終了時期</th>
                            <th style="width: 100px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="amount-input-group">
                                    <input type="text" name="post-terms" class="admin-input amount-input" placeholder="例：10000">
                                    <span class="currency-unit">円</span>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="post-start" class="admin-input datepicker date-input" placeholder="開始日時">
                            </td>
                            <td>
                                <input type="text" name="post-end" class="admin-input datepicker date-input" placeholder="終了日時">
                            </td>
                            <td class="add-action">
                                <button type="submit" name="postage" value="posterms-add" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-plus"></i> 追加
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>



<script>
    // 数値入力の制御
    document.addEventListener('DOMContentLoaded', function() {
        const amountInputs = document.querySelectorAll('.amount-input, .amount-input-large, .amount-input-small');

        amountInputs.forEach(input => {
            // カンマ区切りの処理
            input.addEventListener('blur', function() {
                const value = this.value.replace(/,/g, '');
                if (!isNaN(value) && value !== '') {
                    this.value = Number(value).toLocaleString();
                }
            });

            // フォーカス時はカンマを除去
            input.addEventListener('focus', function() {
                this.value = this.value.replace(/,/g, '');
            });

            // 数字のみ入力許可
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
    });

    // フォーム送信前の確認
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form[action="postage-edit-check.php"]');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitButton = e.submitter;
                const action = submitButton.value;

                let confirmMessage = '';

                switch (action) {
                    case 'pos-region':
                        confirmMessage = '地域別配送料を更新しますか？';
                        break;
                    case 'pos-island':
                        confirmMessage = '離島配送料を変更しますか？';
                        break;
                    case 'posterms':
                        confirmMessage = '基本配送料無料条件を変更しますか？';
                        break;
                    case 'posterms-change':
                        confirmMessage = 'このキャンペーンの内容を変更しますか？';
                        break;
                    case 'posterms-del':
                        confirmMessage = 'このキャンペーンを削除しますか？\n削除すると元に戻せません。';
                        break;
                    case 'posterms-add':
                        confirmMessage = '新しいキャンペーンを追加しますか？';
                        break;
                }

                if (confirmMessage && !confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    });

    // 入力値の妥当性チェック
    function validateAmount(input) {
        const value = parseInt(input.value.replace(/,/g, ''));
        return !isNaN(value) && value >= 0 && value <= 999999;
    }

    // キャンペーン追加フォームの特別なバリデーション
    document.querySelector('.add-campaign-form')?.addEventListener('submit', function(e) {
        e.stopImmediatePropagation(); //他の submit リスナーを止める

        const terms = this.querySelector('[name="post-terms"]').value.replace(/,/g, '');
        const start = this.querySelector('[name="post-start"]').value;
        const end = this.querySelector('[name="post-end"]').value;

        if (!terms || !start || !end) {
            e.preventDefault();
            alert('すべての項目を入力してください。');
            return false;
        }

        if (isNaN(terms) || parseInt(terms) < 0) {
            e.preventDefault();
            alert('有効な金額を入力してください。');
            return false;
        }

        const startDate = new Date(start.replace(/\//g, '-'));
        const endDate = new Date(end.replace(/\//g, '-'));

        if (startDate >= endDate) {
            e.preventDefault();
            alert('終了日時は開始日時より後に設定してください。');
            return false;
        }
    });
</script>

<?php
unset($_SESSION['error1']);
unset($_SESSION['error2']);
unset($_SESSION['error3']);
unset($_SESSION['error4']);
unset($_SESSION['error5']);
?>

<?php require 'admin-footer.php'; ?>