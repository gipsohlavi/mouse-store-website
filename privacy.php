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
    .privacy-policy-container {
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .privacy-policy-container h1 {
        font-size: 2em;
        margin-bottom: 30px;
    }

    .privacy-policy-container h3 {
        text-align: left;
        margin-top: 40px;
        margin-bottom: 10px;
        font-size: 1.2em;
    }

    .privacy-policy-container p,
    .privacy-policy-container ul {
        text-align: left;
        line-height: 1.6;
        margin-bottom: 20px;
    }

    .privacy-policy-container ul {
        margin-left: 20px;
        padding-left: 0;
    }
</style>

<div class="simple-hero">
    <div class="hero-inner">
        <h1>プライバシーポリシー</h1>
        <p>お客様の個人情報の取り扱いについて</p>
    </div>
</div>

<div class="privacy-policy-container">

    <p>
    当サイトを運営する**株式会社KELOT**（以下「当社」といいます。）は、お客様の個人情報保護の重要性について深く認識し、以下の方針に基づき、個人情報の適切な取り扱いに努めます。
    </p>

    <h3>1. 個人情報の取得について</h3>
    <p>
    当社は、お客様が当サイトで商品をご購入される際、お問い合わせをされる際などに、氏名、住所、電話番号、メールアドレス、クレジットカード情報などの個人情報を取得いたします。これらの情報は、適法かつ公正な手段によって取得します。
    </p>

    <h3>2. 個人情報の利用目的について</h3>
    <p>
    お客様から取得した個人情報は、以下の目的で利用します。
    </p>
    <ul>
        <li>ご注文いただいた商品の発送、および関連するご連絡</li>
        <li>商品やサービスに関するお問い合わせへの対応</li>
        <li>新商品やキャンペーン情報など、当社のサービスに関するご案内</li>
        <li>当サイトのサービス改善や、お客様の満足度向上のための分析</li>
    </ul>

    <h3>3. 個人情報の第三者提供について</h3>
    <p>
    当社は、以下の場合を除き、お客様の同意なく個人情報を第三者に提供することはありません。
    </p>
    <ul>
        <li>法令に基づく場合</li>
        <li>人の生命、身体または財産の保護のために必要があり、お客様の同意を得ることが困難な場合</li>
        <li>商品の配送など、業務遂行に必要な範囲で業務委託先に開示する場合</li>
    </ul>

    <h3>4. 個人情報の管理について</h3>
    <p>
    当社は、個人情報の漏洩、紛失、改ざんなどを防止するため、適切な安全管理措置を講じ、個人情報の保護に努めます。
    </p>

    <h3>5. 個人情報の開示、訂正、利用停止について</h3>
    <p>
    お客様がご自身の個人情報の開示、訂正、利用停止を希望される場合、ご本人であることを確認した上で、法令に基づき適切に対応いたします。
    </p>

</div>

<?php require 'footer.php'; ?>