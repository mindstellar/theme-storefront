<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * The headline, tagline, search placeholder, footer text and site name are one value per language,
 * in core's preferences: the settings and brand forms
 * shows each language what its visitors see (its own text, the default language's, then the old
 * single value), so saving before the update has moved the old value keeps it; a visitor reads
 * their language, then the default; the update moves the old value to every language once.
 *
 * DB-backed, on a scratch database through the core's test helpers.
 * Usage:  SHOPCLASS_CORE=/path/to/shopclass/ php tests/text-per-language.php
 */

$core = rtrim((string) (getenv('SHOPCLASS_CORE') ?: getenv('OSC_CORE') ?: dirname(__DIR__, 2) . '/shopclass'), '/') . '/';
require_once $core . 'tests/lib/scratchdb.php';
require_once $core . 'tests/lib/harness.php';

ob_start();
$admin = scratchdb_session('storefront_text_per_language');
define('OC_ADMIN', true);
if (!defined('OSC_CACHE_TTL')) {
    define('OSC_CACHE_TTL', 60);
}
foreach (array(
    'osc_base_url'               => 'return "http://localhost/";',
    'osc_admin_base_url'         => 'return "http://localhost/oc-admin/";',
    'osc_admin_render_theme_url' => 'return "http://localhost/oc-admin/?page=appearance&action=render&file=" . ($args[0] ?? "");',
    'osc_plugins_path'           => 'return ABS_PATH . "oc-content/plugins/";',
    'osc_base_path'              => 'return ABS_PATH;',
    'osc_current_web_theme'      => 'return "storefront";',
    'osc_csrf_token_form'        => 'return "";',
) as $fn => $body) {
    if (!function_exists($fn)) {
        eval('function ' . $fn . '(...$args) { ' . $body . ' }');
    }
}
foreach (array('__' => 'return $text;', '_m' => 'return $text;', '_e' => 'echo $text;') as $fn => $body) {
    if (!function_exists($fn)) {
        eval('function ' . $fn . '($text, $domain = "") { ' . $body . ' }');
    }
}
foreach (array('hPlugins', 'hPreference', 'hCache', 'hLocale', 'hSanitize', 'hSettings', 'hAdminUi') as $helper) {
    require_once ABS_PATH . "oc-includes/osclass/helpers/$helper.php";
}
if (is_file(ABS_PATH . 'oc-admin/themes/modern/parts/ui.php')) {
    require_once ABS_PATH . 'oc-admin/themes/modern/parts/ui.php';
}

$root = dirname(__DIR__);
require_once $root . '/inc/text-settings.php';
require_once $root . '/inc/place-field.php';
function storefront_has_menus()
{
    return false;
}

if ((int) $admin->query('SELECT COUNT(*) FROM ' . DB_TABLE_PREFIX . "t_locale WHERE pk_c_code = 'en_US'")->fetch_row()[0] === 0) {
    seed_locale($admin);
}
seed_locale($admin, 'hi_IN', 'Hindi');
Preference::getInstance()->replace('language', 'en_US', 'osclass', 'STRING');
osc_settings_locales(true);

$stored = static function (string $key) use ($admin): ?string {
    $row = $admin->query('SELECT s_value FROM ' . DB_TABLE_PREFIX . "t_preference WHERE s_section = 'storefront' AND s_name = '" . $admin->real_escape_string($key) . "'")->fetch_row();

    return $row === null ? null : (string) $row[0];
};
$as = static function (string $locale, callable $read) {
    $_COOKIE['oc_userLocale'] = $locale;
    $value = $read();
    unset($_COOKIE['oc_userLocale']);

    return $value;
};
/** The settings form as the admin sees it: each translated textarea's text, by posted name. */
$form = static function (array $keys = array('hero_tagline', 'footer_message'), string $page = 'settings') use ($root): array {
    ob_start();
    require $root . '/admin/' . $page . '.php';
    $html  = (string) ob_get_clean();
    $names = '(?:' . implode('|', $keys) . ')[a-z]{2}_[A-Z]{2}';
    preg_match_all('#<textarea[^>]*name="(' . $names . ')"[^>]*>(.*?)</textarea>#s', $html, $m, PREG_SET_ORDER);
    $out = array();
    foreach ($m as $hit) {
        $out[$hit[1]] = html_entity_decode($hit[2], ENT_QUOTES);
    }
    preg_match_all('#<input(?=[^>]*name="(' . $names . ')")(?=[^>]*value="([^"]*)")[^>]*>#', $html, $m, PREG_SET_ORDER);
    foreach ($m as $hit) {
        $out[$hit[1]] = html_entity_decode($hit[2], ENT_QUOTES);
    }
    ksort($out);

    return $out;
};
$set = static function (array $values): void {
    foreach ($values as $key => $value) {
        osc_set_preference($key, $value, 'storefront');
    }
    osc_reset_preferences();
};

