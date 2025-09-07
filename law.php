<?php
session_start();
require 'common.php';
require 'header.php';

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
    .legal-container {
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .legal-container h1 {
        font-size: 2em;
        margin-bottom: 30px;
    }

    .legal-container h3 {
        text-align: left;
        margin-top: 40px;
        margin-bottom: 10px;
        font-size: 1.2em;
    }

    .legal-container p,
    .legal-container ul,
    .legal-container li {
        text-align: left;
        line-height: 1.6;
    }

    .legal-container p {
        margin-bottom: 20px;
    }

    .legal-container ul {
        margin-bottom: 20px;
        list-style-type: none; /* リストの黒丸を非表示にする */
        padding-left: 0;
    }
</style>

<div class="simple-hero">
    <div class="hero-inner">
        <h1>特定商取引法に基づく表記</h1>
        <p>販売事業者の情報と取引条件</p>
    </div>
</div>

<div class="legal-container">

    <h3>1. 販売事業者</h3>
    <p>株式会社KELOT</p>

    <h3>2. 運営責任者</h3>
    <p>森川 森男</p>

    <h3>3. 所在地</h3>
    <p>
    〒101-0041<br>
    東京都千代田区神田須田町1-1
    </p>

    <h3>4. 連絡先</h3>
    <p>
    電話番号：03-1234-5678<br>
    メールアドレス：info@kelot.shop<br>
    ※電話でのお問い合わせは平日10:00〜17:00にお願いいたします。
    </p>

    <h3>5. 商品の販売価格</h3>
    <p>各商品ページに記載しています。表示価格は消費税込みです。</p>

    <h3>6. 商品代金以外の必要料金</h3>
    <ul>
        <li>送料：**送料は、お届け先の地域によって異なります。詳細は<a href="postage.php">**配送・送料について**</a>のページでご確認ください。**</li>
        <li>振込手数料：銀行振込をご利用の場合</li>
    </ul>

    <h3>7. 支払い方法</h3>
    <p>クレジットカード決済、銀行振込、代金引換</p>

    <h3>8. 支払い期限</h3>
    <ul>
        <li>クレジットカード決済：各カード会社の定める引き落とし日</li>
        <li>銀行振込：ご注文から7日以内</li>
        <li>代金引換：商品受け取り時</li>
    </ul>

    <h3>9. 商品の引き渡し時期</h3>
    <p>ご注文確定後、2〜5営業日以内に発送いたします。</p>

    <h3>10. 返品・交換</h3>
    <p>商品に欠陥がある場合のみ、商品到着後7日以内にご連絡いただいた場合に限り、返品・交換を承ります。お客様のご都合による返品・交換はできません。</p>

</div>

<?php require 'footer.php'; ?>