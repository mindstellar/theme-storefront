<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
osc_current_web_theme_path('common/header.php');
?>
<section class="sf-section sf-form sf-form--narrow">
    <h1 class="sf-section__title"><?php _e('Recover your password', 'storefront'); ?></h1>

    <form action="<?php echo osc_base_url(true); ?>" method="post">
        <input type="hidden" name="page" value="login" />
        <input type="hidden" name="action" value="forgot_post" />
        <input type="hidden" name="userId" value="<?php echo osc_esc_html(Params::getParam('userId')); ?>" />
        <input type="hidden" name="code" value="<?php echo osc_esc_html(Params::getParam('code')); ?>" />
        <div class="sf-field">
            <label for="new_password"><?php _e('New password', 'storefront'); ?></label>
            <input class="sf-input" type="password" name="new_password" value="" autocomplete="off" />
        </div>
        <div class="sf-field">
            <label for="new_password2"><?php _e('Repeat new password', 'storefront'); ?></label>
            <input class="sf-input" type="password" name="new_password2" value="" autocomplete="off" />
        </div>
        <div class="sf-form__actions">
            <button type="submit" class="sf-btn sf-btn--primary"><?php _e('Change password', 'storefront'); ?></button>
        </div>
    </form>
</section>
<?php osc_current_web_theme_path('common/footer.php'); ?>
