<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * The Location field is optional core markup: every call is guarded so the theme still runs on
 * cores without it, the view prints it, and its skin uses theme tokens only.
 * Usage:  php tests/place-field.php [theme-root]
 */

$root = $argv[1] ?? dirname(__DIR__);
$fail = [];

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$call  = '/\b(osc_place_field|osc_item_place_style)\s*\(/';
$seen  = 0;
foreach ($files as $f) {
    $path = $f->getPathname();
    if (substr($path, -4) !== '.php' || preg_match('#/(node_modules|tests|dist)/#', $path)) {
        continue;
    }
    $rel   = substr($path, strlen($root) + 1);
    $lines = file($path);
    foreach ($lines as $n => $line) {
        if (!preg_match_all($call, $line, $all) || preg_match('/^\s*(\*|\/\/)/', $line)) {
            continue;
        }
        foreach ($all[1] as $name) {
            if (preg_match('/function_exists\(\s*\'' . $name . '\'/', $line)) {
                $seen++;
                continue;
            }
            $near = implode('', array_slice($lines, max(0, $n - 4), 5));
            if (!preg_match('/function_exists\(\s*\'' . $name . '\'/', $near)) {
                $fail[] = "unguarded $name() at $rel:" . ($n + 1);
            } else {
                $seen++;
            }
        }
    }
}
if ($seen === 0) {
    $fail[] = 'no guarded place field call found';
}

$post = file_get_contents("$root/item-post.php");
if (!preg_match('/osc_place_field\(\s*\[/', $post)) {
    $fail[] = 'item-post.php does not print the place field';
}
if (strpos($post, 'ItemForm::country_select') === false) {
    $fail[] = 'item-post.php lost the old-core location block';
}

$css = file_get_contents("$root/css/storefront.css");
preg_match_all('/([^{}]*\.oe-place[^{}]*)\{([^{}]*)\}/', $css, $rules, PREG_SET_ORDER);
if (count($rules) < 10) {
    $fail[] = 'no .oe-place skin in css/storefront.css';
}
foreach ($rules as $r) {
    $sel  = trim($r[1]);
    $body = $r[2];
    if (preg_match('/#[0-9a-fA-F]{3,8}\b|\brgba?\(|\bhsla?\(/', $body)) {
        $fail[] = "colour literal in $sel";
    }
    if (preg_match('/box-shadow\s*:(?![^;]*var\()[^;]*[1-9]/', $body)) {
        $fail[] = "raw shadow in $sel";
    }
}
foreach (['.oe-place__select', '.oe-place__part', '.oe-place__legend'] as $sel) {
    if (strpos($css, $sel) === false) {
        $fail[] = "no skin for $sel";
    }
}

if ($fail) {
    fwrite(STDERR, "FAIL\n  " . implode("\n  ", $fail) . "\n");
    exit(1);
}
echo "ok\n";
