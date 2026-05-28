<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="description" content="GridBase Bills - Sistema de facturación y cotizaciones de GridBase Digital Solutions">
    <meta name="theme-color" content="#111827">
    <title>GridBase Bills</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="/assets/css/app.css?v=49">
    <link rel="stylesheet" href="/assets/css/mobile.css?v=6" media="(max-width: 640px)">
    <link rel="icon" type="image/png" href="https://gridbase.com.do/wp-content/uploads/2026/03/cropped-imagen_2026-03-18_101800374-180x180.png">
    <!-- PWA iOS -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Bills">
    <link rel="apple-touch-icon" href="https://gridbase.com.do/wp-content/uploads/2026/03/cropped-imagen_2026-03-18_101800374-180x180.png">
    <link rel="manifest" href="/manifest.json">
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body>
    <div id="app"></div>
    <div class="toast-container" id="toast-container"></div>
    <script type="module" src="/assets/js/app.js?v=50"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
</body>
</html>
