<?php
session_start();
require 'common.php';

if (isset($_SESSION['url'][0])) {
    $_SESSION['url'][1] =  $_SESSION['url'][0]; //0 = 現在のURL　1=遷移元のURL
}
$_SESSION['url'][0] =  $_SERVER['REQUEST_URI'];

//遷移元のチェック
if (!isset($_SESSION['url'][1]) || preg_match('/product-list-(input|edit)\.php/', $_SESSION['url'][1]) === 0) {
    unset($_SESSION['url']);
    header('Location: product-list.php');
} else if (preg_match('/product-list-input.php/', $_SESSION['url'][1]) === 1) {
    $flag = 0;
} else {
    $flag =1;
    $product_id = $_REQUEST['product_id'];
}

// POST以外のリクエストは拒否
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: product-list.php');
    exit;
}

// 入力値の検証と取得
$required_fields = ['name', 'price', 'tax_id', 'stock_quantity', 'weight'];
$errors = [];

// 必須項目チェック
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        $errors[] = "{$field}は必須項目です。";
    }
}
for ($i = 1; $i <= 7; $i++) {
    if ($_FILES["image".$i]["error"] != UPLOAD_ERR_OK) {
        // 画像が未選択 → hiddenから復元（編集時）
        $product_data["image_name".$i] = $_POST["current_image{$i}"] ?? '';
    }
}

// メイン画像のチェック
if ($flag === 0) { // 新規登録のときだけ必須チェック
    if ($_FILES['image1']['error'] != UPLOAD_ERR_OK) {
        $errors[] = "メイン画像（画像1）は必須です。";
    }
}

// 数値項目の検証
$numeric_fields = ['price', 'stock_quantity', 'weight'];
foreach ($numeric_fields as $field) {
    if (isset($_POST[$field]) && !is_numeric($_POST[$field])) {
        $errors[] = "{$field}は数値で入力してください。";
    }
}

// 価格の範囲チェック
if (isset($_POST['price']) && (intval($_POST['price']) < 1 || intval($_POST['price']) > 9999999)) {
    $errors[] = "価格は1円以上999万円以下で入力してください。";
}

// エラーがある場合は戻る
if (!empty($errors)) {
    $_SESSION['product_add_error'] = implode('<br>', $errors);
    $_SESSION['product_add_data'] = $_POST;
    // 通常の追加の場合は商品一覧へリダイレクト
    if ($flag === 0) {
        header('Location: product-list-input.php');
    } else {
        header('Location: product-list-edit.php?id='.$product_id);
    }
    exit;
}

