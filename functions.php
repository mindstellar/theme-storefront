<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Mindstellar Community
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

define('STOREFRONT_THEME_VERSION', '1.0.1');
define('STOREFRONT_THEME_FOLDER', __DIR__);

if (!OC_ADMIN) {
    // Vanilla, jQuery-free assets. Defer every script — all of the theme's behaviour
    // waits for the DOM, so non-blocking `defer` is safe and lets the parser run
    // uninterrupted. The stylesheet + core chrome load on every page (header hook);
    // each view enqueues its own feature bundle where the markup lives (below), so a
    // page never ships JS it cannot use.
    osc_add_filter('scripts_defer', static function () { return true; });
    osc_add_hook('header', 'storefront_enqueue_base', 5);
} else {
    // The SVG sanitizer is admin-only (it's only ever run against an uploaded logo),
    // but must be in place before storefront_admin_actions() below can run.
    require_once STOREFRONT_THEME_FOLDER . '/admin/StorefrontSvg.php';
}

/**
 * Resolve a theme asset URL, preferring the built + minified sibling when it exists.
 * Releases ship the storefront*.min.css/js built by `npm run build`; local dev with
 * no build simply serves the readable source. `osc_asset_url_versioned` cache-busts
 * on the served file's mtime.
 *
 * @param string $file theme-root-relative path, e.g. 'css/storefront.css'
 *
 * @return string
 */
if (!function_exists('storefront_asset')) {
    function storefront_asset($file)
    {
        // Note: osc_current_web_theme_path() *includes* a file, it does not return a
        // path — so resolve existence against the theme folder directly.
        $min = preg_replace('/\.(css|js)$/', '.min.$1', $file);
        if (file_exists(STOREFRONT_THEME_FOLDER . '/' . $min)) {
            $file = $min;
        }

        return osc_asset_url_versioned(osc_current_web_theme_url($file));
    }
}

/**
 * Every page: the design system stylesheet + core chrome (header nav, account menus,
 * dialogs, confirm guards, flash dismiss). Runs on the `header` hook at priority 5,
 * before core prints the queue at 8.
 */
if (!function_exists('storefront_enqueue_base')) {
    function storefront_enqueue_base()
    {
        osc_enqueue_style('storefront', storefront_asset('css/storefront.css'));
        osc_register_script('storefront-core', storefront_asset('js/storefront-core.js'));
        osc_enqueue_script('storefront-core');
    }
}

/**
 * Enqueue one of the theme's page bundles (js/storefront-<name>.js). Views call this
 * at their top, before the header include, so the script prints in <head>. Page
 * detection via osc_is_*() is unreliable at header time for some views (the item
 * page resolves to an "error" location before its controller data is ready), so the
 * view itself — the definitive signal that a page is rendering — declares its needs.
 *
 * @param string            $name bundle short name: 'item' | 'search' | 'account'
 * @param string|array|null $deps registered script id(s) this bundle needs first
 */
if (!function_exists('storefront_enqueue_bundle')) {
    function storefront_enqueue_bundle($name, $deps = null)
    {
        $id = 'storefront-' . $name;
        osc_register_script($id, storefront_asset('js/' . $id . '.js'), $deps);
        osc_enqueue_script($id);
    }
}

/**
 * Search-surface + location-autocomplete bundle — used by the search results page,
 * the home hero, and the item post/edit form. Depends on core's shared combobox
 * (osc-ui-common → oscAutocomplete), which core otherwise loads only on the item
 * form; the dependency pulls it in and orders it first on these pages.
 */
if (!function_exists('storefront_enqueue_search')) {
    function storefront_enqueue_search()
    {
        storefront_enqueue_bundle('search', 'osc-ui-common');
    }
}

/**
 * First-run defaults.
 */
if (!function_exists('storefront_theme_install')) {
    function storefront_theme_install()
    {
        osc_set_preference('version', STOREFRONT_THEME_VERSION, 'storefront');
        osc_set_preference('footer_link', '1', 'storefront');
        osc_set_preference('defaultShowAs@all', 'gallery', 'storefront');
        osc_set_preference('defaultShowAs@search', 'gallery');

        // Settings-backend defaults, added later. Guarded (unlike the seeds above) so
        // re-running install — an upgrade path can call this — never clobbers a value
        // an operator already set. `header_logo_text` and `footer_message` are left
        // unseeded: '' is already their fallback-triggering default.
        if (!osc_get_preference('palette', 'storefront')) {
            osc_set_preference('palette', 'teal', 'storefront');
        }
        if (!osc_get_preference('color_scheme', 'storefront')) {
            osc_set_preference('color_scheme', 'light', 'storefront');
        }
        if (!osc_get_preference('hero_show_decor', 'storefront')) {
            osc_set_preference('hero_show_decor', '1', 'storefront');
        }
        if (!osc_get_preference('hero_show_chips', 'storefront')) {
            osc_set_preference('hero_show_chips', '1', 'storefront');
        }
        if (!osc_get_preference('location_input_type', 'storefront')) {
            osc_set_preference('location_input_type', 'autocomplete', 'storefront');
        }
        if (!osc_get_preference('search_location_type', 'storefront')) {
            osc_set_preference('search_location_type', 'autocomplete', 'storefront');
        }
        if (!osc_get_preference('home_show_featured', 'storefront')) {
            osc_set_preference('home_show_featured', '1', 'storefront');
        }

        osc_reset_preferences();
    }
}

