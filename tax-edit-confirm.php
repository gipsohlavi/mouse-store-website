<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php
// POSTデータのサニタイズ関数
function sanitizeInput($data) {
return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// エラーメッセージをセッションに保存する関数
function setError($errorKey, $message) {
$_SESSION[$errorKey] = '<p>
    <font color="red">' . $message . '</font>
</p>';
}

// 成功メッセージをセッションに保存する関数
function setSuccess($message) {
$_SESSION['success'] = $message;
}

// バリデーション関数
function validateTaxRate($rate) {
if (empty($rate)) {
return '税率を入力してください。';
}

$numericRate = floatval($rate);
if (!is_numeric($rate) || $numericRate < 0 || $numericRate> 100) {
    return '税率は0から100の間の数値を入力してください。';
    }

    return null;
    }

    function validateDate($date, $fieldName) {
    if (empty($date)) {
    return $fieldName . 'を入力してください。';
    }

    $dateTime = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
    return '正しい日付形式で' . $fieldName . 'を入力してください。';
    }

    return null;
    }

    try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tax'])) {
    $action = $_POST['tax'];

    switch ($action) {
    case 'tax-add':
    // 新しい税率の追加
    $taxRate = sanitizeInput($_POST['tax-rate']);
    
    // 税率を小数に変換（例：10% -> 0.10）
    $decimalRate = floatval($taxRate) / 100;
    $startDate = sanitizeInput($_POST['start-date']);
    $endDate = !empty($_POST['end-date']) ? sanitizeInput($_POST['end-date']) : null;

    // バリデーション
    $errors = [];

    $rateError = validateTaxRate($taxRate);
    if ($rateError) $errors[] = $rateError;

    $startDateError = validateDate($startDate, '適用開始日');
    if ($startDateError) $errors[] = $startDateError;

    if ($endDate) {
    $endDateError = validateDate($endDate, '適用終了日');
    if ($endDateError) $errors[] = $endDateError;

    // 終了日が開始日より後かチェック
    if (!$endDateError && $endDate <= $startDate) {
        $errors[]='適用終了日は適用開始日より後の日付を入力してください。' ;
        }
    }

    // 既存の税率との重複チェック
    $checkSql=$pdo->prepare('SELECT COUNT(*) FROM tax WHERE tax_start_date <= ? AND (tax_end_date IS NULL OR tax_end_date> ?) AND tax = ?');
    $checkSql->execute([$endDate ?: '9999-12-31', $startDate, $decimalRate]);
    if ($checkSql->fetchColumn() > 0) {
        $errors[] = '指定した期間に既に適用中の税率があります。期間を確認してください。';
    }

    if (count($errors) > 0) {
        setError('error2', implode('<br>', $errors));
    } else {
        // 新しい税IDを取得
        $maxIdSql = $pdo->query('SELECT MAX(tax_id) FROM tax');
        $newTaxId = ($maxIdSql->fetchColumn() ?: 0) + 1;

        $insertSql = $pdo->prepare('INSERT INTO tax (tax_id, tax, tax_start_date, tax_end_date) VALUES (?, ?, ?, ?)');
        $result = $insertSql->execute([
            $newTaxId,
            $decimalRate,
            $startDate,
            $endDate
        ]);

        if ($result) {
            setSuccess('新しい税率設定を追加しました。');
        } else {
            setError('error2', '税率設定の追加に失敗しました。');
        }
    }
    break;

    case 'tax-change':
    // 税率設定の変更
    $taxId = (int)$_POST['tax-id'];
    $taxRate = sanitizeInput($_POST['tax-rate']);
    $startDate = sanitizeInput($_POST['start-date']);
    $endDate = !empty($_POST['end-date']) ? sanitizeInput($_POST['end-date']) : null;

    $oriTax = floatval($_POST['ori-tax']);
    $oriStart = $_POST['ori-start'];
    $oriEnd = $_POST['ori-end'];

    // バリデーション
    $errors = [];

    $rateError = validateTaxRate($taxRate);
    if ($rateError) $errors[] = $rateError;

    $startDateError = validateDate($startDate, '適用開始日');
    if ($startDateError) $errors[] = $startDateError;

    if ($endDate) {
    $endDateError = validateDate($endDate, '適用終了日');
    if ($endDateError) $errors[] = $endDateError;

    if (!$endDateError && $endDate <= $startDate) {
        $errors[]='適用終了日は適用開始日より後の日付を入力してください。' ;
        }
        }

        // 変更があるかチェック
        $decimalRate=floatval($taxRate) / 100;
        $hasChanges=($decimalRate !=$oriTax) ||
        ($startDate !=$oriStart) ||
        ($endDate !=$oriEnd);

        if (!$hasChanges) {
        $errors[]='変更がありません。' ;
        }

        // 他の税率設定との重複チェック（自分以外）
        if (count($errors)==0) {
        $checkSql=$pdo->prepare('SELECT COUNT(*) FROM tax WHERE tax_id != ? AND tax_start_date <= ? AND (tax_end_date IS NULL OR tax_end_date> ?)  AND tax = ?');
            $checkSql->execute([$taxId, $endDate ?: '9999-12-31', $startDate, $decimalRate]);
            if ($checkSql->fetchColumn() > 0) {
                $errors[] = '指定した期間に他の同税率設定と重複があります。期間を確認してください。';
            }
            }

            if (count($errors) > 0) {
            setError('error1', implode('<br>', $errors));
            } else {
            $updateSql = $pdo->prepare('UPDATE tax SET tax = ?, tax_start_date = ?, tax_end_date = ? WHERE tax_id = ?');
            $result = $updateSql->execute([
            $decimalRate,
            $startDate,
            $endDate,
            $taxId
            ]);

            if ($result) {
            setSuccess('税率設定を変更しました。');
            } else {
            setError('error1', '税率設定の変更に失敗しました。');
            }
            }
            break;

            case 'tax-delete':
            // 税率設定の削除
            $taxId = (int)$_POST['tax-id'];

            // 削除前に使用状況をチェック
            $usageCheckSql = $pdo->prepare('SELECT COUNT(*) FROM product WHERE tax_id = ?');
            $usageCheckSql->execute([$taxId]);
            $usageCount = $usageCheckSql->fetchColumn();

            if ($usageCount > 0) {
            setError('error1', 'この税率は商品で使用されているため削除できません。先に商品の税率設定を変更してください。');
            } else {
            $deleteSql = $pdo->prepare('DELETE FROM tax WHERE tax_id = ?');
            $result = $deleteSql->execute([$taxId]);

            if ($result) {
            setSuccess('税率設定を削除しました。');
            } else {
            setError('error1', '税率設定の削除に失敗しました。');
            }
            }
            break;

            default:
            setError('error1', '無効な操作です。');
            break;
            }
            } else {
            setError('error1', '不正なアクセスです。');
            }

            } catch (PDOException $e) {
            error_log('Tax edit error: ' . $e->getMessage());
            setError('error1', 'データベースエラーが発生しました。管理者にお問い合わせください。');
            } catch (Exception $e) {
            error_log('Tax edit error: ' . $e->getMessage());
            setError('error1', '予期しないエラーが発生しました。管理者にお問い合わせください。');
            }

            // リダイレクト
            header('Location: tax-edit.php');
            exit;