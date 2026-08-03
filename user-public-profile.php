<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * A seller's public profile — the surface where a buyer decides whether to trust
 * a stranger, so identity leads: avatar, name, location, tenure. The avatar comes
 * from core's own resource layer (osc_user_avatar_url), NOT a third party, so a
 * seller's uploaded photo is what shows and no email hash leaks off-site.
 */

$sf_uid = osc_user_id();

$sf_address = '';
if (osc_user_address() != '') {
    $sf_address = osc_user_city_area() != '' ? osc_user_address() . ', ' . osc_user_city_area() : osc_user_address();
} else {
    $sf_address = osc_user_city_area();
}
$sf_location_parts = array();
if (trim(osc_user_city() . ' ' . osc_user_zip()) != '') {
    $sf_location_parts[] = trim(osc_user_city() . ' ' . osc_user_zip());
}
if (osc_user_region() != '') {
    $sf_location_parts[] = osc_user_region();
}
if (osc_user_country() != '') {
    $sf_location_parts[] = osc_user_country();
}
$sf_location = implode(', ', $sf_location_parts);

$sf_since = (function_exists('osc_user_regdate') && osc_user_regdate() != '')
    ? date('Y', strtotime(osc_user_regdate()))
    : '';

osc_current_web_theme_path('common/header.php');
?>
<section class="sf-section">
    <header class="sf-sellercard">
        <span class="sf-avatar sf-avatar--lg">
            <?php if (sf_has_avatar($sf_uid)) { ?>
                <img src="<?php echo osc_esc_html(osc_user_avatar_url($sf_uid, 'normal')); ?>" alt="" />
            <?php } else { ?>
                <span class="sf-avatar__monogram"><?php echo osc_esc_html(sf_user_monogram(osc_user_name())); ?></span>
            <?php } ?>
        </span>
        <div class="sf-sellercard__identity">
            <h1 class="sf-sellercard__name"><?php echo osc_esc_html(osc_user_name()); ?></h1>
            <p class="sf-sellercard__meta">
                <?php
                    $sf_bits = array();
                    $sf_bits[] = '<span>' . osc_esc_html(
                        function_exists('osc_user_is_company') && osc_user_is_company()
                            ? __('Company', 'storefront')
                            : __('Individual', 'storefront')
                    ) . '</span>';
                    if ($sf_location !== '') {
                        $sf_bits[] = '<span class="sf-sellercard__loc">' . storefront_icon('map-pin', 14) . osc_esc_html($sf_location) . '</span>';
                    }
                    if ($sf_since !== '') {
                        $sf_bits[] = '<span>' . osc_esc_html(sprintf(__('Member since %s', 'storefront'), $sf_since)) . '</span>';
                    }
                    echo implode('<span class="sf-sellercard__sep" aria-hidden="true">&middot;</span>', $sf_bits);
                ?>
            </p>
            <?php if (osc_user_website() !== '') { ?>
                <a class="sf-sellercard__web" href="<?php echo osc_esc_html(osc_user_website()); ?>" rel="nofollow noopener">
                    <?php echo storefront_icon('globe', 14); ?><span><?php echo osc_esc_html(preg_replace('~^https?://~i', '', rtrim(osc_user_website(), '/'))); ?></span>
                </a>
            <?php } ?>
        </div>
    </header>

    <?php if (osc_user_info() !== '') { ?>
        <h2 class="sf-section__title sf-sellercard__subhead"><?php _e('About', 'storefront'); ?></h2>
        <div class="sf-sellercard__about"><?php echo nl2br(osc_esc_html(osc_user_info())); ?></div>
    <?php } ?>

    <?php
        // Any visitor who isn't the owner gets a contact panel beside the listings:
        // the message form when they're allowed to send one, or a log-in prompt when
        // core gates contact behind an account (the partial decides which). Two
        // columns only when there's both a panel AND listings to sit next to.
        $sf_is_owner  = (osc_logged_user_id() == osc_user_id());
        $sf_has_items = osc_count_items() > 0;
        $sf_two_col   = !$sf_is_owner && $sf_has_items;
    ?>
    <div class="sf-pubprofile<?php echo $sf_two_col ? '' : ' sf-pubprofile--solo'; ?>">
        <div class="sf-pubprofile__main">
            <?php if ($sf_has_items) { ?>
                <h2 class="sf-section__title sf-sellercard__subhead"><?php _e('Latest listings', 'storefront'); ?></h2>
                <?php
                osc_current_web_theme_path('common/loop.php');
                View::newInstance()->_exportVariableToView('sf_pagination_html', osc_pagination_items());
                osc_current_web_theme_path('common/pagination.php');
                ?>
            <?php } ?>
        </div>
        <?php if (!$sf_is_owner) { ?>
            <div class="sf-pubprofile__aside">
                <?php osc_current_web_theme_path('common/user-public-sidebar.php'); ?>
            </div>
        <?php } ?>
    </div>
</section>
<?php osc_current_web_theme_path('common/footer.php'); ?>
