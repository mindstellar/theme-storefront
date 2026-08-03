<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
osc_current_web_theme_path('common/header.php');
$sf_acct = sf_account_user();
$sf_username = ($sf_acct && !empty($sf_acct['s_username'])) ? $sf_acct['s_username'] : '';
?>
<div class="sf-account">
    <?php osc_current_web_theme_path('common/account-header.php'); ?>
    <div class="sf-account__main">
        <?php osc_current_web_theme_path('common/account-settings-nav.php'); ?>
        <section class="sf-section sf-form">
            <h2 class="sf-section__title"><?php _e('Change username', 'storefront'); ?></h2>
            <ul id="error_list"></ul>
            <form action="<?php echo osc_base_url(true); ?>" method="post" id="change-username">
                <input type="hidden" name="page" value="user" />
                <input type="hidden" name="action" value="change_username_post" />
                <?php if ($sf_username !== '') { ?>
                <div class="sf-field">
                    <label><?php _e('Current username', 'storefront'); ?></label>
                    <p><?php echo osc_esc_html($sf_username); ?></p>
                </div>
                <?php } ?>
                <div class="sf-field">
                    <label for="s_username"><?php _e('New username', 'storefront'); ?> *</label>
                    <input class="sf-input" type="text" name="s_username" id="s_username" value="" />
                </div>
                <div class="sf-form__actions">
                    <button type="submit" class="sf-btn sf-btn--primary"><?php _e('Update', 'storefront'); ?></button>
                </div>
            </form>
        </section>
    </div>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