/**
 * Idempotent upgrade path from a stored version to the current one.
 */
if (!function_exists('storefront_theme_update')) {
    function storefront_theme_update($current_version)
    {
        if ($current_version === 0 || $current_version === '') {
            storefront_theme_install();
        }
        osc_set_preference('version', STOREFRONT_THEME_VERSION, 'storefront');
        osc_reset_preferences();
    }
}

if (!function_exists('storefront_check_install')) {
    function storefront_check_install()
    {
        $current_version = osc_get_preference('version', 'storefront');
        if ($current_version === '') {
            storefront_theme_update(0);
        } elseif (version_compare((string) $current_version, STOREFRONT_THEME_VERSION, '<')) {
            storefront_theme_update($current_version);
        }
    }
}

/**
 * Read a theme preference with a real fallback: '' or false — an unset preference,
 * or one explicitly cleared — falls back to $default; a stored '0' is a genuine value
 * and passes straight through (a checkbox toggled off is not "unset").
 */
if (!function_exists('storefront_setting')) {
    function storefront_setting($key, $default = '')
    {
        $value = osc_get_preference($key, 'storefront');
        if ($value === '' || $value === false) {
            return $default;
        }

        return $value;
    }
}

/**
 * Site brand mark. Each of the two logo slots (primary, compact) independently holds
 * EITHER a sanitized inline SVG or an uploaded raster filename — never both, an upload
 * of one clears the other — and each is rendered as: the SVG markup if set (it was
 * already run through StorefrontSvg::clean() at save time, so it is safe to print
 * as-is here); else the raster <img> if set; else, for the compact slot only, the
 * primary mark (there is no compact-specific asset to fall back to first). With no
 * logo at all, the site name renders as a plain wordmark.
 *
 * Default call emits BOTH marks side by side, wrapped so the stylesheet can swap
 * between them at a breakpoint (`.sf-logo__mark--compact` hides above it, `--full`
 * hides below). Pass $compact = true from a context that only ever has room for one
 * mark (no responsive swap wanted) to get just the compact-preferring mark back.
 *
 * @param bool $compact when true, return a single compact-preferring mark instead of
 *                       the full+compact pair
 *
 * @return string
 */
if (!function_exists('storefront_logo')) {
    function storefront_logo($compact = false)
    {
        $siteName = storefront_setting('header_logo_text', osc_page_title());
        $wordmark = '<span class="sf-wordmark">' . osc_esc_html($siteName) . '</span>';

        $resolveMark = static function ($svgKey, $rasterKey) use ($siteName) {
            $svg = osc_get_preference($svgKey, 'storefront');
            if ($svg !== '' && $svg !== false) {
                return $svg; // sanitized on save — printed inline as-is
            }
            $raster = osc_get_preference($rasterKey, 'storefront');
            if ($raster !== '' && $raster !== false) {
                $src = osc_base_url() . 'oc-content/uploads/' . $raster;

                return '<img src="' . osc_esc_html($src) . '" alt="' . osc_esc_html($siteName) . '">';
            }

            return null;
        };

        $full        = $resolveMark('brand_logo_svg', 'logo');
        $compactMark = $resolveMark('brand_logo_svg_compact', 'logo_compact');

        if ($full === null && $compactMark === null) {
            return $wordmark;
        }

        if ($full === null) { $full = $wordmark; } // only a compact asset was set
        if ($compact) {
            return $compactMark !== null ? $compactMark : $full;
        }

        $compactContent = $compactMark !== null ? $compactMark : $full;

        return '<span class="sf-logo__mark sf-logo__mark--full">' . $full . '</span>'
            . '<span class="sf-logo__mark sf-logo__mark--compact">' . $compactContent . '</span>';
    }
}

