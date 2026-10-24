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

$start = strpos($css, '/* Messages: inbox, thread');
$end   = strpos($css, "/* Core's message dialog", (int) $start);
if ($start === false || $end === false) {
    $fail[] = 'messaging CSS block not found';
} elseif (preg_match('/#[0-9a-fA-F]{3,8}\b/', substr($css, $start, $end - $start))) {
    $fail[] = 'raw hex colour in the messaging CSS block';
}

if ($fail) {
    echo "FAIL\n  " . implode("\n  ", $fail) . "\n";
    exit(1);
}
echo "OK\n";
