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
            <h2 class="sf-section__title"><?php _e('Change password', 'storefront'); ?></h2>
            <ul id="error_list"></ul>
            <form action="<?php echo osc_base_url(true); ?>" method="post">
                <input type="hidden" name="page" value="user" />
                <input type="hidden" name="action" value="change_password_post" />
                <div class="sf-field">
                    <label for="password"><?php _e('Current password', 'storefront'); ?> *</label>
                    <input class="sf-input" type="password" name="password" id="password" value="" autocomplete="off" />
                </div>
                <div class="sf-field">
                    <label for="new_password"><?php _e('New password', 'storefront'); ?> *</label>
                    <input class="sf-input" type="password" name="new_password" id="new_password" value="" autocomplete="off" />
                </div>
                <div class="sf-field">
                    <label for="new_password2"><?php _e('Repeat new password', 'storefront'); ?> *</label>
                    <input class="sf-input" type="password" name="new_password2" id="new_password2" value="" autocomplete="off" />
                </div>
                <div class="sf-form__actions">
                    <button type="submit" class="sf-btn sf-btn--primary"><?php _e('Update', 'storefront'); ?></button>
                </div>
            </form>
        </section>
    </div>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
