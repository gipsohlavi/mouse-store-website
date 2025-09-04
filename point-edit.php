<?php session_start(); ?>
<?php
if (!isset($_SESSION['url']) || !is_array($_SESSION['url'])) {
    $_SESSION['url'] = [];
}
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //0 = 現在のURL　1=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];
unset($_SESSION['cp-data']);
?>
<?php require 'common.php'; ?>
<?php require 'admin-header.php'; ?>
<?php require 'admin-menu.php'; ?>
<?php
$_SESSION['pc_id'][0] = 0;
?>

<div class="admin-container">
    <div class="admin-page-title">
        <h2><i class="fas fa-coins"></i> ポイント設定</h2>
        <p class="page-description">基本ポイント付与率とキャンペーンを管理します</p>
    </div>

    <!-- 基本ポイント付与率 -->
    <div class="admin-card">
        <h3><i class="fas fa-percentage"></i> 基本ポイント付与率</h3>
        <p class="admin-text-muted">全商品に適用される基本のポイント付与率を設定します</p>

        <?php
        if (isset($_SESSION['error1'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error1']);
            echo '</div>';
        }
        ?>

        <form action="point-edit-check.php" method="post">
            <?php
            $id = 1;
            $sql = $pdo->prepare('SELECT * FROM point_campaign WHERE point_campaign_id = ?');
            $sql->bindParam(1, $id);
            $sql->execute();
            $data = $sql->fetch();
            ?>
            <div class="basic-rate-form">
                <input type="hidden" name="point-id" value="<?= $data['point_campaign_id'] ?>">
                <input type="hidden" name="ori-cprate" value="<?= $data['campaign_point_rate'] ?>">

                <div class="rate-input-group">
                    <label class="admin-form-label">現在の基本ポイント付与率</label>
                    <div class="input-with-unit">
                        <input type="text" name="cprate" class="admin-input rate-input"
                            value="<?= ($data['campaign_point_rate'] * 100) ?>">
                        <span class="input-unit">%</span>
                        <button type="submit" name="point" value="point" class="admin-btn admin-btn-primary">
                            <i class="fas fa-save"></i> 変更
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- 適用中・適用予定のキャンペーン -->
    <div class="admin-card">
        <h3><i class="fas fa-bullhorn"></i> 適用中・適用予定のポイントキャンペーン</h3>
        <p class="admin-text-muted">現在および将来適用されるキャンペーンの管理</p>

        <?php
        if (isset($_SESSION['error2'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error2']);
            echo '</div>';
        }

        // ポイント条件一覧の取得
        $sql = $pdo->prepare('SELECT * FROM point_campaign 
                            WHERE point_campaign_id != ?
                            AND del_kbn = 0 
                            AND ((start_date <= ? AND end_date > ?) 
                            OR start_date > ? )
                            ORDER BY priority DESC, start_date ASC');
        $sql->bindParam(1, $id);
        $sql->bindParam(2, $today);
        $sql->bindParam(3, $today);
        $sql->bindParam(4, $today);
        $sql->execute();
        $campaigns = $sql->fetchAll();
        ?>

        <?php if (count($campaigns) > 0): ?>
            <div class="campaign-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>キャンペーン名</th>
                            <th style="width: 100px;">ポイント<br>付与率</th>
                            <th style="width: 140px;">開始日</th>
                            <th style="width: 140px;">終了日</th>
                            <th style="width: 80px;">優先度</th>
                            <th style="width: 80px;">状態</th>
                            <th style="width: 200px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($campaigns as $row) {
                            $start_date = new DateTime($row['start_date']);
                            $end_date = new DateTime($row['end_date']);
                            $now = new DateTime();

                            // 状態判定
                            if ($start_date > $now) {
                                $status = '<span class="admin-status admin-status-pending">予定</span>';
                            } elseif ($end_date < $now) {
                                $status = '<span class="admin-status admin-status-inactive">終了</span>';
                            } else {
                                $status = '<span class="admin-status admin-status-active">実施中</span>';
                            }

                            echo '<form action="point-edit-check.php" method="post">';
                            echo '<tr>';

                            // Hidden fields
                            echo '<input type="hidden" name="ori-cname" value="', h($row['campaign_name']), '">';
                            echo '<input type="hidden" name="ori-cprate" value="', $row['campaign_point_rate'], '">';
                            echo '<input type="hidden" name="ori-start" value="', $row['start_date'], '">';
                            echo '<input type="hidden" name="ori-end" value="', $row['end_date'], '">';
                            echo '<input type="hidden" name="ori-priority" value="', $row['priority'], '">';
                            echo '<input type="hidden" name="ins-date" value="', $row['ins_date'], '">';
                            echo '<input type="hidden" name="upd-date" value="', $row['upd_date'], '">';
                            echo '<input type="hidden" name="pcid" value="', $row['point_campaign_id'], '">';

                            // Table cells
                            echo '<td class="admin-text-center"><strong>', $row['point_campaign_id'], '</strong></td>';

                            echo '<td>';
                            echo '<input type="text" name="cname" class="admin-input table-input" value="', h($row['campaign_name']), '">';
                            echo '</td>';

                            echo '<td>';
                            echo '<div class="input-with-unit-small">';
                            echo '<input type="text" name="cprate" class="admin-input rate-input-small" value="', ($row['campaign_point_rate'] * 100), '">';
                            echo '<span class="input-unit-small">%</span>';
                            echo '</div>';
                            echo '</td>';

                            echo '<td>';
                            echo '<input type="text" name="start" class="admin-input datepicker date-input" value="', date('Y/m/d H:i', strtotime($row['start_date'])), '">';
                            echo '</td>';

                            echo '<td>';
                            echo '<input type="text" name="end" class="admin-input datepicker date-input" value="', date('Y/m/d H:i', strtotime($row['end_date'])), '">';
                            echo '</td>';

                            echo '<td>';
                            echo '<input type="text" name="priority" class="admin-input priority-input" value="', $row['priority'], '">';
                            echo '</td>';

                            echo '<td class="admin-text-center">', $status, '</td>';

                            echo '<td>';
                            echo '<div class="campaign-actions">';
                            echo '<button type="submit" name="point" value="pc-detail" formaction="point-detail-edit.php" class="admin-btn admin-btn-secondary admin-btn-sm">';
                            echo '<i class="fas fa-cog"></i> 詳細';
                            echo '</button>';
                            echo '<button type="submit" name="point" value="pc-change" class="admin-btn admin-btn-primary admin-btn-sm">';
                            echo '<i class="fas fa-edit"></i> 変更';
                            echo '</button>';
                            echo '<button type="submit" name="point" value="pc-del" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm(\'このキャンペーンを削除しますか？\')">';
                            echo '<i class="fas fa-trash"></i> 削除';
                            echo '</button>';
                            echo '</div>';
                            echo '</td>';

                            echo '</tr>';
                            echo '</form>';

                            $_SESSION['pc_id'][$i] = $row['point_campaign_id'];
                            $i++;
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
        <h3><i class="fas fa-plus-circle"></i> ポイントキャンペーンの追加</h3>
        <p class="admin-text-muted">新しいポイントキャンペーンを作成します</p>

        <?php
        if (isset($_SESSION['error3'])) {
            echo '<div class="admin-alert admin-alert-error">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo str_replace(['<p><font color="red">', '</font></p>'], ['', ''], $_SESSION['error3']);
            echo '</div>';
        }
        ?>

        <form action="point-edit-check.php" method="post" autocomplete="off">
            <div class="add-campaign-container">
                <table class="admin-table add-table">
                    <thead>
                        <tr>
                            <th>キャンペーン名</th>
                            <th style="width: 100px;">ポイント<br>付与率</th>
                            <th style="width: 140px;">開始日</th>
                            <th style="width: 140px;">終了日</th>
                            <th style="width: 80px;">優先度</th>
                            <th style="width: 100px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <input type="text" name="cname" class="admin-input table-input" placeholder="例：年末キャンペーン">
                            </td>
                            <td>
                                <div class="input-with-unit-small">
                                    <input type="text" name="cprate" class="admin-input rate-input-small" placeholder="10">
                                    <span class="input-unit-small">%</span>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="start" class="admin-input datepicker date-input" placeholder="開始日時">
                            </td>
                            <td>
                                <input type="text" name="end" class="admin-input datepicker date-input" placeholder="終了日時">
                            </td>
                            <td>
                                <input type="text" name="priority" class="admin-input priority-input" placeholder="1">
                            </td>
                            <td class="admin-text-center">
                                <button type="submit" name="point" value="pc-add" class="admin-btn admin-btn-primary">
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
    // フォーム送信前のバリデーション
    document.addEventListener('DOMContentLoaded', function() {
        // キャンペーン追加フォームのバリデーション
        const addForm = document.querySelector('form[action="point-edit-check.php"]:last-of-type');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                const cname = this.querySelector('[name="cname"]').value.trim();
                const cprate = this.querySelector('[name="cprate"]').value.trim();
                const start = this.querySelector('[name="start"]').value.trim();
                const end = this.querySelector('[name="end"]').value.trim();
                const priority = this.querySelector('[name="priority"]').value.trim();

                if (!cname || !cprate || !start || !end || !priority) {
                    e.preventDefault();
                    alert('すべての項目を入力してください。');
                    return false;
                }

                if (isNaN(parseFloat(cprate)) || parseFloat(cprate) < 0) {
                    e.preventDefault();
                    alert('付与率は0以上の数値を入力してください。');
                    return false;
                }

                if (isNaN(parseInt(priority)) || parseInt(priority) < 0) {
                    e.preventDefault();
                    alert('優先度は0以上の数値を入力してください。');
                    return false;
                }
            });
        }

        // 基本付与率変更の確認
        const basicForm = document.querySelector('form[action="point-edit-check.php"]:first-of-type');
        if (basicForm) {
            basicForm.addEventListener('submit', function(e) {
                const cprate = this.querySelector('[name="cprate"]').value;
                if (!confirm(`基本ポイント付与率を${cprate}%に変更しますか？`)) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        // テーブル内入力フィールドのEnterキー制御
        const tableInputs = document.querySelectorAll('.table-input, .rate-input-small, .date-input, .priority-input');
        tableInputs.forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const changeBtn = this.closest('form').querySelector('[value="pc-change"]');
                    if (changeBtn && confirm('この行の内容を変更しますか？')) {
                        changeBtn.click();
                    }
                }
            });
        });
    });

    // 数値入力制限
    document.addEventListener('DOMContentLoaded', function() {
        const rateInputs = document.querySelectorAll('.rate-input, .rate-input-small');
        const priorityInputs = document.querySelectorAll('.priority-input');

        rateInputs.forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9.]/g, '');
            });
        });

        priorityInputs.forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
    });
</script>

<?php
unset($_SESSION['error1']);
unset($_SESSION['error2']);
unset($_SESSION['error2-2']);
unset($_SESSION['error3']);
require 'admin-footer.php';
?>