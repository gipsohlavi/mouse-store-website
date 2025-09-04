<?php
// admin-logout-output.php - ログアウト処理実行
session_start();

// セッション変数をすべて削除
$_SESSION = [];

// セッションクッキーも破棄
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// セッションを完全に破棄
session_destroy();

// ログアウト完了画面にリダイレクト
header('Location: admin-login-input.php'); // ログイン画面へリダイレクト
exit;
?>