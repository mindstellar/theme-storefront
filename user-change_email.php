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
        <?php osc_current_web_theme_path('common/account-settings-nav.php'); ?>
        <section class="sf-section sf-form">
            <h2 class="sf-section__title"><?php _e('Change e-mail', 'storefront'); ?></h2>
            <ul id="error_list"></ul>
            <form id="change-email" action="<?php echo osc_base_url(true); ?>" method="post">
                <input type="hidden" name="page" value="user" />
                <input type="hidden" name="action" value="change_email_post" />
                <div class="sf-field">
                    <label><?php _e('Current e-mail', 'storefront'); ?></label>
                    <p><?php echo osc_esc_html(osc_logged_user_email()); ?></p>
                </div>
                <div class="sf-field">
                    <label for="new_email"><?php _e('New e-mail', 'storefront'); ?> *</label>
                    <input class="sf-input" type="email" name="new_email" id="new_email" value="" />
                </div>
                <div class="sf-form__actions">
                    <button type="submit" class="sf-btn sf-btn--primary"><?php _e('Update', 'storefront'); ?></button>
                </div>
            </form>
        </section>
    </div>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
