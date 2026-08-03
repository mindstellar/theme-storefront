<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
osc_current_web_theme_path('common/header.php');
?>
<section class="sf-authcard">
    <h1 style="font-size:28px; margin:0 0 var(--space-2);"><?php _e('Welcome back', 'storefront'); ?></h1>
    <p class="text-muted" style="font-size:14px; margin-bottom:var(--space-4);"><?php _e('Log in to manage your listings and messages.', 'storefront'); ?></p>

    <form action="<?php echo osc_base_url(true); ?>" method="post">
        <input type="hidden" name="page" value="login" />
        <input type="hidden" name="action" value="login_post" />
        <div class="sf-field">
            <label for="email"><?php _e('E-mail', 'storefront'); ?></label>
            <?php UserForm::email_login_text(); ?>
        </div>
        <div class="sf-field">
            <label for="password"><?php _e('Password', 'storefront'); ?></label>
            <?php UserForm::password_login_text(); ?>
        </div>
        <?php if (osc_captcha_enabled()) { ?>
        <div class="sf-field">
            <?php osc_show_captcha('login'); ?>
        </div>
        <?php } ?>
        <div class="sf-field sf-field--check">
            <label for="remember"><?php UserForm::rememberme_login_checkbox(); ?> <?php _e('Remember me', 'storefront'); ?></label>
        </div>
        <div class="sf-form__actions">
            <button type="submit" class="sf-btn sf-btn--primary sf-btn--block" style="justify-content:center;"><?php _e('Log in', 'storefront'); ?></button>
        </div>
        <p class="sf-form__links">
            <a href="<?php echo osc_register_account_url(); ?>"><?php _e('Register for a free account', 'storefront'); ?></a>
            <a href="<?php echo osc_recover_user_password_url(); ?>"><?php _e('Forgot password?', 'storefront'); ?></a>
        </p>
    </form>
</section>
<?php osc_current_web_theme_path('common/footer.php'); ?>
