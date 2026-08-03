<?php
/*
 * Storefront — closing of the page shell and site footer.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
?>
<?php if (!osc_is_home_page()) { ?></div><!-- .sf-container --><?php } ?>
<?php osc_run_hook('after-main'); ?>
</main>

<?php osc_show_widgets('footer'); ?>

<?php
// Static pages are admin-authored (About, Terms, Safety…). Buffer the loop so the
// "About" column is drawn only when at least one exists — an empty titled column
// reads as a broken footer.
osc_reset_static_pages();
ob_start();
while (osc_has_static_pages()) {
    echo '<a href="' . osc_static_page_url() . '">' . osc_esc_html(osc_static_page_title()) . '</a>';
}
$sf_pages = trim(ob_get_clean());
// Whether the header/theme offers a post CTA at all — mirror the header's gate so
// the footer never shows a "Post" button the header withholds.
$sf_can_post = osc_users_enabled() || (!osc_users_enabled() && !osc_reg_user_post());
?>
<footer class="sf-footer">
    <div class="sf-container sf-footer__grid">
        <div class="sf-footer__brand">
            <a class="sf-logo" href="<?php echo osc_base_url(); ?>"><?php echo storefront_logo(); ?></a>
            <p class="sf-footer__tagline">
                <?php if (osc_page_description() !== '') {
                    echo osc_esc_html(osc_page_description());
                } else {
                    _e('Buy and sell locally — real listings, real people, near you.', 'storefront');
                } ?>
            </p>
            <?php if ($sf_can_post) { ?>
                <a class="sf-btn sf-btn--primary sf-footer__cta" href="<?php echo osc_item_post_url_in_category(); ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                    <?php _e('Post a listing', 'storefront'); ?>
                </a>
            <?php } ?>
        </div>

        <nav class="sf-footer__links" aria-label="<?php echo osc_esc_html(__('Marketplace', 'storefront')); ?>">
            <h2><?php _e('Marketplace', 'storefront'); ?></h2>
            <a href="<?php echo osc_search_show_all_url(); ?>"><?php _e('Browse listings', 'storefront'); ?></a>
            <?php if ($sf_can_post) { ?>
                <a href="<?php echo osc_item_post_url_in_category(); ?>"><?php _e('Post a listing', 'storefront'); ?></a>
            <?php } ?>
            <a href="<?php echo osc_contact_url(); ?>"><?php _e('Contact', 'storefront'); ?></a>
        </nav>

        <?php if (osc_users_enabled()) { ?>
        <nav class="sf-footer__links" aria-label="<?php echo osc_esc_html(__('Your account', 'storefront')); ?>">
            <h2><?php _e('Account', 'storefront'); ?></h2>
            <?php if (osc_is_web_user_logged_in()) { ?>
                <a href="<?php echo osc_user_dashboard_url(); ?>"><?php _e('Dashboard', 'storefront'); ?></a>
                <a href="<?php echo osc_user_list_items_url(); ?>"><?php _e('My listings', 'storefront'); ?></a>
                <a href="<?php echo osc_user_alerts_url(); ?>"><?php _e('Alerts', 'storefront'); ?></a>
                <a href="<?php echo osc_user_logout_url(); ?>"><?php _e('Log out', 'storefront'); ?></a>
            <?php } else { ?>
                <a href="<?php echo osc_user_login_url(); ?>"><?php _e('Log in', 'storefront'); ?></a>
                <?php if (osc_user_registration_enabled()) { ?>
                    <a href="<?php echo osc_register_account_url(); ?>"><?php _e('Register', 'storefront'); ?></a>
                <?php } ?>
            <?php } ?>
        </nav>
        <?php } ?>

        <?php if ($sf_pages !== '') { ?>
        <nav class="sf-footer__links" aria-label="<?php echo osc_esc_html(__('About', 'storefront')); ?>">
            <h2><?php _e('About', 'storefront'); ?></h2>
            <?php echo $sf_pages; ?>
        </nav>
        <?php } ?>
    </div>

    <div class="sf-footer__legal">
        <div class="sf-container sf-footer__legal-row">
            <p class="sf-footer__copy">
                <?php // A copyright line always renders, so the legal bar is never an empty ruled strip. ?>
                &copy; <?php echo osc_esc_html(date('Y') . ' ' . osc_page_title()); ?>
                <?php if ((!defined('MULTISITE') || MULTISITE == 0) && osc_get_preference('footer_link', 'storefront') !== '0') { ?>
                    <span class="sf-footer__sep" aria-hidden="true">&middot;</span>
                    <?php printf(__('Powered by %s', 'storefront'), '<a href="https://github.com/mindstellar/shopclass">Shopclass</a>'); ?>
                <?php } ?>
            </p>

            <div class="sf-footer__legal-end">
                <?php if (osc_count_web_enabled_locales() > 1) {
                    // Build the locale links once; capture the active locale's name so the
                    // collapsed switcher can label itself with the current language.
                    $sf_locale = osc_current_user_locale();
                    $sf_locale_name = '';
                    osc_goto_first_locale();
                    ob_start();
                    while (osc_has_web_enabled_locales()) {
                        $sf_active = osc_locale_code() === $sf_locale;
                        if ($sf_active) { $sf_locale_name = osc_locale_name(); }
                        echo '<a href="' . osc_change_language_url(osc_locale_code()) . '"'
                            . ($sf_active ? ' class="is-active" aria-current="true"' : '') . '>'
                            . osc_esc_html(osc_locale_name()) . '</a>';
                    }
                    $sf_locales = ob_get_clean();
                    if ($sf_locale_name === '') { $sf_locale_name = __('Language', 'storefront'); }
                    // A handful stay inline; more than that would swamp the legal row, so
                    // collapse into a disclosure menu labelled with the current language.
                    if (osc_count_web_enabled_locales() > 4) { ?>
                        <details class="sf-footer__lang-menu">
                            <summary aria-label="<?php echo osc_esc_html(__('Change language', 'storefront')); ?>">
                                <?php echo storefront_icon('globe', 15); ?>
                                <span><?php echo osc_esc_html($sf_locale_name); ?></span>
                                <?php echo storefront_icon('chevron-down', 14); ?>
                            </summary>
                            <div class="sf-footer__lang-list" role="menu"><?php echo $sf_locales; ?></div>
                        </details>
                    <?php } else { ?>
                        <nav class="sf-footer__lang" aria-label="<?php echo osc_esc_html(__('Language', 'storefront')); ?>">
                            <span class="sf-footer__lang-label"><?php _e('Language', 'storefront'); ?></span>
                            <?php echo $sf_locales; ?>
                        </nav>
                    <?php }
                } ?>

                <a class="sf-footer__top" href="#top">
                    <?php _e('Back to top', 'storefront'); ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"></path><path d="m5 12 7-7 7 7"></path></svg>
                </a>
            </div>
        </div>
    </div>
</footer>

<?php if (osc_is_web_user_logged_in()) {
    // Shared confirmation dialog for destructive owner actions (delete a listing or
    // comment). One branded <dialog> the core JS fills per click with the target's
    // name, so an irreversible action names exactly what it will remove — the trust
    // moment the browser's own confirm() can't carry. Only rendered for signed-in
    // users, since every destructive link that uses it requires login. ?>
<dialog id="sf-confirm" class="sf-confirm" aria-labelledby="sf-confirm-title">
    <form method="dialog" class="sf-confirm__box">
        <h2 class="sf-confirm__title" id="sf-confirm-title"><?php _e('Delete this?', 'storefront'); ?></h2>
        <p class="sf-confirm__msg" data-confirm-msg></p>
        <div class="sf-confirm__actions">
            <button type="submit" class="sf-btn sf-btn--ghost" value="cancel"><?php _e('Cancel', 'storefront'); ?></button>
            <a class="sf-btn sf-btn--danger" data-confirm-go href="#"><?php _e('Delete', 'storefront'); ?></a>
        </div>
    </form>
</dialog>
<?php } ?>

<?php osc_run_hook('footer'); ?>
</body>
</html>
