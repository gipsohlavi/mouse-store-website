<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>
<?php require 'menu.php'; ?>
<?php
if (isset($_SESSION['customer'])) {
	$sql = $pdo->prepare(
		'delete from favorite where customer_id=? and product_id=?'
	);
	$sql->bindParam(1, $_SESSION['customer']['id']);
	$sql->bindParam(2, $_REQUEST['id']);
	$sql->execute();
	echo 'お気に入りから商品を削除しました。';
	echo '<hr>';
} else {
	echo 'お気に入りから商品を削除するには、ログインしてください。';
}
require 'favorite.php';
?>
<?php require 'footer.php'; ?>
