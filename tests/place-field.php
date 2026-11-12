<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * The Location field: item-post.php prints core's field only on a core that has it and its style
 * setting, else the old selects; the old dropdown choice moves to core once; every core call is
 * guarded, and the skin uses theme tokens only.
 * Usage:  php tests/place-field.php
 */

$root = dirname(__DIR__);
$fail = [];
$pin  = static function (string $what, $want, $got) use (&$fail): void {
    if ($want !== $got) {
        $fail[] = $what . "\n    want " . json_encode($want) . "\n    got  " . json_encode($got);
    }
};

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$call  = '/\b(osc_place_field|osc_item_place_style)\s*\(/';
foreach ($files as $f) {
    $path = $f->getPathname();
    if (substr($path, -4) !== '.php' || preg_match('#/(node_modules|tests|dist)/#', $path)) {
        continue;
    }
    $lines = file($path);
    foreach ($lines as $n => $line) {
        if (!preg_match_all($call, $line, $all) || preg_match('/^\s*(\*|\/\/)/', $line)) {
            continue;
        }
        // A call counts as guarded when its function_exists(), or storefront_core_place_field(), sits on the line or just above.
        $near = implode('', array_slice($lines, max(0, $n - 4), 5));
        foreach ($all[1] as $name) {
            if (!preg_match('/function_exists\(\s*\'' . $name . '\'|storefront_core_place_field\(\)/', $near)) {
                $fail[] = "unguarded $name() at " . substr($path, strlen($root) + 1) . ':' . ($n + 1);
            }
        }
    }
}

preg_match_all('/([^{}]*\.oe-place[^{}]*)\{([^{}]*)\}/', (string) file_get_contents("$root/css/storefront.css"), $rules, PREG_SET_ORDER);
foreach ($rules as $r) {
    if (preg_match('/#[0-9a-fA-F]{3,8}\b|\brgba?\(|\bhsla?\(/', $r[2])) {
        $fail[] = 'colour literal in ' . trim($r[1]);
    }
    if (preg_match('/box-shadow\s*:(?![^;]*var\()[^;]*[1-9]/', $r[2])) {
        $fail[] = 'raw shadow in ' . trim($r[1]);
    }
}

$run = static function (string $mode) use ($root): array {
    $json = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tests/lib/place-field-run.php') . ' ' . $mode);

    return (array) json_decode((string) $json, true);
};
$names = static fn (array $calls): array => array_values(array_filter(array_map(static fn (array $c): string => $c[0], $calls), static fn (string $n): bool => in_array($n, [
    'osc_place_field', 'ItemForm::country_select', 'ItemForm::region_select', 'ItemForm::city_select', 'ItemForm::region_text', 'ItemForm::city_text',
], true)));
$label = static function (array $calls): ?string {
    foreach ($calls as $c) {
        if ($c[0] === 'osc_place_field') {
            return $c[1]['label'];
        }
    }

    return null;
};

$new = $run('new');
$pin('new core: a theme dropdown site gets the core dropdown, and the next request writes nothing', ['dropdown', ['osclass/item_place_style=dropdown', 'storefront/place_style_mapped=1']], [$new['map']['dropdown']['style'] ?? null, $new['map']['dropdown']['writes'] ?? null]);
$pin('new core: an autocomplete site keeps the core default', ['', ['storefront/place_style_mapped=1']], [$new['map']['autocomplete']['style'] ?? null, $new['map']['autocomplete']['writes'] ?? null]);
$pin('new core: a saved core style is never overwritten', ['single', ['storefront/place_style_mapped=1']], [$new['map']['saved']['style'] ?? null, $new['map']['saved']['writes'] ?? null]);
foreach (['autocomplete/single' => 'Location', 'dropdown/single' => 'Location', 'autocomplete/fields' => '', 'dropdown/fields' => ''] as $case => $want) {
    $calls = $new['post'][$case] ?? [];
    $pin("new core, $case: the form prints only core's place field", [['osc_place_field'], $want], [$names($calls), $label($calls)]);
}

foreach (['phase1', 'old'] as $mode) {
    $r = $run($mode);
    $pin("$mode core: nothing is mapped", [[], [], []], [$r['map']['dropdown']['writes'] ?? null, $r['map']['autocomplete']['writes'] ?? null, $r['map']['saved']['writes'] ?? null]);
    $pin("$mode core: the theme's autocomplete setting prints the country select and text boxes", ['ItemForm::country_select', 'ItemForm::region_text', 'ItemForm::city_text'], $names($r['post']['autocomplete/single'] ?? []));
    $pin("$mode core: the theme's dropdown setting prints three selects", ['ItemForm::country_select', 'ItemForm::region_select', 'ItemForm::city_select'], $names($r['post']['dropdown/single'] ?? []));
}

if ($fail) {
    fwrite(STDERR, "FAIL\n  " . implode("\n  ", $fail) . "\n");
    exit(1);
}
echo "ok\n";