harness_section('an install the update has not moved yet');

$set(array('hero_tagline' => 'Old tagline', 'footer_message' => 'Old footer'));
pin('every language shows the old value', array(
    'footer_messageen_US' => 'Old footer', 'footer_messagehi_IN' => 'Old footer',
    'hero_taglineen_US' => 'Old tagline', 'hero_taglinehi_IN' => 'Old tagline',
), $form());

$_POST = $form();
$_POST['hero_taglinehi_IN'] = 'Naya tagline';
$_GET = array();
Params::init();
storefront_save_text('hero_tagline');
storefront_save_text('footer_message');
pin('saving the form keeps the old text in each language', array('Old tagline', 'Naya tagline', 'Old footer', 'Old footer'), array(
    $stored('hero_taglineen_US'), $stored('hero_taglinehi_IN'), $stored('footer_messageen_US'), $stored('footer_messagehi_IN'),
));
pin('...and drops the old single values', array(null, null), array($stored('hero_tagline'), $stored('footer_message')));
osc_reset_preferences();
pin('a Hindi visitor reads the Hindi tagline', 'Naya tagline', $as('hi_IN', static fn () => storefront_text_setting('hero_tagline', 'x')));
pin('an English visitor reads the English one', 'Old tagline', $as('en_US', static fn () => storefront_text_setting('hero_tagline', 'x')));

harness_section('a language with no text of its own');

$set(array('hero_taglinehi_IN' => ''));
pin('its box shows the default language\'s text', 'Old tagline', $form()['hero_taglinehi_IN'] ?? null);
pin('...which its visitors read', 'Old tagline', $as('hi_IN', static fn () => storefront_text_setting('hero_tagline', 'x')));
// Set empty, not deleted: a key deleted in this request stays in core's in-memory copy.
$set(array('hero_taglineen_US' => '', 'hero_tagline' => ''));
pin('nothing set anywhere gives the theme default', 'x', $as('hi_IN', static fn () => storefront_text_setting('hero_tagline', 'x')));

harness_section('the update');

$set(array('footer_messageen_US' => '', 'footer_messagehi_IN' => 'Hindi footer', 'footer_message' => 'Moved footer'));
storefront_migrate_text_settings();
pin('the old value moves to each empty language, a set one is kept, the old key goes', array('Moved footer', 'Hindi footer', null), array(
    $stored('footer_messageen_US'), $stored('footer_messagehi_IN'), $stored('footer_message'),
));
storefront_migrate_text_settings();
pin('run again it changes nothing', array('Moved footer', 'Hindi footer'), array($stored('footer_messageen_US'), $stored('footer_messagehi_IN')));

harness_section('the headline, search placeholder and site name');

$set(array('hero_headline' => 'Old headline', 'keyword_placeholder' => 'Old placeholder', 'header_logo_text' => 'Old name'));
pin('the settings form shows the old headline and placeholder in every language', array(
    'hero_headlineen_US' => 'Old headline', 'hero_headlinehi_IN' => 'Old headline',
    'keyword_placeholderen_US' => 'Old placeholder', 'keyword_placeholderhi_IN' => 'Old placeholder',
), $form(array('hero_headline', 'keyword_placeholder')));
pin('the brand form shows the old site name in every language', array(
    'header_logo_texten_US' => 'Old name', 'header_logo_texthi_IN' => 'Old name',
), $form(array('header_logo_text'), 'header'));

$_POST = array('hero_headlineen_US' => 'Find it', 'hero_headlinehi_IN' => 'Khojiye', 'header_logo_texten_US' => 'Bazaar', 'header_logo_texthi_IN' => '');
Params::init();
storefront_save_text('hero_headline');
storefront_save_text('header_logo_text');
osc_reset_preferences();
pin('a Hindi visitor reads the Hindi headline', 'Khojiye', $as('hi_IN', static fn () => storefront_text_setting('hero_headline', 'x')));
pin('...and the default language\'s site name where Hindi has none', 'Bazaar', $as('hi_IN', static fn () => storefront_text_setting('header_logo_text', 'x')));

storefront_migrate_text_settings();
pin('the update moves the old placeholder to each language', array('Old placeholder', 'Old placeholder', null), array(
    $stored('keyword_placeholderen_US'), $stored('keyword_placeholderhi_IN'), $stored('keyword_placeholder'),
));

$code = harness_result();
ob_end_flush();
exit($code);
