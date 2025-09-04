<?php session_start(); ?>
<?php
if (isset($_SESSION['url'][0])){
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //0 = 現在のURL　1=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];

//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/postage-edit.php$/',$_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: postage-edit.php');
}
?>
<?php require 'common.php'; ?>
<?php
unset($_SESSION['error1']);
unset($_SESSION['error2']);
unset($_SESSION['error3']);
unset($_SESSION['error4']);
unset($_SESSION['error5']);
if (isset($_REQUEST['postage'])){
    $flag = true;
    $_SESSION['postage'] = $_REQUEST['postage'];

    switch ($_SESSION['postage']){
        //地域ごと送料の更新
        case 'pos-region':
            $check = true;
            for ($i = 0; $i < $_REQUEST['count']; $i++) {
                //POSTされた情報を受け取るため、名称を整形してを変数に保管
                $postageid = 'region-id' . $i;
                $postagename = 'postage-name' . $i;
                $oripostagenum = 'ori-postage-num' . $i;
                $postagenum = 'postage-num' . $i;
                $array[$i] = [(int)h($_REQUEST[$postageid]),
                                h($_REQUEST[$postagename]),
                                (int)str_replace(',','',h($_REQUEST[$postagenum]))];
                //空かチェック
                if ($_REQUEST[$postagenum] === '') {
                    $_SESSION['error1'] = '<p><font color="red">値を入力してください</font></p>';
                    $flag = false;
                    break;
                }                
                //数字かチェック
                if (!is_numeric(str_replace(',','',$_REQUEST[$postagenum]))) {
                    $_SESSION['error1'] = '<p><font color="red">数値を入力してください</font></p>';
                    $flag = false;
                    break;
                }
                //同じ内容かチェック
                if (str_replace(',','',$_REQUEST[$postagenum]) != $_REQUEST[$oripostagenum]){
                    $check = false;
                    break;
                }
            }
            if ($check && $flag){
                $_SESSION['error1'] = '<p><font color="red">更新する場合は違う値を入力してください</font></p>';
                $flag = false;
            }
            $_SESSION['pos-region'] = $array;
            break;
        

        //離島送料の変更
        case 'pos-island':
            //空かチェック
            if ($_REQUEST['post-island'] === '') {
                $_SESSION['error2'] = '<p><font color="red">値を入力してください</font></p>';
                $flag = false;
                break;
            }               
            //数字かチェック
            if (!is_numeric(str_replace(",","",$_REQUEST['post-island']))) {
                $_SESSION['error2'] = '<p><font color="red">数値を入力してください</font></p>';
                $flag = false;
                break;
            }
            //同じ内容かチェック
            if (str_replace(',','',$_REQUEST['post-island']) === $_REQUEST['ori-post-island']){
                $_SESSION['error2'] = '<p><font color="red">更新する場合は違う値を入力してください</font></p>';
                $flag = false;
                break;
            }
            $_SESSION['post-island'] = (int)str_replace(",","",h($_REQUEST['post-island']));
            break;
        

        //基本送料無料条件の変更
        case 'posterms':
            //空かチェック
            if ($_REQUEST['post-terms'] === '') {
                    $_SESSION['error3'] = '<p><font color="red">値を入力してください</font></p>';
                $flag = false;
                break;
            }
            //数字かチェック
            if (!is_numeric(str_replace(",","",$_REQUEST['post-terms']))) {
                    $_SESSION['error3'] = '<p><font color="red">数値を入力してください</font></p>';
                $flag = false;
                break;
            }
            //同じ内容かチェック
            if (str_replace(',','',$_REQUEST['post-terms']) === $_REQUEST['ori-post-terms']){
                $_SESSION['error3'] = '<p><font color="red">更新する場合は違う値を入力してください</font></p>';
                $flag = false;
                break;
            }
            $_SESSION['post-terms'] = (int)str_replace(",","",h($_REQUEST['post-terms']));
            
            break;
        

        //キャンペーン送料の変更
        case 'posterms-change':
            //空かチェック
            if ($_REQUEST['post-terms'] === '' 
                || $_REQUEST['post-start'] === '' 
                || $_REQUEST['post-end'] === '') {
                $_SESSION['error4'] = '<p><font color="red">値を入力してください</font></p>';
                $flag = false;
                break;
            }
            //数字かチェック
            if (!is_numeric(str_replace(",","",$_REQUEST['post-terms']))) {
                $_SESSION['error4'] = '<p><font color="red">数値を入力してください</font></p>';
                $flag = false;
                break;
            }
            // echo $_REQUEST['post-start'] . " & " . date('Y/m/d H:i',strtotime($_REQUEST['ori-post-start'])) .'<br>';
            // echo $_REQUEST['post-end'] . " & " . date('Y/m/d H:i',strtotime($_REQUEST['ori-post-end'])) .'<br>';
            // return;
            //同じ内容かチェック
            if (str_replace(',','',$_REQUEST['post-terms']) === $_REQUEST['ori-post-terms']
                && $_REQUEST['post-start'] === date('Y/m/d H:i',strtotime($_REQUEST['ori-post-start']))
                && $_REQUEST['post-end'] === date('Y/m/d H:i', strtotime($_REQUEST['ori-post-end']))){
                $_SESSION['error4'] = '<p><font color="red">更新する場合は違う値を入力してください</font></p>';
                $flag = false;
                break;
            }
            //開始時期と終了時期のチェック
            $datetime1 = new DateTime($_REQUEST['post-start']);
            $datetime2 = new DateTime($_REQUEST['post-end']);
            if ($datetime1 > $datetime2 ) {
                $_SESSION['error4'] = '<p><font color="red">正しい日付範囲を指定してください</font></p>';
                $flag = false;
                break;
            }
            $_SESSION['post-terms'] = [$_REQUEST['post-id'],
                                        (int)str_replace(",","", h($_REQUEST['post-terms'])),
                                        h($_REQUEST['post-start']),
                                        h($_REQUEST['post-end'])];
            break;
        
            
        //キャンペーン送料の削除
        case 'posterms-del':
            $_SESSION['post-terms'] = [$_REQUEST['post-id'],
                                        (int)str_replace(",","",h($_REQUEST['ori-post-terms'])),
                                        h($_REQUEST['ori-post-start']),
                                        h($_REQUEST['ori-post-end'])];
            break;
        
        //キャンペーン送料の追加
        case 'posterms-add':
            //空かチェック
            if ($_REQUEST['post-terms'] === '' 
                || $_REQUEST['post-start'] === '' 
                || $_REQUEST['post-end'] === '') {
                $_SESSION['error5'] = '<p><font color="red">値を入力してください</font></p>';
                $flag = false;
                break;
            }
            //数字かチェック
            if (!is_numeric(str_replace(",","",$_REQUEST['post-terms']))) {
                $_SESSION['error5'] = '<p><font color="red">条件価格には数値を入力してください</font></p>';
                $flag = false;
                break;
            }
            //開始時期と終了時期のチェック
            $datetime1 = new DateTime($_REQUEST['post-start']);
            $datetime2 = new DateTime($_REQUEST['post-end']);
            if ($datetime1 > $datetime2 ) {
                $_SESSION['error5'] = '<p><font color="red">正しい日付範囲を指定してください</font></p>';
                $flag = false;
                break;
            }

            //キャンペーン期間の重複確認
            $sql=$pdo->prepare('SELECT COUNT(*) FROM postage_free WHERE start_date = ? AND end_date IS NOT NULL AND end_date <= ?');
            $sql->bindParam(1,$_REQUEST['post-start']);
            $sql->bindParam(2,$_REQUEST['post-end']);
            $sql->execute();
            if ($sql->fetchColumn() > 0) {
                $_SESSION['error6'] = '<p><font color="red">指定した期間に重複があります。期間を確認してください。</font></p>';
                $flag = false;
                break;
            }

            $_SESSION['post-terms'] = [0,(int)str_replace(",","",h($_REQUEST['post-terms'])),
                                        h($_REQUEST['post-start']),
                                        h($_REQUEST['post-end'])];
            break;
    }
    if ($flag) {
        header('Location: postage-edit-confirm.php');
    } else {
        header('Location: postage-edit.php');
    }
}
?>