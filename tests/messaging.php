<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Messaging stays optional: core calls are guarded, views print core's markup through sf_messaging_link(),
 * and the stylesheet covers the messaging classes with theme tokens.
 * Usage:  php tests/messaging.php
 */

$root = dirname(__DIR__);
$fail = [];

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $path = $f->getPathname();
    if (substr($path, -4) === '.php' && !preg_match('#/(node_modules|tests|dist)/#', $path)) {
        $files[] = $path;
    }
}

$call = '/\b(osc_messaging_\w+|osc_item_uses_messaging|osc_push_\w+)\s*\(/';
foreach ($files as $path) {
    $rel = substr($path, strlen($root) + 1);
    $lines = file($path);
    foreach ($lines as $n => $line) {
        if (!preg_match($call, $line, $m) || preg_match('/^\s*(\*|\/\/)/', $line)) {
            continue;
        }
        // A call counts as guarded when function_exists() for it sits on the line or just above.
        $near = implode('', array_slice($lines, max(0, $n - 4), 5));
        if (!preg_match('/function_exists\(\s*\'' . preg_quote($m[1], '/') . '\'/', $near)) {
            $fail[] = "unguarded {$m[1]}() at $rel:" . ($n + 1);
        }
    }
}

// Core owns the messaging markup: the views reach it only through sf_messaging_link().
foreach (['common/header.php', 'common/account-header.php'] as $view) {
    $src = file_get_contents("$root/$view");
    if (preg_match($call, $src, $m)) {
        $fail[] = "$view calls {$m[1]}() directly";
    }
    if (preg_match('/oe-(badge|unread|msg-link)|data-osc-/', $src)) {
        $fail[] = "$view writes its own messaging markup";
    }
}
if (strpos(file_get_contents("$root/common/header.php"), 'sf_messaging_link()') === false) {
    $fail[] = 'common/header.php does not print sf_messaging_link()';
}

$css = file_get_contents("$root/css/storefront.css");
foreach (['.oe-thread', '.oe-message', '.oe-message.is-mine', '.oe-message.is-held', '.oe-compose', '.oe-push', '.oe-badge.oe-unread', '.oe-badge[hidden]'] as $sel) {
    if (!preg_match('/^\.sf (\.oe-page )?[^{]*' . preg_quote($sel, '/') . '[^{]*\{/m', $css)) {
        $fail[] = "no .sf rule for $sel";
    }
}

$classes = [
    '.oe-messenger', '.oe-messenger-list', '.oe-messenger-chat', '.oe-list-head', '.oe-list-filter', '.oe-list-tabs',
    '.oe-thread-link', '.oe-thread.is-unread', '.oe-thread.is-current', '.oe-thread-photo', '.oe-thread-initial',
    '.oe-thread-name', '.oe-thread-time', '.oe-thread-preview', '.oe-thread-flag', '.oe-chat-head', '.oe-chat-back',
    '.oe-chat-name', '.oe-chat-role', '.oe-menu', '.oe-menu-panel', '.oe-menu-item', '.oe-menu-report',
    '.oe-listing-card', '.oe-listing-photo', '.oe-listing-title', '.oe-listing-price', '.oe-listing-link',
    '.oe-day', '.oe-seen', '.oe-held', '.oe-typing', '.oe-typing-bubble', '.oe-compose-row', '.oe-compose-send',
    '.oe-compose-foot', '.oe-compose-counter', '.oe-stopbar', '.oe-chat-empty',
];
foreach ($classes as $sel) {
    if (!preg_match('/^\.sf [^{]*' . preg_quote($sel, '/') . '[^{]*\{/m', $css)) {
        $fail[] = "no .sf rule for $sel";
    }
}

// The phone and footer rules ship as CSS only (:has), no theme PHP.
$d4 = [
    '.sf:has(.oe-messenger) .sf-footer { display: none; }' => 'footer hidden on the messages screen',
    '.oe-account-main:has(> .oe-messenger.is-chat) > :is(.sf-account, .oe-h1) { display: none; }' => 'band and tabs hidden in a phone chat',
    '.oe-messenger:not(.is-chat) .oe-messenger-chat { display: none; }' => 'phone list shows one pane',
    '.oe-messenger.is-chat .oe-messenger-list { display: none; }' => 'phone chat shows one pane',
];
foreach ($d4 as $rule => $what) {
    if (strpos($css, $rule) === false) {
        $fail[] = "missing rule: $what";
    }
}
if (!preg_match('/prefers-reduced-motion: reduce\)\s*\{\s*\.sf \.oe-typing-bubble > i \{ animation: none/', $css)) {
    $fail[] = 'typing dots ignore prefers-reduced-motion';
}

$start = strpos($css, '/* Messages: inbox, thread');
$end   = strpos($css, "/* Core's message dialog", (int) $start);
if ($start === false || $end === false) {
    $fail[] = 'messaging CSS block not found';
} else {
    $block = substr($css, $start, $end - $start);
    if (preg_match('/#[0-9a-fA-F]{3,8}\b(?![^()]*\))/', preg_replace('/url\([^)]*\)/', '', $block))) {
        $fail[] = 'raw hex colour in the messaging CSS block';
    }
    // Colours come from tokens, so dark mode (data-theme dark and auto) follows with no extra rules.
    if (preg_match('/\[data-theme|prefers-color-scheme/', $block)) {
        $fail[] = 'messaging CSS has its own dark-mode override; use tokens';
    }
}

if ($fail) {
    echo "FAIL\n  " . implode("\n  ", $fail) . "\n";
    exit(1);
}
echo "OK\n";
