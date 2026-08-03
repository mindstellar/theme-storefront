<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
osc_current_web_theme_path('common/header.php');
?>
<h1><?php _e('Contact &amp; support', 'storefront'); ?></h1>
<p class="text-muted" style="font-size:15px; max-width:56ch; margin-bottom:var(--space-6);">
    <?php _e("Questions about your account, a listing, or something not right? Send the team a message and we'll get back to you.", 'storefront'); ?>
</p>

<div style="display:flex; gap:var(--space-8); align-items:flex-start; flex-wrap:wrap;">
    <form class="sf-form" name="contact_form" action="<?php echo osc_base_url(true); ?>" method="post" style="flex:1; min-width:320px;">
        <input type="hidden" name="page" value="contact" />
        <input type="hidden" name="action" value="contact_post" />
        <ul id="error_list"></ul>
        <div class="sf-field">
            <label for="yourName"><?php _e('Your name', 'storefront'); ?> (<?php _e('optional', 'storefront'); ?>)</label>
            <?php ContactForm::your_name(); ?>
        </div>
        <div class="sf-field">
            <label for="yourEmail"><?php _e('Your email address', 'storefront'); ?></label>
            <?php ContactForm::your_email(); ?>
        </div>
        <div class="sf-field">
            <label for="subject"><?php _e('Subject', 'storefront'); ?> (<?php _e('optional', 'storefront'); ?>)</label>
            <?php ContactForm::the_subject(); ?>
        </div>
        <div class="sf-field">
            <label for="message"><?php _e('Message', 'storefront'); ?></label>
            <?php ContactForm::your_message(); ?>
        </div>
        <div class="sf-field">
            <?php osc_run_hook('contact_form'); ?>
            <?php osc_show_captcha(); ?>
        </div>
        <div class="sf-form__actions">
            <button type="submit" class="sf-btn sf-btn--primary"><?php _e('Send message', 'storefront'); ?></button>
            <?php osc_run_hook('admin_contact_form'); ?>
        </div>
    </form>

    <aside style="flex:0 0 300px; min-width:280px; display:flex; flex-direction:column; gap:var(--space-4);">
        <div class="sf-panel" style="padding:var(--space-4);">
            <h2 style="font-size:13px; letter-spacing:0.08em; text-transform:uppercase; margin:0 0 var(--space-2);"><?php _e('Support hours', 'storefront'); ?></h2>
            <div style="font-size:14px;"><?php _e('We reply as soon as we can', 'storefront'); ?></div>
            <div class="text-muted" style="font-size:12px;"><?php _e('Typically within 24 hours', 'storefront'); ?></div>
        </div>
        <div class="sf-panel" style="padding:var(--space-4);">
            <h2 style="font-size:13px; letter-spacing:0.08em; text-transform:uppercase; margin:0 0 var(--space-3);"><?php _e('Quick links', 'storefront'); ?></h2>
            <div style="display:flex; flex-direction:column; gap:8px; font-size:14px;">
                <a href="<?php echo osc_item_post_url_in_category(); ?>"><?php _e('Post a free listing', 'storefront'); ?> &rarr;</a>
                <a href="<?php echo osc_search_show_all_url(); ?>"><?php _e('Browse listings', 'storefront'); ?> &rarr;</a>
                <?php osc_reset_static_pages(); while (osc_has_static_pages()) { ?>
                    <a href="<?php echo osc_static_page_url(); ?>"><?php echo osc_esc_html(osc_static_page_title()); ?> &rarr;</a>
                <?php } ?>
            </div>
        </div>
    </aside>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
