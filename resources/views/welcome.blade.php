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
    <link rel="stylesheet" href="/assets/css/app.css?v={{ filemtime(public_path('assets/css/app.css')) }}">
    <link rel="stylesheet" href="/assets/css/mobile.css?v={{ filemtime(public_path('assets/css/mobile.css')) }}" media="(max-width: 640px)">
    <link rel="icon" type="image/png" href="https://gridbase.com.do/wp-content/uploads/2026/03/cropped-imagen_2026-03-18_101800374-180x180.png">
    <!-- PWA iOS -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Bills">
    <link rel="apple-touch-icon" href="https://gridbase.com.do/wp-content/uploads/2026/03/cropped-imagen_2026-03-18_101800374-180x180.png">
    <link rel="manifest" href="/manifest.json">
    <script>
        (function() {
            window.__splashStartTime = Date.now();
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <style>
        #app-splash {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            opacity: 1;
            visibility: visible;
            transition: opacity 0.32s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.32s ease;
            user-select: none;
            -webkit-user-select: none;
            pointer-events: all;
        }
        html[data-theme="dark"] #app-splash {
            background-color: #0A0A0A;
        }
        #app-splash.splash-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .splash-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .splash-logo {
            width: 112px;
            height: auto;
            display: block;
            animation: splashLogoEntrance 0.38s cubic-bezier(0.16, 1, 0.3, 1) forwards, splashLogoPulse 2.2s ease-in-out 0.38s infinite;
            transform-origin: center center;
        }
        .splash-logo-accent {
            fill: #00a460;
        }
        .splash-logo-text {
            fill: #111827;
            transition: fill 0.2s ease;
        }
        html[data-theme="dark"] .splash-logo-text {
            fill: #FAFAFA !important;
        }
        .splash-progress-track {
            width: 80px;
            height: 3px;
            border-radius: 9999px;
            background: rgba(0, 0, 0, 0.08);
            overflow: hidden;
            position: relative;
            margin-top: 22px;
        }
        html[data-theme="dark"] .splash-progress-track {
            background: rgba(255, 255, 255, 0.12);
        }
        .splash-progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            background: #00a460;
            border-radius: 9999px;
            animation: splashLineMove 1.1s cubic-bezier(0.65, 0, 0.35, 1) infinite;
        }
        @keyframes splashLogoEntrance {
            0% {
                opacity: 0;
                transform: scale(0.88) translateY(6px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        @keyframes splashLogoPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.02);
            }
        }
        @keyframes splashLineMove {
            0% {
                left: 0%;
                width: 0%;
            }
            50% {
                left: 20%;
                width: 60%;
            }
            100% {
                left: 100%;
                width: 0%;
            }
        }
    </style>