try {
    // トランザクション開始
    $pdo->beginTransaction();

    // 商品テーブルに挿入するデータを準備
    $product_data = [
        'name' => trim($_POST['name']),
        'price' => intval($_POST['price']),
        'tax_id' => intval($_POST['tax_id']),
        'stock_quantity' => intval($_POST['stock_quantity']),
        'weight' => intval($_POST['weight']),
        'recommend' => isset($_POST['recommend']) ? 1 : 0,
        'on_sale' => isset($_POST['on_sale']) ? 1 : 0,
        'for_gift' => isset($_POST['for_gift']) ? 1 : 0,
        'product_overview' => isset($_POST['product_overview']) ? trim($_POST['product_overview']) : null,
        'product_detailed_review' => isset($_POST['product_detailed_review']) ? trim($_POST['product_detailed_review']) : null,
        'purchase_quantity' => 0,
        'sales_quantity' => 0,
        'created_day' => date('Y-m-d H:i:s'),
        'updated_day' => date('Y-m-d H:i:s')
    ];

    // 編集時は既存の値を保持
    if ($flag === 1) {
        // 既存の商品データを取得
        $existing_sql = $pdo->prepare("SELECT * FROM product WHERE id = ?");
        $existing_sql->execute([$product_id]);
        $existing_product = $existing_sql->fetch();
        
        if ($existing_product) {
            // 編集時は既存の値を保持（変更されていない場合）
            $product_data['purchase_quantity'] = $existing_product['purchase_quantity'];
            $product_data['sales_quantity'] = $existing_product['sales_quantity'];
            $product_data['created_day'] = $existing_product['created_day'];
        }
    }

    // オプション項目の設定
    $optional_fields = [
        'button_count',
        'width',
        'depth',
        'height',
        'cable_length',
        'dpi_max',
        'polling_rate',
        'lod_distance_mm',
        'debounce_time_ms',
        'click_delay_ms',
        'battery_capacity_mah',
        'battery_life_hours'
    ];

    foreach ($optional_fields as $field) {
        if (isset($_POST[$field]) && trim($_POST[$field]) !== '') {
            $product_data[$field] = is_numeric($_POST[$field]) ?
                (strpos($_POST[$field], '.') !== false ? floatval($_POST[$field]) : intval($_POST[$field])) :
                trim($_POST[$field]);
        } else {
            $product_data[$field] = null;
        }
    }

    // チェックボックス項目
    $checkbox_fields = ['lod_adjustable', 'debounce_adjustable', 'motion_sync_support'];
    foreach ($checkbox_fields as $field) {
        $product_data[$field] = isset($_POST[$field]) ? 1 : 0;
    }

    // 画像ファイル名を設定（後でアップロード処理）
    for ($i = 1; $i <= 7; $i++) {
        $product_data["image_name{$i}"] = '';
    }
    if ($flag === 0) {
        // 商品テーブルへの挿入
        $sql_columns = implode(', ', array_keys($product_data));
        $sql_placeholders = ':' . implode(', :', array_keys($product_data));

        $sql = $pdo->prepare("INSERT INTO product ({$sql_columns}) VALUES ({$sql_placeholders})");
        $sql->execute($product_data);

        $product_id = $pdo->lastInsertId();
    } else {
        // 商品テーブルデータの変更
        $set_parts = [];
        foreach ($product_data as $column => $value) {
            $set_parts[] = "$column = :$column";
        }
        $set_clause = implode(', ', $set_parts);

        // SQL 準備
        $sql = $pdo->prepare("UPDATE product SET {$set_clause} WHERE id = :id");

        // id を WHERE 条件用に追加
        $product_data['id'] = $_REQUEST['product_id'];

        // 実行
        $sql->execute($product_data);
    }

    // 画像アップロード処理
    $uploaded_images = [];
    for ($i = 1; $i <= 7; $i++) {
        // 編集時の画像削除処理
        if ($flag === 1 && isset($_POST["delete_image{$i}"])) {
            $current_image = $_POST["current_image{$i}"] ?? '';
            if (!empty($current_image)) {
                $old_file_path = "images/{$current_image}.jpg";
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
                $uploaded_images[$i] = ''; // 空文字で削除をマーク
            }
        }
        // 新しい画像のアップロード
        elseif (isset($_FILES["image{$i}"]) && $_FILES["image{$i}"]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES["image{$i}"];

            // ファイル形式チェック
            $allowed_types = ['image/jpeg', 'image/jpg'];
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception("画像{$i}はJPEGファイルのみ対応しています。");
            }

            // ファイルサイズチェック（5MB制限）
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception("画像{$i}のファイルサイズが大きすぎます（5MB以下）。");
            }

            // 編集時は古い画像を削除
            if ($flag === 1) {
                $current_image = $_POST["current_image{$i}"] ?? '';
                if (!empty($current_image)) {
                    $old_file_path = "images/{$current_image}.jpg";
                    if (file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                }
            }

            // ファイル名を設定
            $image_name = $product_id . ($i === 1 ? '' : "_{$i}");
            $upload_path = "images/{$image_name}.jpg";

            // ファイル移動
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $uploaded_images[$i] = $image_name;
            } else {
                throw new Exception("画像{$i}のアップロードに失敗しました。");
            }
        }
        // 編集時で画像が変更されていない場合は既存の画像名を保持
        elseif ($flag === 1) {
            $current_image = $_POST["current_image{$i}"] ?? '';
            if (!empty($current_image)) {
                $uploaded_images[$i] = $current_image;
            }
        }
    }

    // アップロードされた画像の情報を更新
    if (!empty($uploaded_images)) {
        $image_updates = [];
        $image_params = ['id' => $product_id];

        foreach ($uploaded_images as $num => $image_name) {
            if (isset($product_data["image_name".$num])) {
                echo $num. '<br>';
                $image_updates[] = "image_name{$num} = :image_name{$num}";
                $image_params["image_name{$num}"] = $image_name;
            }
        }

        $sql = $pdo->prepare("UPDATE product SET " . implode(', ', $image_updates) . " WHERE id = :id");
        $sql->execute($image_params);
    }

    // product_master_relation テーブルへの関連データ処理
    $master_relations = [
        'maker_id' => 1,      // メーカー
        'sensor_id' => 5,     // センサー
        'switch_id' => 21,    // スイッチ
        'mcu_id' => 22,       // MCU
        'charging_port_id' => 23, // 充電端子
        'software_id' => 24,  // ソフトウェア
        'material_id' => 18,  // 素材
        'surface_finish_id' => 19, // 表面仕上げ
        'shape_id' => 7       // 形状
    ];

    // product_master_relationテーブルの更新処理
    // 編集時は既存の関連データを削除してから新規追加
    if ($flag === 1) {
        // 既存の関連データを削除
        $sql = $pdo->prepare("DELETE FROM product_master_relation WHERE product_id = ?");
        $sql->execute([$product_id]);
    }
    
    // 新規追加時もproductテーブルの外部キーカラムを設定
    if ($flag === 0) {
        $update_fields = [];
        $update_params = [];
        
        // メーカーID
        if (isset($_POST['maker_id']) && !empty($_POST['maker_id'])) {
            $update_fields[] = "maker_id = ?";
            $update_params[] = intval($_POST['maker_id']);
        }
        
        // 色ID
        if (isset($_POST['colors']) && is_array($_POST['colors']) && count($_POST['colors']) > 0) {
            $update_fields[] = "color_id = ?";
            $update_params[] = intval($_POST['colors'][0]); // 最初の色を設定
        }
        
        // 接続方式ID
        if (isset($_POST['connections']) && is_array($_POST['connections']) && count($_POST['connections']) > 0) {
            $update_fields[] = "connection_id = ?";
            $update_params[] = intval($_POST['connections'][0]); // 最初の接続方式を設定
        }
        
        // センサーID
        if (isset($_POST['sensor_id']) && !empty($_POST['sensor_id'])) {
            $update_fields[] = "sensor_id = ?";
            $update_params[] = intval($_POST['sensor_id']);
        }
        
        // インターフェースID（充電端子から設定）
        if (isset($_POST['charging_port_id']) && !empty($_POST['charging_port_id'])) {
            $update_fields[] = "interface_id = ?";
            $update_params[] = intval($_POST['charging_port_id']);
        }
        
        // 形状ID
        if (isset($_POST['shape_id']) && !empty($_POST['shape_id'])) {
            $update_fields[] = "shape_id = ?";
            $update_params[] = intval($_POST['shape_id']);
        }
        
        // スイッチID
        if (isset($_POST['switch_id']) && !empty($_POST['switch_id'])) {
            $update_fields[] = "switch_id = ?";
            $update_params[] = intval($_POST['switch_id']);
        }
        
        // 表面仕上げID
        if (isset($_POST['surface_finish_id']) && !empty($_POST['surface_finish_id'])) {
            $update_fields[] = "surface_finish_id = ?";
            $update_params[] = intval($_POST['surface_finish_id']);
        }
        
        // サイズカテゴリー
        if (isset($_POST['size_category']) && !empty($_POST['size_category'])) {
            $update_fields[] = "size_category = ?";
            $update_params[] = $_POST['size_category'];
        }
        
        // 更新実行
        if (!empty($update_fields)) {
            $update_params[] = $product_id; // WHERE句用
            $update_sql = "UPDATE product SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute($update_params);
        }
    }

    // 単一選択項目の処理
    foreach ($master_relations as $field => $kbn_id) {
        if (isset($_POST[$field]) && !empty($_POST[$field])) {
            $sql = $pdo->prepare("INSERT INTO product_master_relation (product_id, kbn_id, master_id) VALUES (?, ?, ?)");
            $sql->execute([$product_id, $kbn_id, intval($_POST[$field])]);
        }
    }

    // 複数選択項目（接続方式、カラー）の処理
    if (isset($_POST['connections']) && is_array($_POST['connections'])) {
        foreach ($_POST['connections'] as $connection_id) {
            $sql = $pdo->prepare("INSERT INTO product_master_relation (product_id, kbn_id, master_id) VALUES (?, ?, ?)");
            $sql->execute([$product_id, 3, intval($connection_id)]);
        }
    }

    if (isset($_POST['colors']) && is_array($_POST['colors'])) {
        foreach ($_POST['colors'] as $color_id) {
            $sql = $pdo->prepare("INSERT INTO product_master_relation (product_id, kbn_id, master_id) VALUES (?, ?, ?)");
            $sql->execute([$product_id, 2, intval($color_id)]);
        }
    }

    // 商品テーブルの外部キーカラムも更新（整合性を保つため）
    if ($flag === 1) {
        $update_fields = [];
        $update_params = [];
        
        // メーカーID
        if (isset($_POST['maker_id']) && !empty($_POST['maker_id'])) {
            $update_fields[] = "maker_id = ?";
            $update_params[] = intval($_POST['maker_id']);
        }
        
        // 色ID
        if (isset($_POST['colors']) && is_array($_POST['colors']) && count($_POST['colors']) > 0) {
            $update_fields[] = "color_id = ?";
            $update_params[] = intval($_POST['colors'][0]); // 最初の色を設定
        }
        
        // 接続方式ID
        if (isset($_POST['connections']) && is_array($_POST['connections']) && count($_POST['connections']) > 0) {
            $update_fields[] = "connection_id = ?";
            $update_params[] = intval($_POST['connections'][0]); // 最初の接続方式を設定
        }
        
        // センサーID
        if (isset($_POST['sensor_id']) && !empty($_POST['sensor_id'])) {
            $update_fields[] = "sensor_id = ?";
            $update_params[] = intval($_POST['sensor_id']);
        }
        
        // インターフェースID（充電端子から設定）
        if (isset($_POST['charging_port_id']) && !empty($_POST['charging_port_id'])) {
            $update_fields[] = "interface_id = ?";
            $update_params[] = intval($_POST['charging_port_id']);
        }
        
        // 形状ID
        if (isset($_POST['shape_id']) && !empty($_POST['shape_id'])) {
            $update_fields[] = "shape_id = ?";
            $update_params[] = intval($_POST['shape_id']);
        }
        
        // スイッチID
        if (isset($_POST['switch_id']) && !empty($_POST['switch_id'])) {
            $update_fields[] = "switch_id = ?";
            $update_params[] = intval($_POST['switch_id']);
        }
        
        // 表面仕上げID
        if (isset($_POST['surface_finish_id']) && !empty($_POST['surface_finish_id'])) {
            $update_fields[] = "surface_finish_id = ?";
            $update_params[] = intval($_POST['surface_finish_id']);
        }
        
        // サイズカテゴリー
        if (isset($_POST['size_category']) && !empty($_POST['size_category'])) {
            $update_fields[] = "size_category = ?";
            $update_params[] = $_POST['size_category'];
        }
        
        // 更新実行
        if (!empty($update_fields)) {
            $update_params[] = $product_id; // WHERE句用
            $update_sql = "UPDATE product SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute($update_params);
        }
    }
    // トランザクションコミット
    $pdo->commit();

    // 成功時の処理
    if ($flag === 0) {
        $_SESSION['product_add_success'] = "商品「{$product_data['name']}」を正常に追加しました。";
    } else {
        $_SESSION['product_add_success'] = "商品「{$product_data['name']}」を正常に更新しました。";
    }

    // 下書き保存の場合はJSON応答
    if (isset($_POST['action']) && $_POST['action'] === 'draft') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '下書きを保存しました。']);
        exit;
    }

    // 通常の追加の場合は商品一覧へリダイレクト
    if ($flag === 0) {
        header('Location: product-list.php');
    } else {
        header('Location: product-list-edit.php?id='.$product_id);
    }
    exit;
} catch (Exception $e) {
    // エラー時はロールバック
    $pdo->rollBack();

    // アップロードされた画像があれば削除
    if (isset($uploaded_images)) {
        foreach ($uploaded_images as $image_name) {
            $file_path = "images/{$image_name}.jpg";
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }

    if ($flag === 0) {
        $_SESSION['product_add_error'] = "商品の追加中にエラーが発生しました: " . $e->getMessage();
    } else {
        $_SESSION['product_add_error'] = "商品の更新中にエラーが発生しました: " . $e->getMessage();
    }
    $_SESSION['product_add_data'] = $_POST;

    // 下書き保存の場合はJSON応答
    if (isset($_POST['action']) && $_POST['action'] === 'draft') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
    if ($flag === 0) {
        header('Location: product-list-input.php');
    } else {
        header('Location: product-list-edit.php?id='.$product_id);
    }
    exit;
}
