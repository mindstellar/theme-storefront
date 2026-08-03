<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
$sf_site = osc_get_preference('pageTitle');
?><!doctype html>
<html lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo osc_esc_html(ucfirst($sf_site) . ' — ' . __('under maintenance', 'storefront')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Archivo:wght@400;600;800&display=swap');
        /* Standalone page (core serves it without the theme stylesheet), so it carries
           its own tokens — mirroring the theme values — rather than any raw colour. */
        :root {
            color-scheme: light dark;
            --color-text: #0f2742;
            --color-bg: #f7f5f1;
            --color-accent: #12a6a0;
            --color-muted: #435466;
        }
        @media (prefers-color-scheme: dark) {
            :root { --color-text: #eaf1f8; --color-bg: #0c1a2b; --color-muted: #93a6b8; }
        }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; text-align: center;
            font: 400 17px/1.6 "Archivo", system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--color-text); background: var(--color-bg); padding: 40px; }
        main { max-width: 560px; }
        .mark { width: 18px; height: 18px; background: var(--color-accent); display: inline-block; margin-bottom: 20px; }
        h1 { font-family: "Archivo", sans-serif; font-weight: 800; font-size: clamp(28px, 6vw, 44px);
            line-height: 1.05; margin: 0 0 12px; letter-spacing: -.02em; }
        p { color: var(--color-muted); margin: 0 0 10px; }
    </style>
</head>
<body>
<main>
    <span class="mark" aria-hidden="true"></span>
    <h1><?php _e("We'll be back soon", 'storefront'); ?></h1>
    <p><?php _e("Sorry for the inconvenience — we're performing some maintenance right now. We'll be back online shortly.", 'storefront'); ?></p>
    <p><?php printf(__('— The %s team', 'storefront'), osc_esc_html($sf_site)); ?></p>
</main>
</body>
</html>
