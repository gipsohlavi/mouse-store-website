<?php
// admin-login-output.php
session_start();
require 'common.php';

try {
    // ログイン名とパスワードを取得
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    // 入力チェック
    if (empty($login) || empty($password)) {
        $_SESSION['eMessage'] = 0; // 未入力エラー
        header('Location: admin-login-input.php');
        exit;
    }

    // `administrator` テーブルからログイン名に一致する情報を取得
    $sql = $pdo->prepare('SELECT * FROM administrator WHERE administrator_id = ?');
    $sql->execute([$login]);
    $admin = $sql->fetch();

    if ($admin && $password === $admin['password']) {
        // ログイン成功
        // 既存のセッションをクリアし、新しいセッションを生成
        $_SESSION = [];
        session_regenerate_id(true);

        $_SESSION['admin'] = [
            'id' => $admin['id'],
            'login' => $admin['login'],
            'name' => $admin['name']
        ];

        // 管理者トップページへリダイレクト
        header('Location: admin-index.php');
        exit;
    } else {
        // ログイン失敗
        $_SESSION['eMessage'] = 1; // ログイン情報不一致エラー
        header('Location: admin-login-input.php');
        exit;
    }

} catch (PDOException $e) {
    // データベース接続エラー
    // エラーメッセージを画面に表示せず、ログに記録する方が安全
    error_log('Login DB Error: ' . $e->getMessage());
    $_SESSION['eMessage'] = 1; 
    header('Location: admin-login-input.php');
    exit;
}