/**
 * Top-level category <select> for the search forms. Rendered here rather than through
 * core's osc_categories_select(), whose CategoryForm helper emits a blank <option>
 * after every category — they show up as empty rows in the dropdown. Only parent
 * categories are listed.
 *
 * @param string     $name          field name, e.g. 'sCategory'
 * @param string|int $selected      currently selected category id, or '' for none
 * @param string     $default_label label for the leading "any category" option
 */
if (!function_exists('storefront_category_select')) {
    function storefront_category_select($name, $selected, $default_label)
    {
        $selected = (string) $selected;
        echo '<select name="' . osc_esc_html($name) . '" id="' . osc_esc_html($name) . '">';
        echo '<option value="">' . osc_esc_html($default_label) . '</option>';
        osc_goto_first_category();
        while (osc_has_categories()) {
            $id = (string) osc_category_id();
            echo '<option value="' . osc_esc_html($id) . '"'
                . ($selected !== '' && $selected === $id ? ' selected' : '') . '>'
                . osc_esc_html(osc_category_name()) . '</option>';
        }
        echo '</select>';
    }
}

/**
 * Emit the <body> class attribute. Each view passes its own page class.
 */
if (!function_exists('storefront_body_class')) {
    function storefront_body_class($extra = '')
    {
        $classes = ['sf'];
        if (osc_is_home_page())   { $classes[] = 'sf-home'; }
        if (osc_is_search_page()) {
            $classes[] = 'sf-search';
            // List vs. grid view drives the card layout in css/storefront.css.
            if (osc_search_show_as() === 'list') { $classes[] = 'sf-list'; }
        }
        if (osc_is_static_page()) { $classes[] = 'sf-page'; }
        if ($extra !== '')        { $classes[] = $extra; }

        echo 'class="' . osc_esc_html(implode(' ', $classes)) . '"';
    }
}

/**
 * A short plain-text teaser from a listing's description, for the list-view card.
 * The description is core-formatted HTML; here it is deliberately stripped and
 * collapsed to a single run of words, then clamped, because a card teaser is text,
 * not markup. Returns '' when there is nothing to show. Multibyte-safe.
 */
if (!function_exists('storefront_excerpt')) {
    function storefront_excerpt($html, $len = 180)
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)));
        if ($text === '') { return ''; }
        if (function_exists('mb_strlen')) {
            if (mb_strlen($text) <= $len) { return $text; }
            return rtrim(mb_substr($text, 0, $len)) . '…';
        }
        if (strlen($text) <= $len) { return $text; }

        return rtrim(substr($text, 0, $len)) . '…';
    }
}

/**
 * Related listings: other live ads in the current item's category. Returns an
 * array of item rows (Search already filters to enabled + active items).
 */
if (!function_exists('storefront_related_items')) {
    function storefront_related_items($max = 4)
    {
        $max = max(1, (int) $max);
        $currentId = (int) osc_item_id();
        $items = array();
        $seen  = array($currentId => true);

        // Append a Search's live results (minus the current item and anything already
        // collected) until $max is reached.
        $collect = static function ($search) use (&$items, &$seen, $max, $currentId) {
            if (count($items) >= $max) { return; }
            $search->addConditions(DB_TABLE_PREFIX . 't_item.pk_i_id <> ' . $currentId);
            $search->limit(0, $max * 3);
            foreach ((array) $search->doSearch() as $it) {
                if (count($items) >= $max) { break; }
                $id = (int) $it['pk_i_id'];
                if (isset($seen[$id])) { continue; }
                $items[] = $it;
                $seen[$id] = true;
            }
        };

        $catId = (int) osc_item_category_id();

        // 1) Same category — the genuinely related set.
        if ($catId > 0) {
            $s = Search::newInstance();
            $s->addCategory($catId);
            $collect($s);
        }
        // 2) Backfill from the parent category — addCategory() pulls in the whole subtree
        //    (the sibling categories), so a thin category doesn't collapse the section
        //    into dead whitespace beside the taller rail on desktop.
        if (count($items) < $max && $catId > 0) {
            $cat = Category::newInstance()->findByPrimaryKey($catId);
            $parentId = isset($cat['fk_i_parent_id']) ? (int) $cat['fk_i_parent_id'] : 0;
            if ($parentId > 0) {
                $s = Search::newInstance();
                $s->addCategory($parentId);
                $s->order('dt_pub_date', 'DESC');
                $collect($s);
            }
        }
        // 3) Last resort — the most recent live listings anywhere, so the row only ever
        //    disappears on a near-empty site with genuinely nothing else to show.
        if (count($items) < $max) {
            $s = Search::newInstance();
            $s->order('dt_pub_date', 'DESC');
            $collect($s);
        }

        return $items;
    }
}

