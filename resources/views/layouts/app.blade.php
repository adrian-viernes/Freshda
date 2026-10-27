<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="description" content="Fresh-DA — AI-powered fish freshness & species recognition for local vendors.">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Fresh-DA">

    <link rel="icon" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <title>@yield('title', 'Fresh-DA')</title>

    @stack('head')
    @stack('styles')
</head>

<body>
    <script>
        /* Apply the saved theme before the first paint so every page — login,
           dashboard — opens in the same light/dark state the user last chose
           anywhere. `freshda-dark` is the shared flag; the dashboard's
           settings blob is only a fallback for older visits. A view that
           defines the `lockLightTheme` section (the landing page) opts out
           and always renders light. */
        (function () {
            if (@json($__env->hasSection('lockLightTheme'))) return;

            var flag = null;
            try {
                flag = localStorage.getItem('freshda-dark');
            } catch (e) {}

            if (flag === null) {
                try {
                    var settings = JSON.parse(localStorage.getItem('freshda-settings') || 'null');
                    if (settings && typeof settings === 'object' && settings.darkMode !== undefined) {
                        flag = settings.darkMode ? '1' : '0';
                    }
                } catch (e) {}
            }

            if (flag === '1') document.body.classList.add('dark');
        })();
    </script>

    @yield('content')

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                /* updateViaCache: 'none' stops the browser from reusing a cached
                   copy of sw.js itself, so fixes here ship immediately. */
                navigator.serviceWorker
                    .register("{{ asset('sw.js') }}", { updateViaCache: 'none' })
                    .catch(function () {});

                /* Reload once when a newer worker takes control, so long-lived
                   tabs never run an old bundle against a newer backend. */
                var hadController = !!navigator.serviceWorker.controller;
                var refreshing = false;
                navigator.serviceWorker.addEventListener('controllerchange', function () {
                    if (!hadController || refreshing) return;
                    refreshing = true;
                    window.location.reload();
                });
            });
        }
    </script>

    @stack('scripts')
</body>

</html>
