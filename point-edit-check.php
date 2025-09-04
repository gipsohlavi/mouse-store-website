<?php
session_start();
if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //0 = 現在のURL　1=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];
//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/point(-detail)?-edit.php$/', $_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: point-edit.php');
}
?>
<?php require 'common.php'; ?>
<?php
unset($_SESSION['error1']);
unset($_SESSION['error2']);
unset($_SESSION['error2']);
unset($_SESSION['error3']);
if (isset($_REQUEST['point'])) {
    $flag = true;
    $_SESSION['point'] = $_REQUEST['point'];

    switch ($_SESSION['point']) {

        //基本ポイント付与率の変更
        case 'point':
            //空かチェック
            if ($_REQUEST['cprate'] === '') {
                $_SESSION['error1'] = '<p><font color="red">値を入力してください</font></p>';
                $flag = false;
                break;
            }
            //数字かチェック
            if (!is_numeric($_REQUEST['cprate'])) {
                $_SESSION['error1'] = '<p><font color="red">数値を入力してください</font></p>';
                $flag = false;
                break;
            }
            //0以上かチェック
            if ($_REQUEST['cprate'] < 0) {
                $_SESSION['error1'] = '<p><font color="red">0以上の数値を入力してください</font></p>';
                $flag = false;
                break;
            }
            //同じ内容かチェック
            if ((int)$_REQUEST['cprate'] === (int)($_REQUEST['ori-cprate'] * 100)) {
                $_SESSION['error1'] = '<p><font color="red">更新する場合は違う値を入力してください</font></p>';
                $flag = false;
                break;
            }
            $_SESSION['cprate'] = h($_REQUEST['cprate'] / 100);

            break;


        //キャンペーンの変更
        case 'pc-change':
            //空かチェック
            if (
                $_REQUEST['cname'] === ''
                || $_REQUEST['cprate'] === ''
                || $_REQUEST['start'] === ''
                || $_REQUEST['end'] === ''
                || $_REQUEST['priority'] === ''
            ) {
                $_SESSION['error2'] = '<p><font color="red">値を入力してください</font></p>';
                $_SESSION['error2-2'] = '<p><font color="red">値を入力してください</font></p>';
                $flag = false;
                break;
            }

            //数字かチェック
            if (
                !is_numeric($_REQUEST['cprate'])
                || !is_numeric($_REQUEST['cprate'])
            ) {
                if (!is_numeric($_REQUEST['cprate'])) {
                    $_SESSION['error2'] += '<p><font color="red">ポイント付与率には数値を入力してください</font></p>';
                    $_SESSION['error2-2'] += '<p><font color="red">ポイント付与率には数値を入力してください</font></p>';
                }
                if (!is_numeric($_REQUEST['priority'])) {
                    $_SESSION['error2'] += '<p><font color="red">優先度には数値を入力してください</font></p>';
                    $_SESSION['error2-2'] += '<p><font color="red">優先度には数値を入力してください</font></p>';
                }
                $flag = false;
                break;
            }

            //０以上かチェック
            if (
                $_REQUEST['cprate'] < 0
                || $_REQUEST['priority'] < 0
            ) {
                if ($_REQUEST['cprate'] < 0) {
                    $_SESSION['error2'] += '<p><font color="red">ポイント付与率には0以上の数値を入力してください</font></p>';
                    $_SESSION['error2-2'] += '<p><font color="red">ポイント付与率には0以上の数値を入力してください</font></p>';
                }
                if ($_REQUEST['priority'] < 0) {
                    $_SESSION['error2'] += '<p><font color="red">優先度には0以上の数値を入力してください</font></p>';
                    $_SESSION['error2-2'] += '<p><font color="red">優先度には0以上の数値を入力してください</font></p>';
                }
                $flag = false;
                break;
            }
            //同じ内容かチェック
            if (
                $_REQUEST['cname'] === $_REQUEST['ori-cname']
                && (int)$_REQUEST['cprate'] === (int)($_REQUEST['ori-cprate'] * 100)
                && $_REQUEST['start'] === date('Y/m/d H:i', strtotime($_REQUEST['ori-start']))
                && $_REQUEST['end'] === date('Y/m/d H:i', strtotime($_REQUEST['ori-end']))
                && (int)$_REQUEST['priority'] === (int)$_REQUEST['ori-priority']
            ) {
                $_SESSION['error2'] = '<p><font color="red">更新する場合は違う値を入力してください</font></p>';
                $_SESSION['error2-2'] = '<p><font color="red">更新する場合は違う値を入力してください</font></p>';
                $flag = false;
                break;
            }

            //開始時期と終了時期のチェック
            $datetime1 = new DateTime($_REQUEST['start']);
            $datetime2 = new DateTime($_REQUEST['end']);
            if ($datetime1 > $datetime2) {
                $_SESSION['error2'] = '<p><font color="red">正しい日付範囲を指定してください</font></p>';
                $_SESSION['error2-2'] = '<p><font color="red">正しい日付範囲を指定してください</font></p>';
                $flag = false;
                break;
            }
            if (preg_match('/point-detail-edit.php$/', $_SESSION['url'][1]) === 1) {
                $_SESSION['cp-data'][8] = 1;
                $_SESSION['cp-data'] = [
                    $_REQUEST['pcid'],
                    h($_REQUEST['cname']),
                    h($_REQUEST['cprate'] / 100),
                    h($_REQUEST['start']),
                    h($_REQUEST['end']),
                    h($_REQUEST['priority'])
                ];
            }
            break;

        //キャンペーンの削除
        case 'pc-del':
            $_SESSION['cp-data'] = [
                $_REQUEST['pcid'],
                h($_REQUEST['cname']),
                h($_REQUEST['cprate'] / 100),
                h($_REQUEST['start']),
                h($_REQUEST['end']),
                h($_REQUEST['priority'])
            ];
            break;

        //キャンペーンの追加
        case 'pc-add':
            //空かチェック
            if (
                $_REQUEST['cname'] === ''
                || $_REQUEST['cprate'] === ''
                || $_REQUEST['start'] === ''
                || $_REQUEST['end'] === ''
                || $_REQUEST['priority'] === ''
            ) {
                $_SESSION['error3'] = '<p><font color="red">値を入力してください</font></p>';
                $flag = false;
                break;
            }

            //数字かチェック
            if (
                !is_numeric($_REQUEST['cprate'])
                || !is_numeric($_REQUEST['cprate'])
            ) {
                if (!is_numeric($_REQUEST['cprate'])) {
                    $_SESSION['error3'] += '<p><font color="red">ポイント付与率には数値を入力してください</font></p>';
                }
                if (!is_numeric($_REQUEST['priority'])) {
                    $_SESSION['error3'] += '<p><font color="red">優先度には数値を入力してください</font></p>';
                }
                $flag = false;
                break;
            }

            //０以上かチェック
            if (
                $_REQUEST['cprate'] < 0
                || $_REQUEST['priority'] < 0
            ) {
                if ($_REQUEST['cprate'] < 0) {
                    $_SESSION['error4'] += '<p><font color="red">ポイント付与率には0以上の数値を入力してください</font></p>';
                }
                if ($_REQUEST['priority'] < 0) {
                    $_SESSION['error3'] += '<p><font color="red">優先度には0以上の数値を入力してください</font></p>';
                }
                $flag = false;
                break;
            }

            //開始時期と終了時期のチェック
            $datetime1 = new DateTime($_REQUEST['start']);
            $datetime2 = new DateTime($_REQUEST['end']);
            if ($datetime1 > $datetime2) {
                $_SESSION['error3'] = '<p><font color="red">正しい日付範囲を指定してください</font></p>';
                $flag = false;
                break;
            }

            $_SESSION['cp-data'] = [
                0,
                h($_REQUEST['cname']),
                h($_REQUEST['cprate'] / 100),
                h($_REQUEST['start']),
                h($_REQUEST['end']),
                h($_REQUEST['priority'])
            ];
            break;

        //キャンペーン該当商品の削除
        case 'pcitem-del':
            $_SESSION['cpitem-data'] = $_REQUEST['ctid'];
            break;
    }
    if ($flag) {
        header('Location: point-edit-confirm.php');
    } else {
        if (preg_match('/(point-edit.php)$/', $_SESSION['url'][1]) === 0) {
            header('Location: point-detail-edit.php');
        } else {
            header('Location: point-edit.php');
        }
    }
}
?>