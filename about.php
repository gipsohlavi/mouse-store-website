<?php
session_start();
require 'common.php';
require 'header.php';
require 'menu.php';
?>
<style>
    .company-profile-container {
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .company-profile-container h1 {
        font-size: 2em;
        margin-bottom: 30px;
    }

    .company-profile-container h3 {
        text-align: left;
        margin-top: 40px;
        margin-bottom: 10px;
        font-size: 1.2em;
    }

    .company-profile-container p {
        text-align: left;
        line-height: 1.6;
        margin-bottom: 20px;
    }
    
    .company-profile-container table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
        text-align: left;
    }

    .company-profile-container th,
    .company-profile-container td {
        padding: 10px;
        border: 1px solid #ddd;
    }

    .company-profile-container th {
        background-color: #f2f2f2;
        width: 25%; /* 項目名の列幅を調整 */
    }
</style>

<div class="company-profile-container">

    <h1>会社概要</h1>

    <table>
        <tbody>
            <tr>
                <th>商号</th>
                <td>株式会社KELOT</td>
            </tr>
            <tr>
                <th>所在地</th>
                <td>東京都千代田区神田練塀町300</td>
            </tr>
            <tr>
                <th>事業内容</th>
                <td>最高品質のマウス製品に特化したECサイト運営<br>プレミアムPC周辺機器の企画・販売</td>
            </tr>
            <tr>
                <th>設立</th>
                <td>2018年8月</td>
            </tr>
            <tr>
                <th>コンセプト</th>
                <td>最高品質のマウスを、あらゆるニーズにお応えして。</td>
            </tr>
        </tbody>
    </table>

    <h3>企業紹介</h3>
    <p>
    株式会社KELOTは、プロフェッショナルなユーザーから熱心なゲーマー、クリエイターまで、あらゆるニーズに応える**プレミアムマウスの専門店**です。私たちは、単なる入力デバイスではなく、あなたのパフォーマンスと快適性を最大限に引き出すための「相棒」となるマウスを厳選してお届けします。高度な技術を駆使した機能性、手に馴染む洗練されたデザイン、そして耐久性。これらのすべてを兼ね備えた最高品質のマウスを、世界中から探し求めています。お客様一人ひとりの作業を、より豊かで快適な体験へと導くことをお約束します。
    </p>

</div>

<?php require 'footer.php'; ?>