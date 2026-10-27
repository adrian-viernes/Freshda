<?php

/*
|--------------------------------------------------------------------------
| Dashboard asset modules
|--------------------------------------------------------------------------
|
| The dashboard ships as small, single-concern files instead of one giant
| bundle (the project has no build step, so every module is loaded on its
| own with a cache-busting ?v= query). The order below is the load order.
|
| Keep it strict: all modules share the global scope of the page, so a
| module may only rely on what the modules *before* it declared - exactly
| as if the files were concatenated. Feature tests read the dashboard
| script and stylesheet through this same list, so the two can never
| drift apart.
|
*/

return [

    'dashboard' => [

        'js' => [
            'core.js',              // state, boot payload, csrf + api helpers
            'preferences.js',       // saved theme / language / sound settings
            'i18n.js',              // translation dictionary + apply pass
            'settings-engine.js',   // read, write and apply one setting
            'sections.js',          // page sections, showPage(), phone drawer
            'toast.js',             // showToast()
            'notifications.js',     // notification list and its actions
            'topbar.js',            // avatar dropdown
            'search.js',            // global search box
            'history.js',           // filters, sorting, detail card, export
            'scan.js',              // single-scan flow and live camera
            'analyze.js',           // scan result, recent scans, overview stats
            'dialogs.js',           // logout + generic confirmation contents
            'batch.js',             // 3-5 photo batch verification
            'inventory.js',         // vendor stock table
            'suppliers.js',         // supplier table, three-dot row menu
            'deliveries.js',        // deliveries table, check in / accept / reject
            'reports.js',           // report date ranges and exports
            'profile.js',           // profile form, photo, change password
            'settings-page.js',     // settings page widgets
            'init.js',              // boot
        ],

        'css' => [
            'base.css',                 // tokens, reset, typography, primitives
            'profile.css',              // profile page grid
            'sidebar.css',              // desktop sidebar
            'topbar.css',               // top bar, user chip, row icons
            'notifications.css',        // notifications page
            'content.css',              // page headers, cards, stat grids, tables
            'camera.css',               // camera preview and viewfinder
            'batch.css',                // batch sampling cards
            'settings.css',             // full settings page, camera nav button
            'history.css',              // history search and detail card
            'inventory.css',            // inventory row actions and its table
            'export-card.css',          // export scan data card
            'reports.css',              // reports page
            'dialogs.css',              // confirm contents, upload picker, dropdown
            'suppliers-deliveries.css', // supplier + delivery modules, detail panel
            'phone-drawer.css',         // phone sidebar drawer
        ],

    ],

];
