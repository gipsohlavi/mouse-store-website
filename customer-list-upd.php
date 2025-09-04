<?php require 'common.php'; ?>

<?php
try {
    // 顧客基本情報の更新
    $sql = $pdo->prepare('UPDATE customer SET name=?, login=?, password=?, point=?, updated_at=NOW() WHERE id=?');
    $sql->bindParam(1, $_REQUEST['name']);
    $sql->bindParam(2, $_REQUEST['login']);
    $sql->bindParam(3, $_REQUEST['password']);
    $sql->bindParam(4, $_REQUEST['point']);
    $sql->bindParam(5, $_REQUEST['id']);
    $sql->execute();

    $_SESSION['success'] = '顧客情報を更新しました。';
    header('Location:customer-list.php');
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = 'エラーが発生しました: ' . $e->getMessage();
    header('Location:customer-list-edit.php?id=' . $_REQUEST['id']);
    exit;
}
?>