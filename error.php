<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
$sf_error = View::newInstance()->_get('error');
if (!is_string($sf_error) || $sf_error === '') {
    $sf_error = __('Something went wrong. Please try again later.', 'storefront');
}
osc_current_web_theme_path('common/header.php');
?>
<section class="sf-section sf-error">
    <h1 class="sf-section__title"><?php _e('Something went wrong', 'storefront'); ?></h1>
    <p class="sf-empty"><?php echo osc_esc_html($sf_error); ?></p>
    <p class="sf-more">
        <a class="sf-btn sf-btn--ghost" href="<?php echo osc_base_url(); ?>"><?php _e('Back to home', 'storefront'); ?></a>
    </p>
</section>
<?php osc_current_web_theme_path('common/footer.php'); ?>