</head>
<body>
    <div id="app-splash" aria-hidden="false">
        <div class="splash-container">
            <svg id="splash-logo" class="splash-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 437.18 523.89" aria-label="Bills">
                <path class="splash-logo-accent" fill="#00a460" d="M109.28,284.96c-9.54,0-17.3-7.76-17.31-17.29-.03-33.78,0-95.42.11-188.41,0-8.71,4.05-16.89,10.93-22.2l.21-.42,1.59-.87,1.28-.87h.32L202.54,2.15c2.56-1.41,5.44-2.15,8.31-2.15,9.55,0,17.32,7.77,17.32,17.32v194.72c0,6.32-3.44,12.13-8.98,15.17l-86.29,47.34-15.4,8.31c-2.54,1.37-5.38,2.1-8.22,2.1h0Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M341.17,119.82c.2-2.13.32-4.25.32-6.43s-.12-4.3-.32-6.43c-3.08-30.17-27.53-54.01-57.97-56.16h-40.43v168.66c0,9.67-5.27,18.57-13.74,23.22l-87.04,47.75h136.47c32.44,0,59.14-24.75,62.37-56.46.2-2.13.32-4.25.32-6.45s-.12-4.3-.32-6.42c-2.3-22.5-16.39-41.51-35.98-50.62.05-.02.1-.05.15-.07,19.69-9.09,33.88-28.09,36.18-50.59Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M58.65,451.43H0v-117.3h55.11c29.98,0,43.2,12.66,43.2,32.21,0,13.03-8.75,22.16-21.23,24.2,13.96,2.79,24.58,11.92,24.58,28.67,0,20.29-15.64,32.21-43.01,32.21ZM27.37,381.61h24.58c12.29,0,17.13-5.4,17.13-12.29,0-7.26-4.84-12.66-17.13-12.66h-24.58v24.95ZM27.37,402.84v25.88h27.18c12.47,0,18.25-5.03,18.25-12.85s-5.77-13.03-18.43-13.03h-27Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M138.34,334.13v117.3h-28.3v-117.3h28.3Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M179.49,334.13v94.02h59.58v23.27h-87.88v-117.3h28.3Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M275.38,334.13v94.02h59.58v23.27h-87.88v-117.3h28.3Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M361.58,408.79c1.12,16.2,13.22,22.71,27.74,22.71,12.47,0,20.29-12.66,20.29-12.66s-6.89-9.68-17.13-11.73l-21.97-3.91c-18.62-3.54-31.84-13.96-31.84-33.89,0-23.09,18.06-37.42,46.55-37.42,31.84,0,49.15,15.83,49.71,41.89l-26.07.75c-.75-13.78-10.24-20.48-23.83-20.48-11.92,0-18.62,4.84-18.62,12.85,0,6.7,5.21,9.12,13.78,10.8l21.97,3.91c24.02,4.28,35,16.2,35,35.38,0,24.2-21.04,36.68-47.85,36.68-31.28,0-53.62-15.45-53.62-43.94l25.88-.93Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M86.81,505.65h12.24c-1.78,5.03-6.57,7.86-12.16,7.86-9.32,0-14.59-6.4-14.59-16.13s5.11-15.64,13.62-15.64c6.32,0,10.44,3.04,11.9,7.94h12.23c-1.89-11.61-10.39-18.8-24.36-18.8s-26.02,10.78-26.02,26.5,10.62,26.5,24.8,26.5c6.81,0,12.64-2.27,15.4-7.38v6.4h10.78v-26.18h-23.83v8.92Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M143.75,486.79c-1.03-.49-2.43-.83-4.01-.83-4.86,0-8.59,3.49-9.97,7.54v-6.57h-11.67v35.99h11.67v-17.02c0-6.24,3.24-9,8.11-9,2.38,0,4.11.37,5.87,1.31v-11.42Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M160.89,477.66c0,3.39-2.12,6.42-5.3,7.58l-6.61,2.42v-12.28h11.91v2.28Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M155.76,488.68l5.05-1.88v36.12h-11.67v-24.69c0-4.25,2.64-8.06,6.63-9.55Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M194.45,516.51c-1.7,4.21-5.92,7.38-12.08,7.38-10.62,0-16.05-8.35-16.05-18.97s5.43-18.97,16.05-18.97c6.16,0,10.37,3.16,12.08,7.46v-21.56h11.75v51.06h-11.75v-6.4ZM194.45,504.52c0-5.75-3.32-9.4-8.11-9.4-5.59,0-8.19,3.97-8.19,9.81s2.59,9.89,8.19,9.89c4.78,0,8.11-3.65,8.11-9.48v-.81Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M236.59,522.92h-21.72v-51.06h20.83c12.64,0,17.83,5.51,17.83,13.62,0,5.92-4.21,9.97-9.65,10.94,6.24,1.05,11.1,5.19,11.1,12.48,0,8.83-6.48,14.02-18.4,14.02ZM221.68,493.9h14.26c7.38,0,10.54-3.16,10.54-8.11s-3.16-8.19-10.54-8.19h-14.26v16.29ZM221.68,499.33v17.83h14.75c7.62,0,11.59-3.32,11.59-8.83s-3.97-9-11.59-9h-14.75Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M290.98,515.95c0,1.86,1.05,2.76,2.76,2.76.81,0,2.11-.24,3.08-.81v3.65c-1.22,1.22-2.67,2.11-5.43,2.11-3.65,0-6.24-2.76-6.65-6.97-1.95,4.13-6.97,7.21-12.72,7.21-6.89,0-11.35-3.73-11.35-9.81,0-6.73,5.75-9.48,14.27-11.19l9.56-1.95v-1.38c0-4.86-2.84-7.86-7.78-7.86s-7.86,3-8.92,7.13l-6-.81c1.38-6.65,6.65-11.51,14.99-11.51,8.92,0,14.18,4.54,14.18,13.54v15.89ZM284.5,505.41l-7.94,1.7c-5.59,1.22-9.16,2.27-9.16,6.57,0,3,2.03,5.27,6.08,5.27,6.24,0,11.02-4.7,11.02-11.83v-1.7Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M305.17,510.6c.65,5.43,4.46,8.35,10.54,8.35,4.7,0,8.19-1.7,8.19-5.27,0-3.32-2.35-4.46-6.73-5.27l-6-1.05c-6.65-1.05-10.62-3.89-10.62-9.97,0-6.48,5.43-10.86,13.62-10.86,9.4,0,14.91,4.38,15.56,12.56l-5.43.32c-.81-5.43-4.13-7.94-10.13-7.94-4.46,0-7.38,2.03-7.38,5.27,0,2.84,1.78,4.3,5.43,4.94l6.65,1.05c7.13,1.22,11.35,3.89,11.35,10.29,0,7.05-6.24,10.86-14.51,10.86-8.83,0-15.56-4.13-16.21-12.89l5.67-.41Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M369.85,511c-1.78,8.02-7.7,12.89-16.53,12.89-10.54,0-18.24-7.13-18.24-18.16s7.7-19.21,17.91-19.21c11.27,0,17.02,8.02,17.02,17.67v2.67h-28.21c.32,6.89,5.27,11.75,11.59,11.75,5.92,0,9.4-2.84,10.86-8.27l5.59.65ZM363.12,502.49c-.24-5.67-3.4-10.86-10.13-10.86s-10.21,5.03-11.02,10.86h21.15Z"></path>
                <path class="splash-logo-text" fill="#111827" d="M370.41,483.31c1.87,0,3.39,1.42,3.39,3.39s-1.52,3.39-3.39,3.39-3.39-1.42-3.39-3.39,1.52-3.39,3.39-3.39ZM370.41,489.63c1.61,0,2.91-1.22,2.91-2.93s-1.31-2.93-2.91-2.93-2.91,1.22-2.91,2.93,1.31,2.93,2.91,2.93ZM370.41,484.66c1.13,0,1.77.7,1.85,1.71l-.55.03c-.04-.72-.49-1.22-1.28-1.22s-1.33.58-1.33,1.54.54,1.52,1.33,1.52,1.23-.51,1.28-1.22l.55.04c-.07,1.02-.72,1.71-1.85,1.71s-1.94-.84-1.94-2.05.85-2.05,1.94-2.05Z"></path>
            </svg>
            <div class="splash-progress-track">
                <div class="splash-progress-bar"></div>
            </div>
        </div>
    </div>
    <div id="app"></div>
    <div class="toast-container" id="toast-container"></div>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>window.APP_VERSION = '{{ filemtime(public_path('assets/js/app.js')) }}';</script>
    <script type="module" src="/assets/js/app.js?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(reg => {
                    reg.addEventListener('updatefound', () => {
                        const newWorker = reg.installing;
                        if (newWorker) {
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    if (window.App && typeof window.App.showToast === 'function') {
                                        window.App.showToast('Nueva versión instalada. Recargando...', 'success', 4000);
                                    }
                                    setTimeout(() => window.location.reload(), 1200);
                                }
                            });
                        }
                    });
                }).catch(() => {});
            });
        }
        setTimeout(() => {
            const splash = document.getElementById('app-splash');
            if (splash && !splash.classList.contains('splash-hidden')) {
                splash.classList.add('splash-hidden');
                setTimeout(() => { if (splash.parentNode) splash.parentNode.removeChild(splash); }, 350);
            }
        }, 3500);
    </script>
</body>
</html>
