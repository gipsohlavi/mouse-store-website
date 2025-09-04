<?php require 'common.php'; ?>

<?php
try {
    // トランザクション開始
    $pdo->beginTransaction();

    // 顧客基本情報の登録
    $customer_sql = $pdo->prepare('INSERT INTO customer (name, login, password, point, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
    $customer_sql->bindParam(1, $_REQUEST['name']);
    $customer_sql->bindParam(2, $_REQUEST['login']);
    $customer_sql->bindParam(3, $_REQUEST['password']);
    $customer_sql->bindParam(4, $_REQUEST['point']);
    $customer_sql->execute();

    // 新規登録された顧客IDを取得
    $customer_id = $pdo->lastInsertId();

    // 住所情報が入力されている場合は配送先住所も登録
    if (!empty($_REQUEST['prefecture']) || !empty($_REQUEST['city']) || !empty($_REQUEST['address_line1'])) {
        // 住所名が空の場合はデフォルトで「自宅」を設定
        $address_name = !empty($_REQUEST['address_name']) ? $_REQUEST['address_name'] : '自宅';

        // 受取人名が空の場合は顧客名を使用
        $recipient_name = !empty($_REQUEST['recipient_name']) ? $_REQUEST['recipient_name'] : $_REQUEST['name'];

        // 郵便番号のフォーマット調整（ハイフンを除去）
        $postal_code = !empty($_REQUEST['postal_code']) ? str_replace('-', '', $_REQUEST['postal_code']) : null;

        // 地域IDと離島チェック
        $region_id = !empty($_REQUEST['region_id']) ? $_REQUEST['region_id'] : null;
        $remote_island_check = isset($_REQUEST['remote_island_check']) ? 1 : 0;

        $address_sql = $pdo->prepare('
            INSERT INTO shipping_addresses 
            (customer_id, address_name, recipient_name, postal_code, prefecture, city, address_line1, address_line2, phone, is_default, remote_island_check, region_id, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, NOW(), NOW())
        ');
        $address_sql->bindParam(1, $customer_id);
        $address_sql->bindParam(2, $address_name);
        $address_sql->bindParam(3, $recipient_name);
        $address_sql->bindParam(4, $postal_code);
        $address_sql->bindParam(5, $_REQUEST['prefecture']);
        $address_sql->bindParam(6, $_REQUEST['city']);
        $address_sql->bindParam(7, $_REQUEST['address_line1']);
        $address_sql->bindParam(8, $_REQUEST['address_line2']);
        $address_sql->bindParam(9, $_REQUEST['phone']);
        $address_sql->bindParam(10, $remote_island_check);
        $address_sql->bindParam(11, $region_id);
        $address_sql->execute();
    }

    // トランザクションをコミット
    $pdo->commit();

    $_SESSION['success'] = '新規顧客「' . htmlspecialchars($_REQUEST['name']) . '」を登録しました。（顧客ID: #' . str_pad($customer_id, 4, '0', STR_PAD_LEFT) . '）';
    header('Location:customer-list.php');
    exit;
} catch (PDOException $e) {
    // トランザクションをロールバック
    $pdo->rollBack();

    // エラーメッセージの設定
    if (strpos($e->getMessage(), 'login') !== false) {
        $_SESSION['error'] = 'このログインIDは既に使用されています。別のIDを入力してください。';
    } else {
        $_SESSION['error'] = 'データベースエラーが発生しました: ' . $e->getMessage();
    }

    header('Location:customer-list-add.php');
    exit;
} catch (Exception $e) {
    // トランザクションをロールバック
    $pdo->rollBack();

    $_SESSION['error'] = 'エラーが発生しました: ' . $e->getMessage();
    header('Location:customer-list-add.php');
    exit;
}
?>