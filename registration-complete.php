<?php session_start(); ?>
<?php require 'common.php'; ?>
<?php require 'header.php'; ?>

<?php
// 登録完了データの確認
if (!isset($_SESSION['registration_complete'])) {
    header('Location: customer-input.php');
    exit;
}

$registration_data = $_SESSION['registration_complete'];
$success_message = $_SESSION['success_message'] ?? '会員登録が完了しました。';

// 登録完了データをクリア（再表示防止）
unset($_SESSION['registration_complete']);
unset($_SESSION['success_message']);
?>

<div class="registration-complete-page">
    <div class="completion-container">
        <!-- 成功ヘッダー -->
        <div class="completion-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="completion-title">会員登録完了</h1>
            <p class="completion-subtitle">KELOTへようこそ！登録が正常に完了しました</p>
        </div>

        <!-- 登録情報表示 -->
        <div class="completion-content">
            <div class="registration-summary">
                <h2 class="summary-title">
                    <i class="fas fa-user-check"></i>
                    登録情報
                </h2>
                
                <div class="summary-card">
                    <div class="summary-row">
                        <span class="summary-label">会員ID</span>
                        <span class="summary-value">#<?= str_pad($registration_data['customer_id'], 4, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">お名前</span>
                        <span class="summary-value"><?= h($registration_data['name']) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">ログイン名</span>
                        <span class="summary-value"><?= h($registration_data['login']) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">配送先住所</span>
                        <span class="summary-value"><?= h($registration_data['address']) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">保有ポイント</span>
                        <span class="summary-value points-value">0 pt</span>
                    </div>
                </div>
            </div>

            <!-- 次のステップ案内 -->
            <div class="next-steps">
                <h2 class="steps-title">
                    <i class="fas fa-compass"></i>
                    次のステップ
                </h2>
                
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-icon shopping">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3>お買い物を始める</h3>
                        <p>豊富な商品ラインナップから、お気に入りの商品を見つけてください</p>
                        <a href="product.php" class="step-button btn-primary">
                            <i class="fas fa-arrow-right"></i>
                            商品を見る
                        </a>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon profile">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <h3>プロフィール設定</h3>
                        <p>配送先住所の追加や、お客様情報の詳細設定ができます</p>
                        <a href="shipping-address-list.php" class="step-button btn-outline">
                            <i class="fas fa-cog"></i>
                            設定画面へ
                        </a>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon points">
                            <i class="fas fa-gift"></i>
                        </div>
                        <h3>ポイント活用</h3>
                        <p>お買い物でポイントが貯まり、次回のお買い物でご利用いただけます</p>
                        <a href="point-info.php" class="step-button btn-info">
                            <i class="fas fa-info-circle"></i>
                            詳しく見る
                        </a>
                    </div>
                </div>
            </div>

            <!-- 重要なお知らせ -->
            <div class="important-notice">
                <div class="notice-header">
                    <i class="fas fa-info-circle"></i>
                    <h3>重要なお知らせ</h3>
                </div>
                <div class="notice-content">
                    <ul class="notice-list">
                        <li>
                            <i class="fas fa-shield-alt"></i>
                            登録情報は安全に保護されています
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            重要なお知らせはメールでご連絡いたします
                        </li>
                        <li>
                            <i class="fas fa-lock"></i>
                            ログイン情報は大切に保管してください
                        </li>
                        <li>
                            <i class="fas fa-headset"></i>
                            ご不明な点がございましたらお気軽にお問い合わせください
                        </li>
                    </ul>
                </div>
            </div>

            <!-- メインアクション -->
            <div class="main-actions">
                <a href="product.php" class="main-button btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    お買い物を始める
                </a>
                <a href="index.php" class="main-button btn-secondary">
                    <i class="fas fa-home"></i>
                    トップページへ
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.registration-complete-page {
    min-height: calc(100vh - 160px);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 20px;
    position: relative;
}

.registration-complete-page::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
    pointer-events: none;
}

.completion-container {
    max-width: 1000px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

.completion-header {
    text-align: center;
    margin-bottom: 50px;
}

.success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    color: white;
    font-size: 3rem;
    box-shadow: 0 20px 40px rgba(16, 185, 129, 0.3);
    animation: successPulse 2s ease-in-out infinite;
}

@keyframes successPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.completion-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 15px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.completion-subtitle {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.9);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.completion-content {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.summary-title, .steps-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.summary-title i { color: #10b981; }
.steps-title i { color: #3b82f6; }

.summary-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 40px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e2e8f0;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-label {
    font-weight: 500;
    color: #6b7280;
}

.summary-value {
    font-weight: 600;
    color: #1f2937;
}

.points-value {
    color: #10b981;
    font-size: 1.1rem;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.step-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.step-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.step-card:hover::before {
    transform: scaleX(1);
}

.step-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
}

.step-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 1.8rem;
    color: white;
}

.step-icon.shopping { background: linear-gradient(135deg, #f59e0b, #d97706); }
.step-icon.profile { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.step-icon.points { background: linear-gradient(135deg, #ef4444, #dc2626); }

.step-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 15px;
}

.step-card p {
    color: #6b7280;
    margin-bottom: 20px;
    line-height: 1.6;
}

.step-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border: none;
}

.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.btn-outline {
    background: white;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.btn-outline:hover {
    background: #f9fafb;
    color: #374151;
}

.btn-info {
    background: #06b6d4;
    color: white;
}

.btn-info:hover {
    background: #0891b2;
}

.important-notice {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border: 1px solid #f59e0b;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 40px;
}

.notice-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.notice-header i {
    color: #d97706;
    font-size: 1.25rem;
}

.notice-header h3 {
    color: #92400e;
    font-size: 1.2rem;
    font-weight: 600;
}

.notice-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.notice-list li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 8px 0;
    color: #92400e;
    line-height: 1.5;
}

.notice-list i {
    color: #d97706;
    margin-top: 2px;
    flex-shrink: 0;
}

.main-actions {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.main-button {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 18px 36px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    min-width: 200px;
    justify-content: center;
}

.main-button.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}

.main-button.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(59, 130, 246, 0.4);
}

.main-button.btn-secondary {
    background: white;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.main-button.btn-secondary:hover {
    background: #f9fafb;
    color: #374151;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .registration-complete-page {
        padding: 20px 15px;
    }
    
    .completion-content {
        padding: 30px 20px;
    }
    
    .completion-title {
        font-size: 2rem;
    }
    
    .steps-grid {
        grid-template-columns: 1fr;
    }
    
    .main-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .summary-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>

<?php require 'footer.php'; ?>