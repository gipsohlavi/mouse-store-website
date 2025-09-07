<?php
session_start();
require 'common.php';
require 'header.php';




// SQLクエリの実行
$stmt = $pdo->query("SELECT region_id, postage_fee FROM postage");
$postage_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 地域IDを地域名に変換する配列（仮のデータ）
$region_names = [
    1 => '北海道',
    2 => '東北',
    3 => '関東',
    4 => '中部',
    5 => '近畿',
    6 => '中国',
    7 => '四国',
    8 => '九州',
    9 => '沖縄',
];
?>
<style>
    .simple-hero {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        padding: 48px 16px;
        margin-bottom: 16px;
    }
    .simple-hero .hero-inner {
        max-width: 1000px;
        margin: 0 auto;
    }
    .simple-hero h1 {
        margin: 0 0 8px 0;
        font-size: 1.8rem;
        font-weight: 700;
    }
    .simple-hero p { opacity: .9; margin: 0; }
    .shipping-container {
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .shipping-container h1 {
        font-size: 2em;
        margin-bottom: 30px;
    }

    .shipping-container p {
        text-align: left;
        line-height: 1.6;
        margin-bottom: 20px;
    }

    .shipping-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }

    .shipping-table th,
    .shipping-table td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
    }

    .shipping-table th {
        background-color: #f2f2f2;
    }
</style>

<div class="simple-hero">
    <div class="hero-inner">
        <h1>配送・送料について</h1>
        <p>地域別送料と送料無料条件のご案内</p>
    </div>
</div>

<div class="shipping-container">

    <p>
    送料は、お届け先の地域によって異なります。以下の一覧表をご確認ください。
    </p>

    <table class="shipping-table">
        <thead>
            <tr>
                <th>お届け地域</th>
                <th>送料（税込）</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($postage_data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($region_names[$row['region_id']]); ?></td>
                    <td><?php echo htmlspecialchars(number_format($row['postage_fee'])) . " 円"; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p>
    ※複数の商品を同時に購入された場合でも、送料は上記料金から変わりません。
    </p>

</div>

<?php require 'footer.php'; ?>