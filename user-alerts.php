<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
osc_current_web_theme_path('common/header.php');
?>
<div class="sf-account">
    <?php osc_current_web_theme_path('common/account-header.php'); ?>
    <div class="sf-account__main">
        <section class="sf-section">
            <?php if (osc_count_alerts() == 0) { ?>
                <div class="sf-empty-state">
                    <span class="sf-empty-state__icon" aria-hidden="true"><?php echo storefront_icon('bell', 30); ?></span>
                    <h2 class="sf-empty-state__title"><?php _e('No saved alerts yet', 'storefront'); ?></h2>
                    <p class="sf-empty-state__text"><?php _e('Save a search and we\'ll email you the moment a matching listing goes up — no need to keep checking back.', 'storefront'); ?></p>
                    <div class="sf-empty-state__actions">
                        <a class="sf-btn sf-btn--primary sf-btn--lg" href="<?php echo osc_esc_html(osc_search_url()); ?>">
                            <?php echo storefront_icon('search', 16); ?><span><?php _e('Browse listings', 'storefront'); ?></span>
                        </a>
                    </div>
                </div>
            <?php } else { ?>
                <h2 class="sf-section__title"><?php _e('Alerts', 'storefront'); ?></h2>
                <?php
                $sf_i = 1;
                while (osc_has_alerts()) { ?>
                <div class="sf-alert">
                    <div class="sf-results__head">
                        <h2 class="sf-results__title"><?php _e('Alert', 'storefront'); ?> <?php echo (int) $sf_i; ?></h2>
                        <a class="sf-btn sf-btn--ghost"
                           onclick="return confirm('<?php echo osc_esc_js(__("This action can't be undone. Are you sure you want to continue?", 'storefront')); ?>');"
                           href="<?php echo osc_user_unsubscribe_alert_url(); ?>"><?php _e('Delete this alert', 'storefront'); ?></a>
                    </div>
                    <?php osc_current_web_theme_path('common/loop.php'); ?>
                    <?php if (osc_count_items() == 0) { ?>
                        <p class="sf-empty">0 <?php _e('listings', 'storefront'); ?></p>
                    <?php } ?>
                </div>
            <?php $sf_i++; }
            } ?>
        </section>
    </div>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
