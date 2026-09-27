<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Partial: the account chrome on core's account pages, hung on core's
 * account_page_before hook: an identity band, the account tabs, the settings
 * sub-tabs, and the page's own heading. Core draws everything below it.
 * Reads $sf_page, the core page slug the hook passed.
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

$sf_page = isset($sf_page) ? (string) $sf_page : '';

// Live listings only: the figure a seller watches, one COUNT on their own rows.
$sf_active = (int) Item::newInstance()->countItemTypesByUserID(osc_logged_user_id(), 'active');

$sf_settings = in_array($sf_page, array('user-profile', 'user-signin', 'user-delete_account'), true);
$sf_tabs = array(
    array('name' => __('Dashboard', 'storefront'),   'url' => osc_user_dashboard_url(),  'icon' => 'layout', 'current' => $sf_page === 'user-dashboard'),
    array('name' => __('My listings', 'storefront'), 'url' => osc_user_list_items_url(), 'icon' => 'list',   'current' => $sf_page === 'user-items'),
    array('name' => __('Alerts', 'storefront'),      'url' => osc_user_alerts_url(),     'icon' => 'bell',   'current' => $sf_page === 'user-alerts'),
    // "Settings", not "Profile": the sub-tabs below own "Profile". `soft` keeps it
    // active without a second aria-current, which the sub-tab carries.
    array('name' => __('Settings', 'storefront'),    'url' => osc_user_profile_url(),    'icon' => 'settings', 'soft' => true, 'current' => $sf_settings),
);
if (function_exists('osc_billing_enabled') && osc_billing_enabled()) {
    $sf_tabs[] = array('name' => __('Credits', 'storefront'), 'url' => osc_billing_wallet_url(), 'icon' => 'credit-card', 'current' => osc_get_osclass_location() === 'billing');
}

$sf_settings_tabs = array(
    array('name' => __('Profile', 'storefront'),  'url' => osc_user_profile_url(),        'current' => $sf_page === 'user-profile'),
    array('name' => __('Username', 'storefront'), 'url' => osc_change_user_username_url(), 'current' => osc_is_change_username_page()),
    array('name' => __('E-mail', 'storefront'),   'url' => osc_change_user_email_url(),    'current' => osc_is_change_email_page()),
    array('name' => __('Password', 'storefront'), 'url' => osc_change_user_password_url(), 'current' => osc_is_change_password_page()),
);

// The heading each page had when the theme drew it; core's own h1 is kept for
// screen readers and hidden on screen (css/storefront.css).
$sf_titles = array(
    'user-dashboard'      => __('Your latest listings', 'storefront'),
    'user-items'          => __('My listings', 'storefront'),
    'user-alerts'         => __('Alerts', 'storefront'),
    'user-profile'        => __('Edit profile', 'storefront'),
    'user-signin'         => __('Sign-in details', 'storefront'),
    'user-delete_account' => __('Delete your account', 'storefront'),
    'billing-wallet'      => __('Credits', 'storefront'),
    'billing-buy'         => __('Buy credits', 'storefront'),
    'billing-orders'      => __('Your orders', 'storefront'),
);
?>
<div class="sf-account">
<header class="sf-account-head">
    <span class="sf-avatar sf-account-head__avatar">
        <?php if (sf_has_avatar()) { ?>
            <img src="<?php echo osc_esc_html(osc_user_avatar_url(osc_logged_user_id(), 'thumbnail')); ?>" alt="" width="60" height="60" />
        <?php } else { ?>
            <span class="sf-avatar__monogram" aria-hidden="true"><?php echo osc_esc_html($sf_initial); ?></span>
        <?php } ?>
    </span>
    <div class="sf-account-head__identity">
        <p class="sf-account-head__name" role="presentation"><?php echo osc_esc_html($sf_name); ?></p>
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

<?php if ($sf_settings) { ?>
    <nav class="sf-settings-nav" aria-label="<?php echo osc_esc_html(__('Account settings', 'storefront')); ?>">
        <?php foreach ($sf_settings_tabs as $sf_st) { ?>
            <a class="sf-settings-nav__tab<?php echo $sf_st['current'] ? ' is-active' : ''; ?>"
               href="<?php echo osc_esc_html($sf_st['url']); ?>"<?php echo $sf_st['current'] ? ' aria-current="page"' : ''; ?>>
                <?php echo osc_esc_html($sf_st['name']); ?>
            </a>
        <?php } ?>
    </nav>
<?php } ?>

<?php if (isset($sf_titles[$sf_page])) { ?>
    <div class="sf-manage-head">
        <div>
            <h2 class="sf-section__title"><?php echo osc_esc_html($sf_titles[$sf_page]); ?></h2>
            <?php if ($sf_page === 'user-dashboard' && $sf_active > 0) { ?>
                <p class="sf-dash-lead">
                    <strong class="sf-dash-lead__count"><?php echo $sf_active; ?></strong>
                    <?php echo osc_esc_html($sf_active === 1 ? __('active listing', 'storefront') : __('active listings', 'storefront')); ?>
                </p>
            <?php } ?>
            <?php if ($sf_page === 'user-profile') { ?>
                <p class="sf-form__lede"><?php _e('This is how buyers and sellers see you across the marketplace.', 'storefront'); ?></p>
            <?php } ?>
            <?php if ($sf_page === 'user-delete_account') { ?>
                <p class="sf-form__lede"><?php _e('Enter your password to delete your account. Your listings and messages are removed with it. This cannot be undone.', 'storefront'); ?></p>
            <?php } ?>
        </div>
        <?php if ($sf_page === 'user-dashboard' && $sf_active > 0) { ?>
            <a class="sf-account-more" href="<?php echo osc_esc_html(osc_user_list_items_url()); ?>">
                <?php _e('Manage all', 'storefront'); ?><?php echo storefront_icon('chevron-right', 15); ?>
            </a>
        <?php } elseif ($sf_page === 'user-items') { ?>
            <a class="sf-btn sf-btn--primary sf-btn--sm" href="<?php echo osc_esc_html(osc_item_post_url_in_category()); ?>">
                <?php echo storefront_icon('plus', 15); ?><span><?php _e('Post a listing', 'storefront'); ?></span>
            </a>
        <?php } ?>
    </div>
<?php } ?>
</div>
