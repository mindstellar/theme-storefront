<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Partial: the contact panel on a public user profile. Shows the message form when
 * the visitor may contact the seller, or a log-in prompt when core gates contact
 * behind an account. Rendered for any visitor who is not the profile's owner.
 */
?>
<?php if (osc_logged_user_id() != osc_user_id()) { ?>
    <?php if ((osc_reg_user_can_contact() && osc_is_web_user_logged_in()) || !osc_reg_user_can_contact()) { ?>
    <aside class="sf-panel">
        <h2 class="sf-seller__head"><?php _e('Contact', 'storefront'); ?></h2>
        <ul id="error_list"></ul>
        <form action="<?php echo osc_base_url(true); ?>" method="post" name="contact_form" id="contact_form">
            <input type="hidden" name="action" value="contact_post" />
            <input type="hidden" name="page" value="user" />
            <input type="hidden" name="id" value="<?php echo osc_user_id(); ?>" />
            <div class="sf-field">
                <label for="yourName"><?php _e('Your name', 'storefront'); ?></label>
                <?php ContactForm::your_name(); ?>
            </div>
            <div class="sf-field">
                <label for="yourEmail"><?php _e('Your email address', 'storefront'); ?></label>
                <?php ContactForm::your_email(); ?>
            </div>
            <div class="sf-field">
                <label for="phoneNumber"><?php _e('Phone number', 'storefront'); ?> (<?php _e('optional', 'storefront'); ?>)</label>
                <?php ContactForm::your_phone_number(); ?>
            </div>
            <div class="sf-field">
                <label for="message"><?php _e('Message', 'storefront'); ?></label>
                <?php ContactForm::your_message(); ?>
            </div>
            <div class="sf-field">
                <?php osc_run_hook('item_contact_form', osc_item_id()); ?>
                <?php osc_show_captcha(); ?>
            </div>
            <div class="sf-form__actions">
                <button type="submit" class="sf-btn sf-btn--primary sf-btn--block"><?php _e('Send message', 'storefront'); ?></button>
            </div>
        </form>
    </aside>
    <?php } else { ?>
    <aside class="sf-panel sf-contact-gate">
        <h2 class="sf-seller__head"><?php _e('Contact', 'storefront'); ?></h2>
        <p class="sf-contact-gate__text"><?php _e('Log in to message this seller — it keeps the conversation to real, registered members.', 'storefront'); ?></p>
        <a class="sf-btn sf-btn--primary sf-btn--block" href="<?php echo osc_esc_html(osc_user_login_url()); ?>"><?php _e('Log in to contact', 'storefront'); ?></a>
        <?php if (osc_user_registration_enabled()) { ?>
            <a class="sf-btn sf-btn--secondary sf-btn--block" href="<?php echo osc_esc_html(osc_register_account_url()); ?>"><?php _e('Create an account', 'storefront'); ?></a>
        <?php } ?>
    </aside>
    <?php } ?>
<?php } ?>
