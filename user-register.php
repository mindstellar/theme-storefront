<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
osc_current_web_theme_path('common/header.php');
?>
<section class="sf-authcard">
    <h1 style="font-size:28px; margin:0 0 var(--space-2);"><?php _e('Create your account', 'storefront'); ?></h1>
    <p class="text-muted" style="font-size:14px; margin-bottom:var(--space-4);"><?php _e('Post listings, save searches, and message sellers — free.', 'storefront'); ?></p>

    <form name="register" action="<?php echo osc_base_url(true); ?>" method="post">
        <input type="hidden" name="page" value="register" />
        <input type="hidden" name="action" value="register_post" />
        <ul id="error_list"></ul>
        <div class="sf-field">
            <label for="name"><?php _e('Name', 'storefront'); ?></label>
            <?php UserForm::name_text(); ?>
        </div>
        <div class="sf-field">
            <label for="email"><?php _e('E-mail', 'storefront'); ?></label>
            <?php UserForm::email_text(); ?>
        </div>
        <div class="sf-field">
            <label for="password"><?php _e('Password', 'storefront'); ?></label>
            <?php UserForm::password_text(); ?>
        </div>
        <div class="sf-field">
            <label for="password-2"><?php _e('Repeat password', 'storefront'); ?></label>
            <?php UserForm::check_password_text(); ?>
        </div>
        <?php osc_run_hook('user_register_form'); ?>
        <?php if (osc_captcha_enabled()) { ?>
        <div class="sf-field">
            <?php osc_show_captcha('register'); ?>
        </div>
        <?php } ?>
        <div class="sf-form__actions">
            <button type="submit" class="sf-btn sf-btn--primary sf-btn--block" style="justify-content:center;"><?php _e('Create account', 'storefront'); ?></button>
        </div>
        <p class="sf-form__links">
            <?php printf(
                /* translators: %s is a "Log in" link. */
                osc_esc_html(__('Already have an account? %s', 'storefront')),
                '<a href="' . osc_esc_html(osc_user_login_url()) . '">' . osc_esc_html(__('Log in', 'storefront')) . '</a>'
            ); ?>
        </p>
    </form>
</section>
<?php osc_current_web_theme_path('common/footer.php'); ?>
