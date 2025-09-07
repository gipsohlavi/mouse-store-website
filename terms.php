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
    .terms-of-service-container {
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .terms-of-service-container h1 {
        font-size: 2em;
        margin-bottom: 30px;
    }

    .terms-of-service-container h3 {
        text-align: left;
        margin-top: 40px;
        margin-bottom: 10px;
        font-size: 1.2em;
    }

    .terms-of-service-container p,
    .terms-of-service-container ul {
        text-align: left;
        line-height: 1.6;
        margin-bottom: 20px;
    }
    
    .terms-of-service-container ul {
        margin-left: 20px;
        padding-left: 0;
    }
</style>

<div class="simple-hero">
    <div class="hero-inner">
        <h1>利用規約</h1>
        <p>サイトのご利用条件について</p>
    </div>
</div>

<div class="terms-of-service-container">

    <p>
    当サイトをご利用いただく際は、本規約に同意したものとみなします。
    </p>

    <h3>1. サービスの内容</h3>
    <p>
    当サイトは、プレミアムマウスの販売サービスを提供します。サービスは予告なく変更、中断、終了することがあります。
    </p>

    <h3>2. ご注文について</h3>
    <p>
    ご注文は、当サイトの注文フォームを通じてのみ受け付けます。ご注文が完了した時点でお客様と当社との間で売買契約が成立します。ただし、在庫切れなどにより、ご注文をお断りする場合もございます。
    </p>

    <h3>3. お支払いについて</h3>
    <p>
    お支払い方法は、クレジットカード決済、銀行振込をご利用いただけます。お支払いにかかる手数料はお客様のご負担となります。
    </p>

    <h3>4. 返品・交換について</h3>
    <p>
    商品に欠陥があった場合のみ、返品または交換を承ります。商品到着後7日以内に当社の定める方法でご連絡ください。お客様のご都合による返品・交換は原則としてお受けできません。
    </p>

    <h3>5. 禁止事項</h3>
    <p>
    当サイトの利用にあたり、以下の行為を禁止します。
    </p>
    <ul>
        <li>他のお客様や当社、または第三者の財産、プライバシーなどを侵害する行為</li>
        <li>当サイトの運営を妨害する行為</li>
        <li>法令に違反する行為</li>
    </ul>

    <h3>6. 著作権</h3>
    <p>
    当サイトに掲載されているすべてのコンテンツ（文章、画像、ロゴなど）の著作権は、当社または正当な権利を有する第三者に帰属します。無断での複製、転載を禁じます。
    </p>

</div>

<?php require 'footer.php'; ?>