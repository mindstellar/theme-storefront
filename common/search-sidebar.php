<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Partial: the search refinement rail. Rendered ONCE, into search.php. It is the
 * left column on desktop and an off-canvas drawer on narrow screens (one DOM
 * node, toggled by CSS + js/storefront.js — never cloned). The <aside> element
 * itself is the drawer, so it carries the layout + drawer classes and the id the
 * "Filters" button targets.
 *
 * CONTRACT (read by core): the GET field names sPattern / sCity / sRegion /
 * sPriceMin / sPriceMax / bPic / sCategory / sCountry, and the search_form hook
 * that plugins inject per-category fields into. Only presentation is ours.
 */

$sf_types  = Search::getAllowedTypesForSorting();
$sf_cat    = storefront_search_category();
$sf_multi  = storefront_search_multi_country();
$sf_country = Params::getParam('sCountry');
$sf_location_mode = storefront_setting('search_location_type', 'autocomplete');

// Shared by the "country_region_city" and "region_city" modes below: both render the
// same cascading Region + City <select> pair, differing only in whether a Country
// select is shown and what country code the pair is scoped to. Captures the current
// sRegion/sCity params once so both call sites resolve preselection identically.
$sf_region_param = Params::getParam('sRegion');
$sf_region_name  = osc_search_region();
$sf_city_param   = Params::getParam('sCity');
$sf_city_name    = osc_search_city();
$sf_render_region_city_selects = function ($sf_country_code) use ($sf_region_param, $sf_region_name, $sf_city_param, $sf_city_name) {
    // Region options come from the given country code, City options from the
    // resolved region. js/storefront-search.js re-fetches both lists (core's
    // regions/cities ajax) when Country or Region changes.
    $sf_regions = osc_get_regions($sf_country_code);
    $sf_region_id = '';
    foreach ($sf_regions as $sf_r) {
        if ((string) $sf_r['pk_i_id'] === (string) $sf_region_param
            || ($sf_region_name !== '' && $sf_r['s_name'] === $sf_region_name)
        ) {
            $sf_region_id = $sf_r['pk_i_id'];
            break;
        }
    }
    $sf_cities = ($sf_region_id !== '') ? osc_get_cities($sf_region_id) : array();
    ?>
    <div class="sf-field">
        <label for="sRegion"><?php _e('Region', 'storefront'); ?></label>
        <select class="sf-select" name="sRegion" id="sRegion">
            <option value=""><?php echo osc_esc_html(__('Any region', 'storefront')); ?></option>
            <?php foreach ($sf_regions as $sf_r) { ?>
                <option value="<?php echo osc_esc_html($sf_r['pk_i_id']); ?>" <?php echo ((string) $sf_r['pk_i_id'] === (string) $sf_region_id) ? 'selected' : ''; ?>><?php echo osc_esc_html($sf_r['s_name']); ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="sf-field">
        <label for="sCity"><?php _e('City', 'storefront'); ?></label>
        <select class="sf-select" name="sCity" id="sCity">
            <option value=""><?php echo osc_esc_html(__('Any city', 'storefront')); ?></option>
            <?php foreach ($sf_cities as $sf_ct) { ?>
                <option value="<?php echo osc_esc_html($sf_ct['pk_i_id']); ?>" <?php echo (((string) $sf_ct['pk_i_id'] === (string) $sf_city_param) || ($sf_city_name !== '' && $sf_ct['s_name'] === $sf_city_name)) ? 'selected' : ''; ?>><?php echo osc_esc_html($sf_ct['s_name']); ?></option>
            <?php } ?>
        </select>
    </div>
    <?php
};
?>
<aside class="sf-searchlayout__side sf-filters" id="sf-filters" aria-label="<?php echo osc_esc_html(__('Search filters', 'storefront')); ?>">
    <div class="sf-filters__head">
        <h2><?php _e('Filters', 'storefront'); ?></h2>
        <a class="sf-btn sf-btn--ghost sf-btn--sm" href="<?php echo osc_esc_html(osc_search_show_all_url()); ?>"><?php _e('Reset', 'storefront'); ?></a>
        <button type="button" class="sf-btn sf-btn--icon-only sf-filters__close" data-offcanvas-close
                aria-label="<?php echo osc_esc_html(__('Close filters', 'storefront')); ?>">
            <?php echo storefront_icon('x', 18); ?>
        </button>
    </div>

    <form class="sf-filters__form nocsrf" action="<?php echo osc_base_url(true); ?>" method="get">
        <input type="hidden" name="page" value="search" />
        <?php // Preserve sort across a filter change. ?>
        <input type="hidden" name="sOrder" value="<?php echo osc_esc_html(osc_search_order()); ?>" />
        <input type="hidden" name="iOrderType" value="<?php echo osc_esc_html($sf_types[osc_search_order_type()]); ?>" />
        <?php // The list/gallery choice is a view preference, not a query — it must survive a
              // filter submit, or the user is silently kicked back to the default every time. ?>
        <input type="hidden" name="sShowAs" value="<?php echo osc_esc_html(osc_search_show_as()); ?>" />
        <?php // Keep the browsed category when filters are applied. ?>
        <input type="hidden" name="sCategory" id="sCategory" value="<?php echo $sf_cat['id'] ? (int) $sf_cat['id'] : ''; ?>" />
        <?php foreach (osc_search_user() as $sf_uid) { ?>
            <input type="hidden" name="sUser[]" value="<?php echo osc_esc_html($sf_uid); ?>" />
        <?php } ?>

        <div class="sf-filters__group">
            <h3 class="sf-filters__label"><?php _e('Keywords', 'storefront'); ?></h3>
            <div class="sf-filters__search">
                <label class="sf-sr-only" for="query"><?php _e('Keywords', 'storefront'); ?></label>
                <input class="sf-input" type="search" name="sPattern" id="query"
                       value="<?php echo osc_esc_html(osc_search_pattern()); ?>"
                       placeholder="<?php echo osc_esc_html(__('What are you looking for?', 'storefront')); ?>" />
                <button class="sf-btn sf-btn--primary sf-btn--icon-only" type="submit"
                        aria-label="<?php echo osc_esc_html(__('Search', 'storefront')); ?>">
                    <?php echo storefront_icon('search', 16); ?>
                </button>
            </div>
        </div>

        <div class="sf-filters__group">
            <h3 class="sf-filters__label"><?php _e('Location', 'storefront'); ?></h3>

            <?php if ($sf_location_mode === 'country_region_city') { ?>
                <?php // Country, region & city: the Country select is always shown (even on a
                      // single-country install — an operator picking this mode wants the full
                      // three-tier control), and Region/City cascade from whichever country is
                      // currently selected (or "any", scoping to nothing selected). ?>
                <div class="sf-field">
                    <label for="sCountry"><?php _e('Country', 'storefront'); ?></label>
                    <select class="sf-select" name="sCountry" id="sCountry">
                        <option value=""><?php _e('Any country', 'storefront'); ?></option>
                        <?php foreach (Country::newInstance()->listAll() as $sf_c) { ?>
                            <option value="<?php echo osc_esc_html($sf_c['pk_c_code']); ?>" <?php echo ($sf_country === $sf_c['pk_c_code']) ? 'selected' : ''; ?>><?php echo osc_esc_html($sf_c['s_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <?php $sf_render_region_city_selects($sf_country); ?>
            <?php } elseif ($sf_location_mode === 'region_city') { ?>
                <?php // Region & city: for single-country sites. No Country select — sCountry is
                      // carried as a hidden field pinned to the site's default (first listed)
                      // country, unless a sCountry param already overrides it (e.g. a bookmarked
                      // search URL, or another mode's link). Region/City scope to that country. ?>
                <?php
                $sf_countries = Country::newInstance()->listAll();
                $sf_cc = ($sf_country !== '') ? $sf_country : (isset($sf_countries[0]['pk_c_code']) ? $sf_countries[0]['pk_c_code'] : '');
                ?>
                <input type="hidden" name="sCountry" id="sCountry" value="<?php echo osc_esc_html($sf_cc); ?>" />
                <?php $sf_render_region_city_selects($sf_cc); ?>
            <?php } else { ?>
                <?php // Autocomplete (default): Country select shown only when the install spans
                      // more than one country — a single-option select is a control you cannot
                      // operate. Region + City are free text with autocomplete against core's own
                      // ajax endpoints (page=ajax&action=location_regions|location_cities). The
                      // hidden *Id fields carry the resolved primary key when a suggestion is
                      // picked; core search accepts the id or the raw string, so typing a place
                      // that is not in the table still searches. ?>
                <?php if ($sf_multi) { ?>
                    <div class="sf-field">
                        <label for="sCountry"><?php _e('Country', 'storefront'); ?></label>
                        <select class="sf-select" name="sCountry" id="sCountry">
                            <option value=""><?php _e('Any country', 'storefront'); ?></option>
                            <?php foreach (Country::newInstance()->listAll() as $sf_c) { ?>
                                <option value="<?php echo osc_esc_html($sf_c['pk_c_code']); ?>" <?php echo ($sf_country === $sf_c['pk_c_code']) ? 'selected' : ''; ?>><?php echo osc_esc_html($sf_c['s_name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                <?php } else { ?>
                    <input type="hidden" name="sCountry" id="sCountry" value="<?php echo osc_esc_html($sf_country); ?>" />
                <?php } ?>

                <div class="sf-field">
                    <label for="sRegion"><?php _e('Region', 'storefront'); ?></label>
                    <input class="sf-input" type="text" name="sRegion" id="sRegion" autocomplete="off"
                           value="<?php echo osc_esc_html(osc_search_region()); ?>"
                           placeholder="<?php echo osc_esc_html(__('Any region', 'storefront')); ?>"
                           data-ac="location_regions" data-ac-url="<?php echo osc_esc_html(osc_base_url(true)); ?>"
                           data-ac-target="#regionId" data-ac-scope="#sCountry" data-ac-scope-param="country"
                           data-ac-clears="#sCity,#cityId" />
                    <input type="hidden" name="regionId" id="regionId" value="" />
                </div>
                <div class="sf-field">
                    <label for="sCity"><?php _e('City', 'storefront'); ?></label>
                    <input class="sf-input" type="text" name="sCity" id="sCity" autocomplete="off"
                           value="<?php echo osc_esc_html(osc_search_city()); ?>"
                           placeholder="<?php echo osc_esc_html(__('Any city', 'storefront')); ?>"
                           data-ac="location_cities" data-ac-url="<?php echo osc_esc_html(osc_base_url(true)); ?>"
                           data-ac-target="#cityId" data-ac-scope="#regionId" data-ac-scope-param="region" />
                    <input type="hidden" name="cityId" id="cityId" value="" />
                </div>
            <?php } ?>
        </div>

        <?php if (osc_price_enabled_at_items()) { ?>
        <div class="sf-filters__group">
            <h3 class="sf-filters__label"><?php _e('Price range', 'storefront'); ?></h3>
            <div class="sf-range">
                <label class="sf-sr-only" for="priceMin"><?php _e('Minimum price', 'storefront'); ?></label>
                <input class="sf-input" type="text" id="priceMin" name="sPriceMin" inputmode="numeric" maxlength="10" value="<?php echo osc_esc_html(osc_search_price_min()); ?>" placeholder="<?php echo osc_esc_html(__('Min', 'storefront')); ?>" />
                <span class="sf-range__sep" aria-hidden="true">&ndash;</span>
                <label class="sf-sr-only" for="priceMax"><?php _e('Maximum price', 'storefront'); ?></label>
                <input class="sf-input" type="text" id="priceMax" name="sPriceMax" inputmode="numeric" maxlength="10" value="<?php echo osc_esc_html(osc_search_price_max()); ?>" placeholder="<?php echo osc_esc_html(__('Max', 'storefront')); ?>" />
            </div>
        </div>
        <?php } ?>

        <?php if (osc_images_enabled_at_items()) { ?>
        <div class="sf-filters__group sf-field--check">
            <label class="sf-check" for="withPicture">
                <input type="checkbox" name="bPic" id="withPicture" value="1" <?php echo (osc_search_has_pic() ? 'checked' : ''); ?> />
                <?php _e('With photos only', 'storefront'); ?>
            </label>
        </div>
        <?php } ?>

        <?php
        // PLUGIN CONTRACT — the hook and its argument are unchanged. Plugins inject
        // per-category custom fields here (a "Condition" radio group is one such field;
        // core has no condition column). Only the wrapper around it is ours, and it is
        // drawn only when the hook actually emitted something — an empty bordered group
        // reads as a broken control on categories with no plugin fields.
        ob_start();
        if (osc_search_category_id()) {
            osc_run_hook('search_form', osc_search_category_id());
        } else {
            osc_run_hook('search_form');
        }
        $sf_plugin = trim(ob_get_clean());
        if ($sf_plugin !== '') {
            echo '<div class="sf-filters__group sf-filters__plugin">' . $sf_plugin . '</div>';
        }
        ?>

        <div class="sf-form__actions">
            <button type="submit" class="sf-btn sf-btn--primary sf-btn--block"><?php _e('Apply filters', 'storefront'); ?></button>
        </div>
    </form>

    <?php // Category refinement is NAVIGATION, not a form control — each row is a URL you can
          // bookmark, share and open in a new tab, and the current category doubles as "you are here". ?>
    <nav class="sf-filters__group sf-catnav" aria-label="<?php echo osc_esc_html(__('Refine by category', 'storefront')); ?>">
        <h3 class="sf-filters__label"><?php _e('Categories', 'storefront'); ?></h3>
        <ul class="sf-cat-list">
            <?php storefront_subcategories(); ?>
        </ul>
    </nav>

    <?php
    // Email alerts. Core's ajax `alerts` action optionally requires a logged-in user
    // (the `alerts_require_login` preference under Settings → Spam & bots) to stop
    // anonymous address harvesting. Draw the form exactly where core will accept a
    // subscription: to everyone when the switch is off, to signed-in users only when
    // it is on — with a sign-in prompt for guests so the feature is still discoverable.
    if (osc_users_enabled()) {
        $sf_alerts_gated = (bool) osc_get_preference('alerts_require_login');
        if (osc_is_web_user_logged_in() || !$sf_alerts_gated) { ?>
            <div class="sf-filters__group sf-filters__alert">
                <?php osc_alert_form(); ?>
            </div>
        <?php } else { ?>
            <div class="sf-filters__group sf-filters__alert">
                <h3 class="sf-filters__label"><?php _e('Get an email alert', 'storefront'); ?></h3>
                <p class="sf-alert-form__hint">
                    <?php printf(
                        __('%s to be emailed when a new listing matches this search.', 'storefront'),
                        '<a href="' . osc_esc_html(osc_user_login_url()) . '">' . osc_esc_html(__('Sign in', 'storefront')) . '</a>'
                    ); ?>
                </p>
            </div>
        <?php }
    }
    ?>

    <div class="sf-filters__promo">
        <h3><?php _e('Selling something?', 'storefront'); ?></h3>
        <p><?php _e('Post a free listing in under two minutes.', 'storefront'); ?></p>
        <a class="sf-btn sf-btn--primary sf-btn--block" href="<?php echo osc_item_post_url_in_category(); ?>"><?php _e('Post a listing', 'storefront'); ?></a>
    </div>
</aside>
