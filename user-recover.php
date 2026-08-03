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
        <input type="hidden" name="action" value="recover_post" />
        <div class="sf-field">
            <label for="email"><?php _e('E-mail', 'storefront'); ?></label>
            <?php UserForm::email_text(); ?>
            <?php osc_show_captcha('recover_password'); ?>
        </div>
        <div class="sf-form__actions">
            <button type="submit" class="sf-btn sf-btn--primary"><?php _e('Send me a new password', 'storefront'); ?></button>
        </div>
    </form>
</section>
<?php osc_current_web_theme_path('common/footer.php'); ?>