/**
 * Price display state for the detail page: 'price' (a real amount), 'free' (a
 * zero price) or 'ask' (price-on-ask, or prices disabled). Lets the template
 * show "Check with seller" without rendering it in the money colour, and keeps
 * the detail page's price wording in step with the listing card.
 */
if (!function_exists('storefront_price_state')) {
    function storefront_price_state()
    {
        if (!osc_price_enabled_at_items()) { return 'ask'; }
        $price = osc_item_price();
        if ($price === null || $price === '') { return 'ask'; }
        if ((float) $price === 0.0) { return 'free'; }

        return 'price';
    }
}

/**
 * Build a human place line from location tiers: drop empties, and drop any tier that
 * repeats the one before it (case-insensitively) — so a city inside a same-named
 * region reads "Delhi, India", not "Delhi, Delhi, India". Ordered narrow → wide.
 *
 * @param string[] $parts e.g. [city area, city, region, country]
 *
 * @return string comma-joined location line
 */
if (!function_exists('storefront_location_line')) {
    function storefront_location_line(array $parts)
    {
        $out = array();
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') { continue; }
            $prev = end($out);
            if ($prev !== false && mb_strtolower($part) === mb_strtolower($prev)) { continue; }
            $out[] = $part;
        }

        return implode(', ', $out);
    }
}

/**
 * Inline icon set (feather-style, one consistent stroke). Drawn SVG, never glyphs.
 * A theme-global function so both item.php and the partials it pulls in through
 * osc_current_web_theme_path() — which requires them in their own scope, out of
 * reach of a page-local closure — can render the same icons.
 */
if (!function_exists('storefront_icon')) {
    function storefront_icon($name, $size = 16)
    {
        $p = array(
            'share'         => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.6" y1="13.5" x2="15.4" y2="17.5"/><line x1="15.4" y1="6.5" x2="8.6" y2="10.5"/>',
            'flag'          => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/>',
            'mail'          => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
            'phone'         => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
            'map-pin'       => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'edit'          => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
            'trash'         => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
            'info'          => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'x'             => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
            'download'      => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
            'chevron-left'  => '<polyline points="15 18 9 12 15 6"/>',
            'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
            'chevron-down'  => '<polyline points="6 9 12 15 18 9"/>',
            'log-out'       => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
            'image'         => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
            'alert'         => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'copy'          => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
            'check'         => '<polyline points="20 6 9 17 4 12"/>',
            'clock'         => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
            'star'          => '<polygon points="12 2 15.1 8.6 22 9.3 17 14.1 18.2 21 12 17.6 5.8 21 7 14.1 2 9.3 8.9 8.6 12 2"/>',
            'lock'          => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
            'ban'           => '<circle cx="12" cy="12" r="10"/><line x1="4.9" y1="4.9" x2="19.1" y2="19.1"/>',
            'settings'      => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
            'search'        => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
            'sliders'       => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
            'layout'        => '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>',
            'list'          => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
            'bell'          => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
            'user'          => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'globe'         => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
            'plus'          => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
            'eye'           => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
            'message-circle' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
            'external-link' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
            'rss'           => '<path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/>',
        );
        $d = isset($p[$name]) ? $p[$name] : '';

        return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
    }
}

/**
 * The logged-in user's own row, fetched once per request.
 *
 * The account chrome (identity band + tabs) shows on every account page, but
 * osc_user_field()/osc_user_is_company()/osc_user_regdate() read the `user` view
 * variable, and core exports that only on the profile and alerts routes. On the
 * dashboard and the listings page it is unset, so those helpers silently report
 * "Individual" with no location or join date. Read the row straight off the model
 * keyed by the session user id instead, so identity is consistent wherever the
 * band renders. Returns an array, or null for a guest.
 */
if (!function_exists('sf_account_user')) {
    function sf_account_user()
    {
        static $row = false;
        if ($row !== false) { return $row; }

        $id = osc_logged_user_id();
        $row = $id ? User::newInstance()->findByPrimaryKey($id) : null;
        if (!is_array($row)) { $row = null; }

        return $row;
    }
}

/**
 * Whether the avatar-upload UI should be offered.
 *
 * Two gates: the core build must actually ship the avatar helpers (older cores
 * don't, and the theme bundles into whatever core it lands on), and the operator
 * must have the feature switched on. Existing avatars still *display* wherever
 * osc_has_user_avatar() is true even if uploads are switched off — this only
 * governs the editing affordance.
 */
if (!function_exists('sf_avatars_enabled')) {
    function sf_avatars_enabled()
    {
        return function_exists('osc_user_avatar_url')
            && function_exists('osc_get_preference')
            && osc_get_preference('enabled_user_avatars');
    }
}

