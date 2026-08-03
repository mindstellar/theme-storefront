<?php
/*
 * Storefront — site header and the opening of the page shell.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
?><!DOCTYPE html>
<html lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>" dir="<?php echo osc_get_preference('rtl', 'storefront') === '1' ? 'rtl' : 'ltr'; ?>">
<head>
<?php osc_current_web_theme_path('common/head.php'); ?>
</head>
<body <?php storefront_body_class(); ?>>
<a class="sf-skip" href="#sf-main"><?php _e('Skip to content', 'storefront'); ?></a>
<header class="sf-header">
    <div class="sf-container sf-header__bar">
        <a class="sf-logo" href="<?php echo osc_base_url(); ?>"><?php echo storefront_logo(); ?></a>

        <button class="sf-nav-toggle" type="button" aria-expanded="false"
                aria-controls="sf-nav" aria-label="<?php echo osc_esc_html(__('Menu', 'storefront')); ?>"
                data-sf-nav-toggle>
            <span></span><span></span><span></span>
        </button>

        <nav id="sf-nav" class="sf-nav" aria-label="<?php echo osc_esc_html(__('Primary', 'storefront')); ?>">
            <a class="sf-nav__link" href="<?php echo osc_base_url(); ?>"<?php echo osc_is_home_page() ? ' aria-current="page"' : ''; ?>><?php _e('Home', 'storefront'); ?></a>
            <a class="sf-nav__link" href="<?php echo osc_search_show_all_url(); ?>"<?php echo osc_is_search_page() ? ' aria-current="page"' : ''; ?>><?php _e('Browse', 'storefront'); ?></a>
            <a class="sf-nav__link" href="<?php echo osc_contact_url(); ?>"<?php echo osc_is_contact_page() ? ' aria-current="page"' : ''; ?>><?php _e('Help', 'storefront'); ?></a>

            <?php if (osc_users_enabled()) { ?>
                <?php if (osc_is_web_user_logged_in()) {
                    // One account control: an avatar+name button that opens a menu
                    // (dashboard, listings, alerts, profile, log out). On desktop it is
                    // a dropdown; inside the mobile drawer the CSS shows the items inline.
                    // Replaces the old avatar-icon + "Hi, name" + top-level "Log out",
                    // which duplicated the dashboard target and left logout one mis-tap away.
                    $sf_uname = osc_logged_user_name();
                    $sf_umono = $sf_uname !== '' ? mb_strtoupper(mb_substr($sf_uname, 0, 1, 'UTF-8'), 'UTF-8') : '?';
                ?>
                    <div class="sf-account" data-sf-menu>
                        <button class="sf-account__toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="sf-account-menu">
                            <span class="sf-avatar sf-account__avatar" aria-hidden="true">
                                <?php if (sf_has_avatar()) { ?>
                                    <img src="<?php echo osc_esc_html(osc_user_avatar_url(osc_logged_user_id(), 'thumbnail')); ?>" alt="" width="28" height="28" />
                                <?php } else { ?>
                                    <span class="sf-avatar__monogram"><?php echo osc_esc_html($sf_umono); ?></span>
                                <?php } ?>
                            </span>
                            <span class="sf-account__name"><?php echo osc_esc_html($sf_uname); ?></span>
                            <?php echo storefront_icon('chevron-down', 16); ?>
                        </button>
                        <div class="sf-account__menu" id="sf-account-menu" role="menu" hidden>
                            <a class="sf-account__item" role="menuitem" href="<?php echo osc_esc_html(osc_user_dashboard_url()); ?>"><?php _e('Dashboard', 'storefront'); ?></a>
                            <a class="sf-account__item" role="menuitem" href="<?php echo osc_esc_html(osc_user_list_items_url()); ?>"><?php _e('My listings', 'storefront'); ?></a>
                            <a class="sf-account__item" role="menuitem" href="<?php echo osc_esc_html(osc_user_alerts_url()); ?>"><?php _e('Alerts', 'storefront'); ?></a>
                            <a class="sf-account__item" role="menuitem" href="<?php echo osc_esc_html(osc_user_profile_url()); ?>"><?php _e('Profile', 'storefront'); ?></a>
                            <hr class="sf-account__sep" />
                            <a class="sf-account__item sf-account__item--logout" role="menuitem" href="<?php echo osc_esc_html(osc_user_logout_url()); ?>"><?php echo storefront_icon('log-out', 15); ?><span><?php _e('Log out', 'storefront'); ?></span></a>
                        </div>
                    </div>
                <?php } else { ?>
                    <a class="sf-nav__link" href="<?php echo osc_user_login_url(); ?>"><?php _e('Log in', 'storefront'); ?></a>
                    <?php if (osc_user_registration_enabled()) { ?>
                        <a class="sf-nav__link" href="<?php echo osc_register_account_url(); ?>"><?php _e('Register', 'storefront'); ?></a>
                    <?php } ?>
                <?php } ?>
            <?php } ?>

            <?php if (osc_users_enabled() || (!osc_users_enabled() && !osc_reg_user_post())) { ?>
                <a class="sf-btn sf-btn--primary sf-nav__cta" href="<?php echo osc_item_post_url_in_category(); ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                    <?php _e('Post a listing', 'storefront'); ?>
                </a>
            <?php } ?>
        </nav>
    </div>
</header>
<?php // Scrim behind the open mobile nav drawer — click to dismiss; the drawer
      // becomes modal (scroll-locked, focus-trapped) while it is open. ?>
<div class="sf-nav-backdrop" data-sf-nav-close hidden></div>

<main id="sf-main" class="sf-main">
<?php osc_run_hook('before-main'); ?>
<?php
// Core queues user feedback (listing published, profile saved, invalid login, an
// alert subscription confirmed, …) as flash messages to render on the next page
// load. The theme must show them or that feedback is silently lost — the default
// theme does the same in its header. Capture first so the container is drawn only
// when there is actually a message, and so the (rare) flash on the full-bleed home
// still sits inside a container. Keep core's default class/id so its dismiss markup
// and any plugin JS keyed on them keep working; presentation is styled in the CSS.
ob_start();
osc_show_flash_message();
$sf_flash = trim(ob_get_clean());
if ($sf_flash !== '') {
    echo '<div class="sf-container sf-flash-wrap">' . $sf_flash . '</div>';
}
?>
<?php // Home is full-bleed (its sections carry their own inner containers); every
      // other view sits inside one padded container. ?>
<?php if (!osc_is_home_page()) { ?><div class="sf-container sf-main__pad"><?php } ?>
