<?php require 'common.php'; ?>
<?php require 'header.php'; ?>
<?php require 'admin-menu.php'; ?>

<h2>明細</h2>
<?php
$date = explode("-", $_REQUEST['date']);
echo $date[0] . '年' . $date[1] . '月' . $date[2] . '日　　';
echo '<u>' . $_REQUEST['name'] . ' 様</u>';
?>
<br><br>
<table border=1>
    <th>ID</th>
    <th>商品</th>
    <th>数量</th>
    <th>単価</th>
    <th>金額</th>
    <?php
    $sql = $pdo->prepare('select * from purchase_detail as pd, 
    product as pr where pd.product_id=pr.id and pd.purchase_id=?');
    $sql->bindParam(1, $_REQUEST['id']);
    $sql->execute();
    foreach ($sql as $row) {
        echo '<tr>';
        echo '<td>' . $row['purchase_detail_id'] . '</td>';
        echo '<td>' . $row['name'] . '</td>';
        echo '<td>' . $row['count'] . '</td>';
        echo '<td>' . number_format($row['unit_price']) . '円</td>';
        echo '<td>' . number_format($row['total']) . '円</td>';
        echo '</tr>';
    }
    ?>
</table><br>
<?php
$sql = $pdo->prepare('select * from tax_total as tt, 
    tax as t where tt.tax_id=t.tax_id and tt.id=?');
$sql->bindParam(1, $_REQUEST['id']);
$sql->execute();
foreach ($sql as $row) {
    echo '税率' . $row['tax'] * 100 . '%　';
    echo $row['tax_amount'] . '円<br>';
}
?>
　　　　　　送料　円<br>
　　　　　　<u>総額　<?php echo $_REQUEST['total'] ?>円</u><br><br>
　　　　　　　　　<a href="purchase-list.php">一覧へ</a>
<?php require 'footer.php'; ?>