/**
 * True when this user has an uploaded avatar, guarded for cores without the API.
 */
if (!function_exists('sf_has_avatar')) {
    function sf_has_avatar($userId = null)
    {
        return function_exists('osc_has_user_avatar') && osc_has_user_avatar($userId);
    }
}

/**
 * The uppercase monogram fallback for a user with no photo — first letter of the
 * display name, multibyte-safe. Shared by the identity band and the editor.
 */
if (!function_exists('sf_user_monogram')) {
    function sf_user_monogram($name = null)
    {
        if ($name === null) { $name = osc_logged_user_name(); }

        return $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') : '?';
    }
}

/**
 * True only on installs that actually span more than one country. A country
 * <select> with a single option is a control you cannot operate, so the search
 * rail renders it only when the choice is real and passes the code through as a
 * hidden field otherwise. Answered once per request.
 */
if (!function_exists('storefront_search_multi_country')) {
    function storefront_search_multi_country()
    {
        static $multi = null;
        if ($multi === null) {
            $multi = count((array) Country::newInstance()->listAll()) > 1;
        }

        return $multi;
    }
}

/**
 * The category the current search is scoped to, as array{id:int, name:string}.
 * osc_search_category_id() returns an array (a search may span several); the
 * refinement tree descends from ONE parent, so the first id is authoritative.
 * Name is '' when browsing all categories.
 */
if (!function_exists('storefront_search_category')) {
    function storefront_search_category()
    {
        $ids = (array) osc_search_category_id();
        if (empty($ids)) { return array('id' => 0, 'name' => ''); }
        $id  = (int) reset($ids);
        $cat = $id ? Category::newInstance()->findByPrimaryKey($id) : array();

        return array('id' => $id, 'name' => !empty($cat['s_name']) ? $cat['s_name'] : '');
    }
}

/**
 * Every filter narrowing the current results, each paired with the URL that
 * removes just that one — rendered as chips on the results head so the applied
 * state is stated on the page. It otherwise lived only in the rail's filled-in
 * controls, invisible the moment the mobile drawer closed. Removal is a URL, not
 * a form submit, so each chip stays bookmarkable and back-button safe;
 * osc_search_url() prunes the null out, and iPage is dropped alongside every
 * removal so widening never strands the user on a page that no longer exists.
 *
 * @return array list of array{label:string, url:string}
 */
if (!function_exists('storefront_active_filters')) {
    function storefront_active_filters()
    {
        $filters = array();
        $add = function ($label, array $remove) use (&$filters) {
            $remove['iPage'] = null;
            $filters[] = array('label' => $label, 'url' => osc_update_search_url($remove));
        };

        $pattern = trim((string) osc_search_pattern());
        if ($pattern !== '') { $add($pattern, array('sPattern' => null)); }

        $cat = storefront_search_category();
        if ($cat['name'] !== '') { $add($cat['name'], array('sCategory' => null)); }

        $city = trim((string) osc_search_city());
        if ($city !== '') { $add($city, array('sCity' => null, 'cityId' => null)); }

        $region = trim((string) osc_search_region());
        if ($region !== '') { $add($region, array('sRegion' => null, 'regionId' => null)); }

        // One chip for the price: min and max are two inputs but a single decision.
        // A zero bound narrows nothing, so it is not treated as an applied filter.
        $min = trim((string) osc_search_price_min());
        $max = trim((string) osc_search_price_max());
        if (is_numeric($min) && (float) $min == 0.0) { $min = ''; }
        if (is_numeric($max) && (float) $max == 0.0) { $max = ''; }
        if ($min !== '' || $max !== '') {
            if ($min !== '' && $max !== '') { $label = $min . ' – ' . $max; }
            elseif ($min !== '')            { $label = sprintf(__('Over %s', 'storefront'), $min); }
            else                            { $label = sprintf(__('Under %s', 'storefront'), $max); }
            $add($label, array('sPriceMin' => null, 'sPriceMax' => null));
        }

        if (osc_search_has_pic()) { $add(__('With photos', 'storefront'), array('bPic' => null)); }

        return $filters;
    }
}

/**
 * Category-refinement rows for the search rail: a way back up, the current
 * category as a heading, then its enabled subcategories as links. Rendered as
 * <a href> navigation, not form controls, so every level is bookmarkable,
 * shareable and openable in a new tab (a category IS a URL). One cached round
 * trip per parent via the object cache; a cached empty list is not re-fetched
 * (osc_cache_get returns false only on a real miss). Echoes <li> rows into the
 * caller's <ul>. Names come through the model's locale-joined listWhere, so no
 * separate name lookup is needed.
 */
