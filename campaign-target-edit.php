<?php session_start(); ?>
<?php
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
        <h2><i class="fas fa-box"></i> キャンペーン対象商品設定</h2>
        <p class="page-description">キャンペーン対象の商品選択を行います</p>
    </div>
    <div class="admin-card">
        <div class="table-header">
            <h3><i class="fas fa-list"></i> 商品一覧 </h3>
        </div>
        <div class="table-container">
            <form action="campaign-target-edit-check.php" method="post" autocomplete="off">
                <?php
                echo '<table class="admin-table">';
                echo '<thead>';
                echo '<th></th>';
                echo '<th>商品番号</th>';
                echo '<th></th>'; 
                echo '<th>商品名</th>'; 
                echo '<th>メーカー</th>'; 
                echo '<th>価格</th>';
                echo '</thead>';
                echo '<tbody>';
                if (isset($_REQUEST['keyword'])) {
                    $sql = $pdo->prepare('select * from product where name like ?');
                    $keyword = '%' . $_REQUEST['keyword'] . '%';
                    $sql->bindParam(1, $keyword, PDO::PARAM_STR);
                    $sql->execute();
                } else {
                    //すでにキャンペーン対象になっている商品以外を表示
                    $sql = $pdo->prepare('SELECT p.id, p.name, p.price, m.name AS maker_name FROM product p 
                                        INNER JOIN master m ON m.master_id = p.maker_id  
                                        WHERE m.kbn = 1 AND p.id NOT IN ( 
                                        SELECT ct.target_id FROM campaign_target ct 
                                        INNER JOIN point_campaign pc ON pc.point_campaign_id = ct.point_campaign_id 
                                        WHERE ct.point_campaign_id = ? 
                                        AND ct.del_kbn = 0 
                                        GROUP BY ct.target_id 
                                        ) 
                                        ORDER BY p.id ASC');
                    $sql->bindParam(1, $_SESSION['cp-data'][0]);
                    $sql->execute();
                }
                foreach ($sql as $row) {
                    $images = getImage($row['id'], $pdo);
                    $id = $row['id'];
                    echo '<tr>';
                    echo '<td class="admin-text-center"><input type="checkbox" name="check[]" value="', $id . '+' . $images[0] . '+' . h($row['name']) .'+' . $row['maker_name']. '+' . $row['price'] . '"></td>';
                    echo '<td><strong>', $id, '</strong></td>';
                    echo '<td class="admin-text-center"><img alt="image" src="images/', $images[0], '.jpg"  style="width: 100px; height: 80px; object-fit: contain; border-radius: 4px; background: #f9fafb;"</td>';
                    echo '<td>';
                    echo '<div class="product-name">';
                    echo '<a href="detail.php?id='. h($id).'" target="_blank" class="product-link">'. h($row['name']) . '</a>';
                    echo '</div>';
                    echo '</td>';
                    echo '<td class="admin-text-center"><strong> ', ($row['maker_name']), '</strong></td>';
                    echo '<td class="admin-text-center"><strong>¥ ', number_format($row['price']), '</strong></td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';

                if (isset($_SESSION['error6'])) {
                    echo $_SESSION['error6'];
                }
                unset($_SESSION['error6']);
                ?>
                
                <div class="list-actions">
                    <button formaction="point-detail-edit.php"  class="admin-btn admin-btn-primary">戻る</button>
                    <button class="admin-btn admin-btn-primary">追加</button>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    input[type="checkbox"] {
        display: inline-block !important;
        appearance: auto !important;
        opacity: 1 !important;
        visibility: visible !important;
        position: static !important;
    }
    .list-actions {
        width: 97%;
        margin: 20px 0px 20px 20px;
        display:flex;
        justify-content:space-between;
    }
</style>
<?php require 'admin-footer.php'; ?>