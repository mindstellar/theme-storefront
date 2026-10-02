<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Partial: the "message the seller" form. Rendered inside the contact <dialog>
 * in item.php, which only includes it when the visitor may actually contact the
 * seller (not expired, not the owner, and logged in when registration is
 * required). The listing context + safety note are here on purpose: on a phone
 * the dialog covers the page, so the price and the advice must travel with it.
 *
 * CONTRACT (unchanged, read by core's item controller): the field names
 * yourName / yourEmail / phoneNumber / message, and the hidden action=contact_post
 * + page=item + id inputs. Only presentation is ours.
 *
 * Core also renders this file on its own for ?page=item&action=contact; then it
 * draws the site chrome and a page title around the form itself.
 */
$sf_contact_page = !defined('STOREFRONT_CONTACT_IN_DIALOG');
if ($sf_contact_page) {
    osc_current_web_theme_path('common/header.php'); ?>
<section class="sf-form sf-contact-page">
    <p class="sf-crumbs"><a href="<?php echo osc_esc_html(osc_item_url()); ?>">&larr; <?php _e('Back to listing', 'storefront'); ?></a></p>
    <h1 class="sf-contact-page__title"><?php _e('Message seller', 'storefront'); ?></h1>
<?php }
?>
<p class="sf-dialog__context">
    <span class="sf-dialog__context-title"><?php echo osc_esc_html(osc_item_title()); ?></span>
    <?php if (storefront_price_state() === 'price') { ?>
        <span class="sf-dialog__context-price"><?php echo osc_esc_html(osc_item_formated_price()); ?></span>
    <?php } ?>
</p>

<p class="sf-dialog__note">
    <?php echo storefront_icon('info', 15); ?>
    <span><?php _e('The seller will see your name and email. Never pay in advance — meet in a public place and inspect the item first.', 'storefront'); ?></span>
</p>

<?php $sf_contact_error = (string) Session::newInstance()->_getForm('contact_error');
if ($sf_contact_error !== '') { ?>
    <p class="sf-form-error" role="alert"><?php echo osc_esc_html($sf_contact_error); ?></p>
<?php } ?>
<ul id="error_list"></ul>
<form action="<?php echo osc_base_url(true); ?>" method="post" name="contact_form" id="contact_form" <?php if (osc_item_attachment()) { echo 'enctype="multipart/form-data"'; } ?>>
    <?php osc_prepare_user_info(); ?>
    <input type="hidden" name="action" value="contact_post" />
    <input type="hidden" name="page" value="item" />
    <input type="hidden" name="id" value="<?php echo osc_item_id(); ?>" />

    <div class="sf-field">
        <label for="yourName"><?php _e('Your name', 'storefront'); ?></label>
        <?php ContactForm::your_name(); ?>
    </div>
    <div class="sf-field">
        <label for="yourEmail"><?php _e('Your e-mail address', 'storefront'); ?></label>
        <?php ContactForm::your_email(); ?>
    </div>
    <div class="sf-field">
        <label for="phoneNumber"><?php _e('Phone number', 'storefront'); ?> <span class="sf-muted">(<?php _e('optional', 'storefront'); ?>)</span></label>
        <?php ContactForm::your_phone_number(); ?>
    </div>
    <div class="sf-field">
        <label for="message"><?php _e('Message', 'storefront'); ?></label>
        <?php ContactForm::your_message(); ?>
        <p class="sf-field__hint"><?php _e('Sellers reply faster when they know what you want to ask.', 'storefront'); ?></p>
    </div>
    <?php if (osc_item_attachment()) { ?>
    <div class="sf-field">
        <label for="attachment"><?php _e('Attachment', 'storefront'); ?></label>
        <?php ContactForm::your_attachment(); ?>
    </div>
    <?php } ?>

    <div class="sf-field">
        <?php osc_run_hook('item_contact_form', osc_item_id()); ?>
        <?php osc_show_captcha(); ?>
    </div>
    <div class="sf-dialog__actions">
        <?php if ($sf_contact_page) { ?>
            <a class="sf-btn sf-btn--ghost" href="<?php echo osc_esc_html(osc_item_url()); ?>"><?php _e('Cancel', 'storefront'); ?></a>
        <?php } else { ?>
            <button type="button" class="sf-btn sf-btn--ghost" data-dialog-close><?php _e('Cancel', 'storefront'); ?></button>
        <?php } ?>
        <button type="submit" class="sf-btn sf-btn--primary"><?php _e('Send message', 'storefront'); ?></button>
    </div>
</form>
<?php if ($sf_contact_page) { ?>
</section>
<?php osc_current_web_theme_path('common/footer.php');
}