if (!function_exists('storefront_subcategories')) {
    function storefront_subcategories()
    {
        $cat      = storefront_search_category();
        $parentId = (int) $cat['id'];

        $key   = 'storefront_subcats_' . $parentId;
        $found = false;
        $subs  = osc_cache_get($key, $found);
        if ($found === false) {
            $subs = $parentId
                ? Category::newInstance()->findSubcategoriesEnabled($parentId)
                : Category::newInstance()->listWhere('a.fk_i_parent_id IS NULL AND a.b_enabled = 1');
            osc_cache_set($key, $subs, 680);
        }

        // Drilled into a category: offer the way out and say where you are.
        if ($parentId) { ?>
            <li class="sf-cat-reset">
                <a href="<?php echo osc_esc_html(osc_update_search_url(array('sCategory' => null, 'iPage' => null))); ?>">
                    <?php echo storefront_icon('chevron-left', 14); ?>
                    <?php _e('All categories', 'storefront'); ?>
                </a>
            </li>
            <?php if ($cat['name'] !== '') { ?>
                <li class="sf-cat-current" aria-current="true"><?php echo osc_esc_html($cat['name']); ?></li>
            <?php }
        }

        if (empty($subs)) {
            // A leaf category. An empty panel reads as broken, so say the true thing.
            if ($parentId) {
                printf('<li class="sf-cat-empty">%s</li>', osc_esc_html(__('No subcategories', 'storefront')));
            }

            return;
        }

        foreach ($subs as $sub) {
            $sid = (int) $sub['pk_i_id'];
            ?>
            <li>
                <a class="sf-cat-link" href="<?php echo osc_esc_html(osc_update_search_url(array('sCategory' => $sid, 'iPage' => null))); ?>">
                    <span class="sf-cat-name"><?php echo osc_esc_html($sub['s_name']); ?></span>
                    <?php echo storefront_icon('chevron-right', 14); ?>
                </a>
            </li>
            <?php
        }
    }
}

/**
 * URL of the adjacent live listing by id order — 'prev' is the next-older listing
 * (lower id), 'next' the next-newer (higher id). One indexed round trip, filtered
 * to live listings only (enabled + active + not spam), so prev/next never point at
 * a hidden listing. Returns '' when there is no neighbour, so the control renders
 * only when it leads somewhere.
 */
if (!function_exists('storefront_adjacent_item_url')) {
    function storefront_adjacent_item_url($dir = 'next')
    {
        $itemId = (int) osc_item_id();
        if ($itemId === 0) { return ''; }

        $isNext = ($dir !== 'prev');
        $key    = 'storefront_adjacent_' . ($isNext ? 'next_' : 'prev_') . $itemId;

        // A real miss leaves $found false; a cached "no neighbour" is stored as ''
        // and comes back with $found true — the newest/oldest listing is often a hot
        // page, so the empty result is worth caching too rather than re-querying it
        // on every hit. osc_cache_get() requires the by-reference $found out-param.
        $found = false;
        $url   = osc_cache_get($key, $found);
        if ($found === false) {
            // $itemId is int-cast; it is the only interpolated value.
            $sql = sprintf(
                'SELECT pk_i_id FROM %st_item WHERE pk_i_id %s %d'
                . ' AND b_spam = 0 AND b_enabled = 1 AND b_active = 1'
                . ' ORDER BY pk_i_id %s LIMIT 1',
                DB_TABLE_PREFIX,
                $isNext ? '>' : '<',
                $itemId,
                $isNext ? 'ASC' : 'DESC'
            );

            $dao    = new DAO();
            $rs     = $dao->dao->query($sql);
            $rows   = $rs ? $rs->result() : array();
            $url    = '';
            if (!empty($rows)) {
                $row = Item::newInstance()->findByPrimaryKey((int) $rows[0]['pk_i_id']);
                if (!empty($row)) { $url = osc_item_url_from_item($row); }
            }
            osc_cache_set($key, $url, 180);
        }

        return $url;
    }
}

/**
 * Front-of-site moderation endpoint for the admin bar on the listing page. Every
 * request is gated (admin only), CSRF-checked (osc_csrf_check fails closed by
 * redirecting), and the action is whitelisted before it selects a branch — the
 * param never reaches a method name, query or path. Moderation goes through
 * ItemActions so core's search index stays in step.
 */
