<?php
session_start();
require 'common.php';
require 'header.php';
require 'menu.php';
?>
<style>
    .contact-container {
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .contact-container h1 {
        font-size: 2em;
        margin-bottom: 30px;
    }

    .contact-container p {
        text-align: center;
        line-height: 1.6;
        margin-bottom: 30px;
    }

    .contact-form {
        text-align: left;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 8px;
        background-color: #f9f9f9;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-group input,
    .form-group textarea {
        width: calc(100% - 20px);
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1em;
    }

    .form-group textarea {
        height: 150px;
        resize: vertical;
    }

    .submit-btn {
        display: inline-block;
        padding: 12px 30px;
        font-size: 1.1em;
        color: #fff;
        background-color: #007bff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .submit-btn:hover {
        background-color: #0056b3;
    }
</style>

<div class="contact-container">

    <h1>お問い合わせ</h1>

    <p>
    ご質問やご意見、ご要望がございましたら、下記フォームよりお気軽にお問い合わせください。<br>
    **※送料は別途記載しています。**
    </p>

    <div class="contact-form">
        <form action="#" method="post">
            <div class="form-group">
                <label for="name">お名前</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="message">お問い合わせ内容</label>
                <textarea id="message" name="message" required></textarea>
            </div>

            <button type="submit" class="submit-btn">送信する</button>
        </form>
    </div>

</div>

<?php require 'footer.php'; ?>