<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
osc_current_web_theme_path('common/header.php');
?>
<section class="sf-section sf-form">
    <h1 class="sf-section__title"><?php _e('Send to a friend', 'storefront'); ?></h1>

    <ul id="error_list"></ul>
    <form name="sendfriend" action="<?php echo osc_base_url(true); ?>" method="post">
        <input type="hidden" name="action" value="send_friend_post" />
        <input type="hidden" name="page" value="item" />
        <input type="hidden" name="id" value="<?php echo osc_item_id(); ?>" />
        <?php if (osc_is_web_user_logged_in()) { ?>
            <input type="hidden" name="yourName" value="<?php echo osc_esc_html(osc_logged_user_name()); ?>" />
            <input type="hidden" name="yourEmail" value="<?php echo osc_esc_html(osc_logged_user_email()); ?>" />
        <?php } else { ?>
        <div class="sf-field">
            <label for="yourName"><?php _e('Your name', 'storefront'); ?></label>
            <?php SendFriendForm::your_name(); ?>
        </div>
        <div class="sf-field">
            <label for="yourEmail"><?php _e('Your e-mail', 'storefront'); ?></label>
            <?php SendFriendForm::your_email(); ?>
        </div>
        <?php } ?>
        <div class="sf-field">
            <label for="friendName"><?php _e("Your friend's name", 'storefront'); ?></label>
            <?php SendFriendForm::friend_name(); ?>
        </div>
        <div class="sf-field">
            <label for="friendEmail"><?php _e("Your friend's e-mail address", 'storefront'); ?></label>
            <?php SendFriendForm::friend_email(); ?>
        </div>
        <div class="sf-field">
            <label for="subject"><?php _e('Subject (optional)', 'storefront'); ?></label>
            <?php ContactForm::the_subject(); ?>
        </div>
        <div class="sf-field">
            <label for="message"><?php _e('Message', 'storefront'); ?></label>
            <?php SendFriendForm::your_message(); ?>
        </div>
        <div class="sf-field">
            <?php osc_run_hook('contact_form'); ?>
            <?php osc_show_captcha(); ?>
        </div>
        <div class="sf-form__actions">
            <button type="submit" class="sf-btn sf-btn--primary"><?php _e('Send', 'storefront'); ?></button>
            <?php osc_run_hook('admin_contact_form'); ?>
        </div>
    </form>
</section>
<?php osc_current_web_theme_path('common/footer.php'); ?>