if (!function_exists('storefront_admin_item_action')) {
    function storefront_admin_item_action()
    {
        if (Params::getParam('route') !== 'storefront-item-admin') { return; }

        if (!osc_is_admin_user_logged_in()) {
            osc_add_flash_error_message(__('You are not allowed to do that.', 'storefront'));
            osc_redirect_to(osc_base_url(true));
        }

        // Fails closed: on a bad/missing token this redirects with an error instead
        // of returning. Core auto-injects the token into any form not marked nocsrf.
        osc_csrf_check();

        $action  = (string) Params::getParam('admin_action');
        $allowed = array('block', 'unblock', 'deactivate', 'activate', 'spam', 'unspam', 'premium', 'unpremium');
        if (!in_array($action, $allowed, true)) {
            osc_add_flash_error_message(__('Unknown action.', 'storefront'));
            osc_redirect_to(osc_base_url(true));
        }

        $id  = (int) Params::getParam('id');
        $row = $id ? Item::newInstance()->findByPrimaryKey($id) : array();
        if (empty($row)) {
            osc_add_flash_error_message(__("This listing doesn't exist.", 'storefront'));
            osc_redirect_to(osc_base_url(true));
        }

        $mItem = new ItemActions(true); // admin context
        switch ($action) {
            case 'block':      $mItem->disable($id); break;
            case 'unblock':    $mItem->enable($id); break;
            case 'deactivate': $mItem->deactivate($id); break;
            case 'activate':   $mItem->activate($id); break;
            case 'spam':       $mItem->spam($id, true); break;
            case 'unspam':     $mItem->spam($id, false); break;
            case 'premium':    $mItem->premium($id, true); break;
            default:           $mItem->premium($id, false); break; // 'unpremium'
        }

        $messages = array(
            'block'      => __('Listing blocked — hidden from the site and search.', 'storefront'),
            'unblock'    => __('Listing unblocked.', 'storefront'),
            'deactivate' => __('Listing deactivated.', 'storefront'),
            'activate'   => __('Listing activated.', 'storefront'),
            'spam'       => __('Marked as spam.', 'storefront'),
            'unspam'     => __('No longer marked as spam.', 'storefront'),
            'premium'    => __('Listing is now featured.', 'storefront'),
            'unpremium'  => __('Listing is no longer featured.', 'storefront'),
        );
        osc_add_flash_ok_message($messages[$action], 'pubMessages');

        // osc_item_url() reads the exported view var for the redirect back.
        View::newInstance()->_exportVariableToView('item', $row);
        osc_redirect_to(osc_item_url());
    }
}
// A hook route: it acts then redirects, so it must reach the handler before any
// output is written.
osc_add_route_hook('storefront-item-admin', 'item/moderate/?$', 'item/moderate');
osc_add_hook('storefront-item-admin', 'storefront_admin_item_action');

/**
 * Unlink a logo slot's stored raster file, if any, from the uploads folder. Shared by
 * the upload/remove admin actions and the on-uninstall cleanup — none of them should
 * leave an orphaned file behind after the preference pointing at it is gone.
 */
if (!function_exists('storefront_logo_unlink_raster')) {
    function storefront_logo_unlink_raster($rasterKey)
    {
        $raster = osc_get_preference($rasterKey, 'storefront');
        if ($raster === '' || $raster === false) { return; }

        $path = osc_uploads_path() . $raster;
        if (file_exists($path)) { @unlink($path); }
    }
}

/**
 * Theme admin panel actions: brand text, logo upload/remove, and the settings form.
 * Hooked on `init_admin` (before any admin output), mirroring bender's single
 * dispatcher keyed off `action_specific`. These forms carry no CSRF token — same as
 * bender's — so none is added here.
 */
