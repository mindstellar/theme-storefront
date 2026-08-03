<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Partial: listing-page reporting control. Included inside the seller rail.
 */
?>
<?php if (!osc_is_web_user_logged_in() || osc_logged_user_id() != osc_item_user_id()) { ?>
<form class="sf-report" action="<?php echo osc_base_url(true); ?>" method="post" name="mask_as_form" id="mask_as_form">
    <input type="hidden" name="id" value="<?php echo osc_item_id(); ?>" />
    <input type="hidden" name="action" value="mark" />
    <input type="hidden" name="page" value="item" />
    <div class="sf-field">
        <label for="as"><?php _e('Report this listing', 'storefront'); ?></label>
        <select class="sf-select" name="as" id="as">
            <option value="spam"><?php _e('Spam or misleading listing', 'storefront'); ?></option>
            <option value="badcat"><?php _e('Wrong category', 'storefront'); ?></option>
            <option value="repeated"><?php _e('Duplicated listing', 'storefront'); ?></option>
            <option value="expired"><?php _e('Expired', 'storefront'); ?></option>
            <option value="offensive"><?php _e('Offensive content', 'storefront'); ?></option>
        </select>
    </div>
    <?php
    // Shown only when core will verify it (report-captcha toggle AND an active provider).
    // Guarded so older cores without the preference never render a challenge.
    if (function_exists('osc_recaptcha_reports_enabled') && osc_recaptcha_reports_enabled()
        && function_exists('osc_captcha_enabled') && osc_captcha_enabled()) { ?>
    <div class="sf-field"><?php osc_show_captcha('report'); ?></div>
    <?php } ?>
    <button type="submit" class="sf-btn sf-btn--secondary sf-btn--block"><?php _e('Submit report', 'storefront'); ?></button>
</form>
<?php } ?>
