<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Core draws the account, auth, profile and contact pages. This file hangs the
 * theme's pieces on core's hooks; css/storefront.css styles core's .oe-* markup.
 */

// Core opens and closes its own pages with the theme's header and footer,
// the credits pages included.
osc_add_theme_support('chrome', array(
    'header'  => 'common/header.php',
    'footer'  => 'common/footer.php',
    'account' => true,
));

/**
 * The account chrome on every account page: identity band, tabs, and the page's
 * heading, as the theme drew them before core took the pages over.
 *
 * @param string $page core's page slug
 */
function storefront_account_band($page)
{
    if (!osc_is_web_user_logged_in()) {
        return;
    }
    $sf_page = (string) $page;
    require STOREFRONT_THEME_FOLDER . '/common/account-header.php';
}
osc_add_hook('account_page_before', 'storefront_account_band');

/**
 * The dashboard's widget slot, below core's overview.
 *
 * @param string $page
 */
function storefront_account_widgets($page)
{
    if ($page === 'user-dashboard') {
        osc_show_widgets('user-dashboard');
    }
}
osc_add_hook('account_page_after', 'storefront_account_widgets');

/**
 * The dashboard, public profile and alert lists as the theme's card grid. Your
 * listings keeps core's rows, which carry the status and the actions.
 *
 * @param string|null $html
 * @param array       $items
 * @param string      $context
 *
 * @return string|null
 */
function storefront_listing_cards($html, $items, $context)
{
    if ($context === 'user_items') {
        ob_start();
        require STOREFRONT_THEME_FOLDER . '/common/manage-list.php';

        return (string) ob_get_clean();
    }
    if (!in_array($context, array('dashboard', 'public_profile', 'alert'), true)) {
        return $html;
    }

    // One query for every card's upgrades, rather than one per card.
    osc_prime_item_upgrades($items);

    $view = View::newInstance();
    if ($context === 'dashboard') {
        // One row of the owner's newest listings; "Manage all" leads to the rest.
        $view->_exportVariableToView('items', array_slice($items, 0, 3));
    }
    $view->_exportVariableToView('listType', 'items');
    $view->_exportVariableToView('sfCardOwner', $context === 'dashboard');
    $view->_exportVariableToView('sfCardViews', $context === 'dashboard');

    ob_start();
    osc_current_web_theme_path('common/loop.php');

    return (string) ob_get_clean();
}
osc_add_filter('listing_list_html', 'storefront_listing_cards');

/**
 * The letter badge that stands in for a missing profile picture. core prints a
 * placeholder image; the account script swaps it for this badge.
 */
function storefront_avatar_monogram()
{
    echo '<span class="sf-avatar sf-avatar--lg" hidden data-sf-monogram><span class="sf-avatar__monogram">'
        . osc_esc_html(sf_user_monogram()) . '</span></span>';
}
osc_add_hook('user_avatar_form', 'storefront_avatar_monogram');

// The profile page's photo preview lives in the account bundle.
osc_add_hook('account_page_before', static function ($page) {
    if ($page === 'user-profile') {
        storefront_enqueue_bundle('account');
    }
});

/**
 * Beside core's contact form: the support hours and quick links panels.
 */
function storefront_contact_aside()
{
    ?>
    <div class="sf-panel sf-contact-panel">
        <h2 class="sf-contact-panel__title"><?php _e('Support hours', 'storefront'); ?></h2>
        <p class="sf-contact-panel__lead"><?php _e('We reply as soon as we can', 'storefront'); ?></p>
        <p class="sf-contact-panel__note"><?php _e('Typically within 24 hours', 'storefront'); ?></p>
    </div>
    <div class="sf-panel sf-contact-panel">
        <h2 class="sf-contact-panel__title"><?php _e('Quick links', 'storefront'); ?></h2>
        <ul class="sf-contact-panel__links">
            <li><a href="<?php echo osc_esc_html(osc_item_post_url_in_category()); ?>"><?php _e('Post a free listing', 'storefront'); ?> &rarr;</a></li>
            <li><a href="<?php echo osc_esc_html(osc_search_show_all_url()); ?>"><?php _e('Browse listings', 'storefront'); ?> &rarr;</a></li>
            <?php
            // The static-page cursor is shared with the footer: reset before and after.
            osc_reset_static_pages();
            while (osc_has_static_pages()) { ?>
                <li><a href="<?php echo osc_esc_html(osc_static_page_url()); ?>"><?php echo osc_esc_html(osc_static_page_title()); ?> &rarr;</a></li>
            <?php }
            osc_reset_static_pages(); ?>
        </ul>
    </div>
    <?php
}
osc_add_hook('contact_page_aside', 'storefront_contact_aside');
