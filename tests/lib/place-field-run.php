<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Runs inc/place-field.php and item-post.php on stub core functions and prints what they did as JSON.
 * Usage: php tests/lib/place-field-run.php old|phase1|new
 *   old: a core without the place field; phase1: osc_place_field() only; new: it and osc_item_place_style().
 */

$mode  = $argv[1] ?? 'new';
$root  = dirname(__DIR__, 2);
$prefs = [];
$calls = [];
$writes = [];

function osc_get_preference($key, $section = 'osclass')
{
    return $GLOBALS['prefs'][$section . '/' . $key] ?? '';
}
function osc_set_preference($key, $value = '', $section = 'osclass')
{
    $GLOBALS['writes'][] = $section . '/' . $key . '=' . $value;
    $GLOBALS['prefs'][$section . '/' . $key] = $value;
}
function storefront_setting($key, $default = '')
{
    return osc_get_preference($key, 'storefront') ?: $default;
}
if ($mode !== 'old') {
    function osc_place_field($opts = [])
    {
        $GLOBALS['calls'][] = ['osc_place_field', $opts];
        echo '[place-field]';
    }
}
if ($mode === 'new') {
    function osc_item_place_style()
    {
        return osc_get_preference('item_place_style') ?: 'single';
    }
}

final class ItemForm
{
    public static function __callStatic($name, $args)
    {
        $GLOBALS['calls'][] = ['ItemForm::' . $name];

        return $name === 'locale_field_id' ? 'description' : null;
    }
}
final class Params
{
    public static function getParam($key)
    {
        return '';
    }
}
final class View
{
    public static function getInstance()
    {
        return new self();
    }
    public function _exists($key)
    {
        return false;
    }
}

$blank = ['osc_base_url', 'osc_captcha_enabled', 'osc_current_web_theme_path', 'osc_enqueue_script', 'osc_enqueue_style',
    'osc_images_enabled_at_items', 'osc_is_web_user_logged_in', 'osc_item_contact_name_required', 'osc_item_country_code',
    'osc_item_description', 'osc_item_id', 'osc_item_post_submit_label', 'osc_item_region_id', 'osc_item_secret', 'osc_item_title',
    'osc_price_enabled_at_items', 'osc_recaptcha_items_enabled', 'osc_show_captcha', 'osc_user_field', 'osc_item', 'osc_user',
    'storefront_enqueue_search', 'storefront_listing_mark', 'storefront_post_intro', 'storefront_required_mark'];
foreach ($blank as $fn) {
    eval('function ' . $fn . '() { return ""; }');
}
foreach (['osc_get_countries', 'osc_get_regions', 'osc_get_cities'] as $fn) {
    eval('function ' . $fn . '() { return []; }');
}
function osc_current_user_locale()
{
    return 'en_US';
}
function osc_esc_html($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES);
}
function __($s, $domain = '')
{
    return $s;
}
function _e($s, $domain = '')
{
    echo $s;
}

require $root . '/inc/place-field.php';

$out = ['map' => []];
// Each case: the theme's choice, the core setting already saved, and how many times init runs.
foreach (['dropdown' => ['dropdown', ''], 'autocomplete' => ['autocomplete', ''], 'saved' => ['dropdown', 'single']] as $case => [$theme, $core]) {
    $prefs  = ['storefront/location_input_type' => $theme, 'osclass/item_place_style' => $core];
    $writes = [];
    storefront_map_place_style();
    storefront_map_place_style();
    $out['map'][$case] = ['style' => $prefs['osclass/item_place_style'], 'writes' => $writes];
}

$out['post'] = [];
foreach (['autocomplete', 'dropdown'] as $theme) {
    foreach (['single', 'fields'] as $core) {
        $prefs = ['storefront/location_input_type' => $theme, 'osclass/item_place_style' => $core];
        $calls = [];
        ob_start();
        include $root . '/item-post.php';
        ob_end_clean();
        $out['post'][$theme . '/' . $core] = $calls;
    }
}

echo json_encode($out);