if (!function_exists('storefront_admin_actions')) {
    function storefront_admin_actions()
    {
        $adminUrl = static function ($page) {
            return osc_admin_render_theme_url('oc-content/themes/storefront/admin/' . $page . '.php');
        };

        switch (Params::getParam('action_specific')) {
            case 'storefront_brand':
                osc_set_preference('header_logo_text', Params::getParam('header_logo_text'), 'storefront');
                osc_reset_preferences();
                osc_add_flash_ok_message(__('Brand settings updated correctly.', 'storefront'), 'admin');
                osc_redirect_to($adminUrl('header'));
                break;

            case 'storefront_upload_logo':
                $slot      = Params::getParam('slot') === 'compact' ? 'compact' : 'primary';
                $suffix    = $slot === 'compact' ? '_compact' : '';
                $svgKey    = 'brand_logo_svg' . $suffix;
                $rasterKey = 'logo' . $suffix;

                $file = Params::getFiles('logo_file');
                if (empty($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
                    osc_add_flash_error_message(__('An error has occurred, please try again.', 'storefront'), 'admin');
                    osc_redirect_to($adminUrl('header'));
                    break;
                }

                $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

                if ($ext === 'svg') {
                    $raw   = @file_get_contents($file['tmp_name']);
                    $clean = $raw !== false ? StorefrontSvg::clean($raw) : '';
                    if ($clean === '') {
                        osc_add_flash_error_message(
                            __('That SVG could not be used — it may be malformed, unsafe, or over the size limit.', 'storefront'),
                            'admin'
                        );
                        osc_redirect_to($adminUrl('header'));
                        break;
                    }

                    storefront_logo_unlink_raster($rasterKey);
                    osc_delete_preference($rasterKey, 'storefront');
                    osc_set_preference($svgKey, $clean, 'storefront');
                    osc_reset_preferences();
                    osc_add_flash_ok_message(__('The logo has been uploaded correctly.', 'storefront'), 'admin');
                } elseif ($ext === 'jpg' || $ext === 'jpeg' || $ext === 'png') {
                    try {
                        $img      = ImageResizer::fromFile($file['tmp_name']);
                        $fileExt  = $img->getExt();
                        $filename = 'storefront_logo' . $suffix . '.' . $fileExt;
                        $img->saveToFile(osc_uploads_path() . $filename);
                    } catch (Exception $e) {
                        osc_add_flash_error_message(__('An error has occurred, please try again.', 'storefront'), 'admin');
                        osc_redirect_to($adminUrl('header'));
                        break;
                    }

                    storefront_logo_unlink_raster($rasterKey);
                    osc_delete_preference($svgKey, 'storefront');
                    osc_set_preference($rasterKey, $filename, 'storefront');
                    osc_reset_preferences();
                    osc_add_flash_ok_message(__('The logo has been uploaded correctly.', 'storefront'), 'admin');
                } else {
                    osc_add_flash_error_message(__('Unsupported file type — upload an SVG, JPG or PNG.', 'storefront'), 'admin');
                }
                osc_redirect_to($adminUrl('header'));
                break;

            case 'storefront_remove_logo':
                $slot      = Params::getParam('slot') === 'compact' ? 'compact' : 'primary';
                $suffix    = $slot === 'compact' ? '_compact' : '';
                $svgKey    = 'brand_logo_svg' . $suffix;
                $rasterKey = 'logo' . $suffix;

                storefront_logo_unlink_raster($rasterKey);
                osc_delete_preference($svgKey, 'storefront');
                osc_delete_preference($rasterKey, 'storefront');
                osc_reset_preferences();
                osc_add_flash_ok_message(__('The logo has been removed.', 'storefront'), 'admin');
                osc_redirect_to($adminUrl('header'));
                break;

            case 'storefront_settings':
                $set = static function ($key) {
                    osc_set_preference($key, Params::getParam($key), 'storefront');
                };
                $setCheckbox = static function ($key) {
                    osc_set_preference($key, Params::getParam($key) ? '1' : '0', 'storefront');
                };

                $set('palette');
                $set('color_scheme');
                $set('hero_headline');
                $set('hero_tagline');
                $setCheckbox('hero_show_decor');
                $setCheckbox('hero_show_chips');
                $setCheckbox('home_show_featured');
                $set('location_input_type');
                $set('search_location_type');
                $set('keyword_placeholder');
                osc_set_preference('defaultShowAs@all', Params::getParam('defaultShowAs@all'), 'storefront');
                osc_set_preference('defaultShowAs@search', Params::getParam('defaultShowAs@all'));
                $set('footer_message');
                $set('social_facebook');
                $set('social_twitter');
                $set('social_instagram');
                $set('social_youtube');
                $set('social_linkedin');
                $setCheckbox('footer_link');

                osc_reset_preferences();
                osc_add_flash_ok_message(__('Theme settings updated correctly.', 'storefront'), 'admin');
                osc_redirect_to($adminUrl('settings'));
                break;
        }
    }
}

/**
 * On theme uninstall: drop every stored preference plus any raster logo files —
 * nothing of the theme's own state should survive it being removed.
 */
if (!function_exists('storefront_theme_delete')) {
    function storefront_theme_delete()
    {
        storefront_logo_unlink_raster('logo');
        storefront_logo_unlink_raster('logo_compact');
        Preference::newInstance()->delete(array('s_section' => 'storefront'));
    }
}

osc_admin_menu_appearance(
    __('Storefront: Brand', 'storefront'),
    osc_admin_render_theme_url('oc-content/themes/storefront/admin/header.php'),
    'storefront_brand'
);
osc_admin_menu_appearance(
    __('Storefront: Settings', 'storefront'),
    osc_admin_render_theme_url('oc-content/themes/storefront/admin/settings.php'),
    'storefront_settings'
);
osc_add_hook('init_admin', 'storefront_admin_actions');
osc_add_hook('theme_delete_storefront', 'storefront_theme_delete');

// Run once per request, mirroring bender: register defaults / migrate on load.
storefront_check_install();
