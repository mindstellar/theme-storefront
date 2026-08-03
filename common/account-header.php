<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Partial: account chrome for the logged-in area — an identity band plus a
 * horizontal tab strip. It replaces the old vertical sidebar rail, which on
 * mobile stacked above the page so every account page opened on a menu instead
 * of on the thing the user came for. Included at the top of every user-* view;
 * it prints identity + navigation only, never page content.
 */

$sf_acct = sf_account_user();
$sf_name = osc_logged_user_name();

// Monogram avatar — the platform ships no avatar helper, so a branded initial
// stands in (first letter of the display name, uppercased, multibyte-safe).
$sf_initial = $sf_name !== '' ? mb_strtoupper(mb_substr($sf_name, 0, 1, 'UTF-8'), 'UTF-8') : '?';

// Member-since is just a year; a bare year needs no locale-aware formatter.
$sf_since = ($sf_acct && !empty($sf_acct['dt_reg_date']))
    ? date('Y', strtotime($sf_acct['dt_reg_date']))
    : '';

$sf_place = $sf_acct
    ? storefront_location_line(array($sf_acct['s_city'] ?? '', $sf_acct['s_region'] ?? ''))
    : '';

// Public osc_* URLs + osc_is_* current checks; icons from the theme set.
// Favourites is a private-plugin route, deliberately absent from a public theme.
$sf_tabs = array(
    array('name' => __('Dashboard', 'storefront'),  'url' => osc_user_dashboard_url(),  'icon' => 'layout',   'current' => osc_is_user_dashboard()),
    array('name' => __('My listings', 'storefront'), 'url' => osc_user_list_items_url(), 'icon' => 'list',     'current' => osc_is_list_items()),
    array('name' => __('Alerts', 'storefront'),      'url' => osc_user_alerts_url(),     'icon' => 'bell',     'current' => osc_is_list_alerts()),
    // "Settings", not "Profile" — the settings sub-nav below already owns a "Profile"
    // tab, and this primary tab covers all four settings views. `soft` keeps it
    // visually active without claiming aria-current="page": on a settings page the
    // sub-nav marks the current location, so only one "current page" is announced.
    array('name' => __('Settings', 'storefront'),    'url' => osc_user_profile_url(),    'icon' => 'settings', 'soft' => true,
          'current' => osc_is_user_profile() || osc_is_change_email_page() || osc_is_change_username_page() || osc_is_change_password_page()),
);
?>
<header class="sf-account-head">
    <span class="sf-avatar sf-account-head__avatar">
        <?php if (sf_has_avatar()) { ?>
            <img src="<?php echo osc_esc_html(osc_user_avatar_url(osc_logged_user_id(), 'thumbnail')); ?>" alt="" width="60" height="60" />
        <?php } else { ?>
            <span class="sf-avatar__monogram" aria-hidden="true"><?php echo osc_esc_html($sf_initial); ?></span>
        <?php } ?>
    </span>
    <div class="sf-account-head__identity">
        <h1 class="sf-account-head__name"><?php echo osc_esc_html($sf_name); ?></h1>
        <p class="sf-account-head__meta">
            <span><?php echo osc_esc_html(empty($sf_acct['b_company']) ? __('Individual', 'storefront') : __('Company', 'storefront')); ?></span>
            <?php if ($sf_place !== '') { ?>
                <span class="sf-account-head__sep" aria-hidden="true">&middot;</span>
                <span class="sf-account-head__place"><?php echo storefront_icon('map-pin', 13); ?><?php echo osc_esc_html($sf_place); ?></span>
            <?php } ?>
            <?php if ($sf_since !== '') { ?>
                <span class="sf-account-head__sep" aria-hidden="true">&middot;</span>
                <span><?php printf(osc_esc_html(__('Member since %s', 'storefront')), osc_esc_html($sf_since)); ?></span>
            <?php } ?>
        </p>
    </div>
    <div class="sf-account-head__actions">
        <a class="sf-btn sf-btn--secondary" href="<?php echo osc_esc_html(osc_user_public_profile_url(osc_logged_user_id())); ?>">
            <?php echo storefront_icon('globe', 15); ?><span><?php _e('Public profile', 'storefront'); ?></span>
        </a>
        <a class="sf-btn sf-btn--primary" href="<?php echo osc_esc_html(osc_item_post_url_in_category()); ?>">
            <?php echo storefront_icon('plus', 15); ?><span><?php _e('Post a listing', 'storefront'); ?></span>
        </a>
    </div>
</header>

<nav class="sf-account-tabs" aria-label="<?php echo osc_esc_html(__('Your account', 'storefront')); ?>">
    <?php foreach ($sf_tabs as $sf_tab) { ?>
        <a class="sf-account-tab<?php echo $sf_tab['current'] ? ' is-active' : ''; ?>"
           href="<?php echo osc_esc_html($sf_tab['url']); ?>"<?php echo ($sf_tab['current'] && empty($sf_tab['soft'])) ? ' aria-current="page"' : ''; ?>>
            <?php echo storefront_icon($sf_tab['icon'], 16); ?><span><?php echo osc_esc_html($sf_tab['name']); ?></span>
        </a>
    <?php }
        // PLUGIN CONTRACT — plugins hang extra account-menu items off this hook.
        osc_run_hook('user_menu');
    ?>
</nav>
