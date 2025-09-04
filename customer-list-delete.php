<?php require 'common.php'; ?>
<?php require 'admin-header.php'; ?>
<?php require 'admin-menu.php'; ?>
<div class="admin-container">
    <div class="admin-page-title">
        <h2>
            <i class="fas fa-check-circle"></i> 
            <?php
            try {
                $sql = $pdo->prepare('delete from customer where id=?');
                $sql->bindParam(1, $_REQUEST['id']);
                $sql->execute();
                echo '<p>顧客ID: '.$_REQUEST['id'].'の削除が完了しました</p>';
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            ?>
        </h2>
    </div>
    <form action="customer-list.php" >
        <button type="submit" class="admin-btn admin-btn-secondary">
            <i class="fas fa-arrow-left"></i> 戻る
        </button>
    </form>
</div>
<?php require 'admin-footer.php'; ?>