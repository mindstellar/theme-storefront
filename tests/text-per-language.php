<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * The tagline and footer text are one value per language: saved per locale, read in the visitor's
 * language, then the site default, then the old single value; the old value moves once.
 * Usage:  php tests/text-per-language.php
 */

$root  = dirname(__DIR__);
$fail  = [];
$prefs = [];
$post  = [];
$cur   = 'en_US';
$pin   = static function (string $what, $want, $got) use (&$fail): void {
    if ($want !== $got) {
        $fail[] = $what . "\n    want " . json_encode($want) . "\n    got  " . json_encode($got);
    }
};

function osc_get_preference($key, $section = 'osclass')
{
    return $GLOBALS['prefs'][$section . '/' . $key] ?? '';
}
function osc_set_preference($key, $value = '', $section = 'osclass')
{
    $GLOBALS['prefs'][$section . '/' . $key] = $value;
}
function osc_delete_preference($key, $section = 'osclass')
{
    unset($GLOBALS['prefs'][$section . '/' . $key]);
}
function osc_settings_locales()
{
    return ['en_US' => 'English', 'en_IN' => 'English (India)', 'hi_IN' => 'Hindi'];
}
function osc_current_user_locale()
{
    return $GLOBALS['cur'];
}
function osc_language()
{
    return 'en_US';
}
final class Params
{
    public static function getParam($key)
    {
        return $GLOBALS['post'][$key] ?? '';
    }
}

require $root . '/inc/text-settings.php';

$read = static fn (string $key): string => storefront_text_setting($key, 'fallback');

// Saving writes one value per enabled locale.
$post = ['hero_taglineen_US' => 'Hello', 'hero_taglineen_IN' => '', 'hero_taglinehi_IN' => 'Namaste'];
storefront_save_text('hero_tagline');
$pin('save: one preference per locale', ['Hello', '', 'Namaste'], [
    $prefs['storefront/hero_taglineen_US'] ?? null, $prefs['storefront/hero_taglineen_IN'] ?? null, $prefs['storefront/hero_taglinehi_IN'] ?? null,
]);

// A visitor sees their own language; a locale with no value gets the default locale's.
$cur = 'hi_IN';
$pin('locale B sees B text', 'Namaste', $read('hero_tagline'));
$cur = 'en_IN';
$pin('empty locale gets the default locale text', 'Hello', $read('hero_tagline'));
$cur = 'en_US';
$pin('default locale', 'Hello', $read('hero_tagline'));
$pin('nothing saved gives the fallback', 'fallback', $read('footer_message'));

// The one-time move copies the old value to every language and keeps a value already set.
$prefs = ['storefront/footer_message' => 'Old line', 'storefront/footer_messagehi_IN' => 'Hindi line'];
storefront_migrate_text_settings();
$pin('migration: old value copied, set one kept, old key gone', ['Old line', 'Old line', 'Hindi line', null], [
    $prefs['storefront/footer_messageen_US'] ?? null, $prefs['storefront/footer_messageen_IN'] ?? null,
    $prefs['storefront/footer_messagehi_IN'] ?? null, $prefs['storefront/footer_message'] ?? null,
]);
$cur = 'en_IN';
$pin('after migration a locale shows the old text', 'Old line', $read('footer_message'));

// Before the move has run, the old single value still shows.
$prefs = ['storefront/hero_tagline' => 'Old'];
$cur   = 'hi_IN';
$pin('unmigrated old value still shows', 'Old', $read('hero_tagline'));

// The form and the readers use it.
$src = file_get_contents($root . '/admin/settings.php');
$pin('form: both fields are translated', 2, preg_match_all("/'translate' => true/", $src));
foreach (['main.php' => 'hero_tagline', 'common/footer.php' => 'footer_message'] as $file => $key) {
    $pin("$file reads $key per language", true, str_contains(file_get_contents("$root/$file"), "storefront_text_setting('$key'"));
}

if ($fail) {
    fwrite(STDERR, "FAIL\n  " . implode("\n  ", $fail) . "\n");
    exit(1);
}
echo "ok\n";
