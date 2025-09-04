<div class="admin-footer">
    <div class="admin-footer-content">
        <div class="admin-footer-info">
            <div class="footer-section">
                <p>&copy; <?= date('Y') ?> KELOT ECサイト管理システム. All rights reserved.</p>
                <p class="admin-footer-version">Version 1.0.0 | Build <?= date('Ymd') ?></p>
            </div>
            <div class="footer-section">
                <div class="admin-footer-stats">
                    <span>セッション時間: <span id="session-time">00:00</span></span>
                    <span>サーバー時刻: <?= date('Y-m-d H:i:s') ?></span>
                </div>
            </div>
        </div>
        <div class="admin-footer-security">
            <i class="fas fa-shield-alt"></i>
            <span>セキュア接続</span>
        </div>
    </div>
</div>

<script>
    // セッション時間カウンター
    let sessionStartTime = sessionStorage.getItem('admin-session-start');
    if (!sessionStartTime) {
        sessionStartTime = Date.now();
        sessionStorage.setItem('admin-session-start', sessionStartTime);
    }

    function updateSessionTime() {
        const elapsed = Math.floor((Date.now() - sessionStartTime) / 1000);
        const hours = Math.floor(elapsed / 3600);
        const minutes = Math.floor((elapsed % 3600) / 60);
        const seconds = elapsed % 60;

        const timeStr = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        const sessionTimeElement = document.getElementById('session-time');
        if (sessionTimeElement) {
            sessionTimeElement.textContent = timeStr;
        }
    }

    updateSessionTime();
    setInterval(updateSessionTime, 1000);

    // セッション警告（30分）
    setTimeout(() => {
        if (confirm('セッションの有効期限が近づいています。作業を続行しますか？')) {
            // セッション延長処理
            sessionStorage.setItem('admin-session-start', Date.now());
        }
    }, 30 * 60 * 1000);
</script>

</body>

</html>