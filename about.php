<?php
session_start();
require 'common.php';
require 'header.php';
/* menu.php は不要のため参照を削除 */
?>
<style>
    .simple-hero {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        padding: 48px 16px;
        margin-bottom: 16px;
    }
    .simple-hero .hero-inner { max-width: 1000px; margin: 0 auto; }
    .simple-hero h1 { margin: 0 0 8px 0; font-size: 1.8rem; font-weight: 700; }
    .simple-hero p { opacity: .9; margin: 0; }
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

<div class="simple-hero">
    <div class="hero-inner">
        <h1>会社概要</h1>
        <p>KELOTの基本情報とコンセプト</p>
    </div>
</div>

<div class="company-profile-container">

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
            <tr>
                <th>連絡先</th>
                <td>
                    TEL：0120-XXX-XXX 9:30～17:30（土日祝除く）<br>
                    FAX：0120-XXX-XXX<br>
                </td>
            </tr>
        </tbody>
    </table>

    <h3>企業紹介</h3>
    <br>
    <p>K：クリックひとつで</p>
    <p>E：選べる楽しさ！</p>
    <p>L：ラクラク見つかる</p>
    <p>O：驚き価格の</p>
    <p>T：とっておきマウス！</p>
    <p>
    株式会社KELOTは、プロフェッショナルなユーザーから熱心なゲーマー、クリエイターまで、あらゆるニーズに応える**プレミアムマウスの専門店**です。私たちは、単なる入力デバイスではなく、あなたのパフォーマンスと快適性を最大限に引き出すための「相棒」となるマウスを厳選してお届けします。高度な技術を駆使した機能性、手に馴染む洗練されたデザイン、そして耐久性。これらのすべてを兼ね備えた最高品質のマウスを、世界中から探し求めています。お客様一人ひとりの作業を、より豊かで快適な体験へと導くことをお約束します。
    </p>

</div>

<?php require 'footer.php'; ?>