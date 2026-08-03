<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Partial: the search "email me new listings" form, loaded by core's
 * osc_alert_form() into the filter rail. Whether it is shown at all — and to
 * whom — is decided in common/search-sidebar.php against core's `alerts_require_login`
 * preference, the same switch core's ajax `alerts` action enforces server-side.
 *
 * Subscription posts to core's ajax `alerts` action (page=ajax&action=alerts)
 * from js/storefront.js — vanilla, no jQuery. The plain no-JS submit falls back
 * to re-running the search, which is harmless. The core default theme rendered a
 * jQuery placeholder-polyfill here that put its own label string in the field as
 * a value; this renders a real <label> + type=email input and keeps the exact
 * field-name contract core reads (`alert`, `email`).
 */
?>
<?php if (function_exists('osc_search_alert_subscribed') && osc_search_alert_subscribed()) { ?>
    <p class="sf-alert-form__done">
        <?php echo storefront_icon('check', 16); ?>
        <span><?php _e('You are subscribed to this search.', 'storefront'); ?></span>
    </p>
<?php } else { ?>
    <form class="sf-alert-form nocsrf" action="<?php echo osc_base_url(true); ?>" method="post" id="sf-alert-form" data-search-alert
          data-wait="<?php echo osc_esc_html(__('Subscribing…', 'storefront')); ?>"
          data-ok="<?php echo osc_esc_html(__('Subscribed. Check your email to confirm.', 'storefront')); ?>"
          data-login="<?php echo osc_esc_html(__('Please sign in to subscribe.', 'storefront')); ?>"
          data-bademail="<?php echo osc_esc_html(__('Enter a valid e-mail address.', 'storefront')); ?>"
          data-fail="<?php echo osc_esc_html(__('Could not subscribe. Please try again.', 'storefront')); ?>">
        <h3 class="sf-filters__label"><?php _e('Get an email alert', 'storefront'); ?></h3>
        <p class="sf-alert-form__hint"><?php _e('We will email you when a new listing matches this search.', 'storefront'); ?></p>

        <?php AlertForm::page_hidden(); ?>
        <?php AlertForm::alert_hidden(); ?>
        <?php AlertForm::user_id_hidden(); ?>

        <?php if (osc_is_web_user_logged_in()) { ?>
            <?php // The address is the account's; core reads it from the session and ignores anything posted. ?>
            <?php AlertForm::email_hidden(); ?>
        <?php } else { ?>
            <div class="sf-field">
                <label class="sf-sr-only" for="sf-alert-email"><?php _e('Your e-mail address', 'storefront'); ?></label>
                <input class="sf-input" type="email" name="alert_email" id="sf-alert-email"
                       autocomplete="email"
                       placeholder="<?php echo osc_esc_html(__('Your e-mail address', 'storefront')); ?>" />
            </div>
        <?php } ?>

        <button type="submit" class="sf-btn sf-btn--secondary sf-btn--block"><?php _e('Subscribe', 'storefront'); ?></button>
        <p class="sf-alert-form__msg" role="status" aria-live="polite" data-alert-msg></p>
    </form>
<?php } ?>
