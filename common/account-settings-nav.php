<?php
/*
 * Storefront — account settings sub-navigation. A secondary tab strip inside the
 * "Profile" section so a signed-in user can move between editing their profile,
 * username, e-mail and password (each is a separate core view). Included at the top
 * of those views' main column, under the primary account tabs. Presentation only.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
$sf_settings_tabs = array(
    array('name' => __('Profile', 'storefront'),  'url' => osc_user_profile_url(),        'current' => osc_is_user_profile()),
    array('name' => __('Username', 'storefront'), 'url' => osc_change_user_username_url(), 'current' => osc_is_change_username_page()),
    array('name' => __('E-mail', 'storefront'),   'url' => osc_change_user_email_url(),    'current' => osc_is_change_email_page()),
    array('name' => __('Password', 'storefront'), 'url' => osc_change_user_password_url(), 'current' => osc_is_change_password_page()),
);
?>
<nav class="sf-settings-nav" aria-label="<?php echo osc_esc_html(__('Account settings', 'storefront')); ?>">
    <?php foreach ($sf_settings_tabs as $sf_st) { ?>
        <a class="sf-settings-nav__tab<?php echo $sf_st['current'] ? ' is-active' : ''; ?>"
           href="<?php echo osc_esc_html($sf_st['url']); ?>"<?php echo $sf_st['current'] ? ' aria-current="page"' : ''; ?>>
            <?php echo osc_esc_html($sf_st['name']); ?>
        </a>
    <?php } ?>
</nav>
