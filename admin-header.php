<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' | ' : '' ?>KELOT管理者システム - ECサイト管理</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style-admin.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <!-- JQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/ui-lightness/jquery-ui.css">

    <!-- JQuery datetimepicker -->
    <script src="https://cdn.jsdelivr.net/npm/jquery-datetimepicker@2.5.20/build/jquery.datetimepicker.full.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-datetimepicker@2.5.20/jquery.datetimepicker.css">
        
    <script>
        $(function () {
        $(".datepicker").datetimepicker();
        });
    </script>
</head>

<body>

    <div class="admin-header">
        <div class="admin-header-content">
            <h1>
                <i class="fas fa-cogs"></i>
                KELOT ECサイト管理システム
                <span class="system-badge">Admin</span>
            </h1>
            <div class="admin-header-info">
                <div class="admin-user-info">
                    <div class="admin-status-indicator"></div>
                    <i class="fas fa-user-shield"></i>
                    <span>管理者</span>
                </div>
                <div class="admin-time" id="current-time"></div>
            </div>
        </div>
    </div>

    <script>
        // リアルタイム時刻表示
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleString('ja-JP', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }

        updateTime();
        setInterval(updateTime, 60000); // 1分ごとに更新

        // 現在のページをアクティブ表示
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.admin-nav a');

            navLinks.forEach(link => {
                if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
                    link.classList.add('active');
                }
            });
        });
    </script>