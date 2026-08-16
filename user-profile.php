<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Account profile editor. The form contract is core's: it POSTs to the base URL
 * with page=user + action=profile_post and the exact field names UserForm emits.
 * The only additions are presentation (grouped sections) and the avatar field —
 * a file input named `avatar` plus a `remove_avatar` toggle, which CWebUser reads
 * when the `enabled_user_avatars` preference is on. The form therefore MUST be
 * multipart/form-data, or the browser posts the fields without the file.
 */
// The avatar editor (live file preview) lives on this page.
storefront_enqueue_bundle('account');
osc_current_web_theme_path('common/header.php');
$sf_user = osc_user();

$sf_avatars = sf_avatars_enabled();
$sf_has_av  = sf_has_avatar();
$sf_kb      = function_exists('osc_max_size_kb') ? (int) osc_max_size_kb() : 0;
$sf_maxsize = $sf_kb >= 1024 ? round($sf_kb / 1024, 1) . ' MB' : $sf_kb . ' KB';
?>
<div class="sf-account">
    <?php osc_current_web_theme_path('common/account-header.php'); ?>
    <div class="sf-account__main">
        <?php osc_current_web_theme_path('common/account-settings-nav.php'); ?>
        <section class="sf-section sf-form">
            <h2 class="sf-section__title"><?php _e('Edit profile', 'storefront'); ?></h2>
            <p class="sf-form__lede"><?php _e('This is how buyers and sellers see you across the marketplace.', 'storefront'); ?></p>
            <ul id="error_list"></ul>

            <form action="<?php echo osc_base_url(true); ?>" method="post" enctype="multipart/form-data" class="sf-profile-form">
                <input type="hidden" name="page" value="user" />
                <input type="hidden" name="action" value="profile_post" />

                <?php if ($sf_avatars) { ?>
                <fieldset class="sf-form__group sf-form__group--avatar">
                    <legend class="sf-form__legend"><?php _e('Photo', 'storefront'); ?></legend>
                    <div class="sf-avatar-edit" data-avatar>
                        <span class="sf-avatar sf-avatar--lg" id="sf-avatar" data-avatar-preview data-monogram="<?php echo osc_esc_html(sf_user_monogram()); ?>">
                            <?php if ($sf_has_av) { ?>
                                <img src="<?php echo osc_esc_html(osc_user_avatar_url(osc_logged_user_id(), 'normal')); ?>" alt="" />
                            <?php } else { ?>
                                <span class="sf-avatar__monogram"><?php echo osc_esc_html(sf_user_monogram()); ?></span>
                            <?php } ?>
                        </span>
                        <div class="sf-avatar-edit__body">
                            <p class="sf-avatar-edit__hint"><?php printf(osc_esc_html(__('JPG, PNG, GIF or WebP — up to %s. Square photos look best.', 'storefront')), osc_esc_html($sf_maxsize)); ?></p>
                            <div class="sf-avatar-edit__actions">
                                <label class="sf-btn sf-btn--secondary" for="sf-avatar-input"><?php echo storefront_icon('image', 15); ?><span><?php echo $sf_has_av ? osc_esc_html(__('Change photo', 'storefront')) : osc_esc_html(__('Upload photo', 'storefront')); ?></span></label>
                                <input type="file" name="avatar" id="sf-avatar-input" class="sf-sr-only" accept="image/jpeg,image/png,image/gif,image/webp" data-avatar-input />
                                <?php if ($sf_has_av) { ?>
                                    <label class="sf-avatar-edit__remove"><input type="checkbox" name="remove_avatar" value="1" data-avatar-remove /> <?php _e('Remove photo', 'storefront'); ?></label>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </fieldset>
                <?php } ?>

                <fieldset class="sf-form__group">
                    <legend class="sf-form__legend"><?php _e('Your details', 'storefront'); ?></legend>
                    <div class="sf-field-grid">
                        <div class="sf-field">
                            <label for="name"><?php _e('Name', 'storefront'); ?></label>
                            <?php UserForm::name_text(osc_user()); ?>
                        </div>
                        <div class="sf-field">
                            <label for="user_type"><?php _e('Account type', 'storefront'); ?></label>
                            <?php UserForm::is_company_select(osc_user()); ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="sf-form__group">
                    <legend class="sf-form__legend"><?php _e('Contact', 'storefront'); ?></legend>
                    <div class="sf-field-grid">
                        <div class="sf-field">
                            <label for="phoneMobile"><?php _e('Cell phone', 'storefront'); ?></label>
                            <?php UserForm::mobile_text(osc_user()); ?>
                        </div>
                        <div class="sf-field">
                            <label for="phoneLand"><?php _e('Phone', 'storefront'); ?></label>
                            <?php UserForm::phone_land_text(osc_user()); ?>
                        </div>
                    </div>
                    <div class="sf-field">
                        <label for="webSite"><?php _e('Website', 'storefront'); ?></label>
                        <?php UserForm::website_text(osc_user()); ?>
                    </div>
                </fieldset>

                <fieldset class="sf-form__group">
                    <legend class="sf-form__legend"><?php _e('Location', 'storefront'); ?></legend>
                    <div class="sf-field-grid">
                        <div class="sf-field">
                            <label for="country"><?php _e('Country', 'storefront'); ?></label>
                            <?php UserForm::country_select(osc_get_countries(), osc_user()); ?>
                        </div>
                        <div class="sf-field">
                            <label for="region"><?php _e('Region', 'storefront'); ?></label>
                            <?php UserForm::region_select(osc_get_regions(), osc_user()); ?>
                        </div>
                        <div class="sf-field">
                            <label for="city"><?php _e('City', 'storefront'); ?></label>
                            <?php UserForm::city_select(osc_get_cities(), osc_user()); ?>
                        </div>
                        <div class="sf-field">
                            <label for="city_area"><?php _e('City area', 'storefront'); ?></label>
                            <?php UserForm::city_area_text(osc_user()); ?>
                        </div>
                    </div>
                    <div class="sf-field">
                        <label for="address"><?php _e('Address', 'storefront'); ?></label>
                        <?php UserForm::address_text(osc_user()); ?>
                    </div>
                </fieldset>

                <fieldset class="sf-form__group">
                    <legend class="sf-form__legend"><?php _e('About you', 'storefront'); ?></legend>
                    <div class="sf-field">
                        <label for="s_info"><?php _e('Description', 'storefront'); ?></label>
                        <?php UserForm::info_textarea('s_info', osc_locale_code(), @$sf_user['locale'][osc_locale_code()]['s_info']); ?>
                    </div>
                </fieldset>

                <?php osc_run_hook('user_profile_form', osc_user()); ?>

                <div class="sf-form__actions">
                    <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg"><?php _e('Save changes', 'storefront'); ?></button>
                </div>
                <?php osc_run_hook('user_form', osc_user()); ?>
            </form>
        </section>

        <?php
        /*
         * Your data — the two things a person can ask for about their own account.
         *
         * Both are guarded on the core function existing rather than on a version number:
         * the download arrived in 6.2.0 and this theme still supports 6.0, so on an older
         * install the whole section simply is not drawn. osc_user_export_url() itself
         * returns '' when nobody is signed in, which is belt to that braces.
         */
        $sf_export_url = function_exists('osc_user_export_url') ? osc_user_export_url() : '';
        $sf_secret     = isset($sf_user['s_secret']) ? $sf_user['s_secret'] : '';
        if ($sf_export_url !== '' || $sf_secret !== '') { ?>
        <section class="sf-section sf-form sf-account__data">
            <h2 class="sf-section__title"><?php _e('Your data', 'storefront'); ?></h2>

            <?php if ($sf_export_url !== '') { ?>
                <div class="sf-field">
                    <p class="sf-form__lede"><?php _e(
                        'Download everything this site holds about you — your profile, listings, comments, saved searches and order history — as a JSON file.',
                        'storefront'
                    ); ?></p>
                    <a class="sf-btn sf-btn--secondary" href="<?php echo osc_esc_html($sf_export_url); ?>" rel="nofollow">
                        <?php echo storefront_icon('download', 15); ?><span><?php _e('Download my data', 'storefront'); ?></span>
                    </a>
                </div>
            <?php } ?>

            <?php if ($sf_secret !== '') { ?>
                <div class="sf-field sf-field--danger">
                    <p class="sf-form__lede"><?php _e(
                        'Deleting your account removes your profile, listings, comments and saved searches. It cannot be undone.',
                        'storefront'
                    ); ?></p>
                    <button type="button" class="sf-btn sf-btn--danger" data-dialog-open="sf-dialog-delete-account">
                        <?php _e('Delete my account', 'storefront'); ?>
                    </button>
                </div>
            <?php } ?>
        </section>
        <?php } ?>
    </div>
</div>

<?php if ($sf_secret !== '') { ?>
<dialog class="sf-dialog" id="sf-dialog-delete-account" aria-labelledby="sf-dialog-delete-account-title">
    <div class="sf-dialog__head">
        <h2 class="sf-dialog__title" id="sf-dialog-delete-account-title"><?php _e('Delete your account?', 'storefront'); ?></h2>
        <button type="button" class="sf-dialog__close" data-dialog-close aria-label="<?php echo osc_esc_html(__('Close', 'storefront')); ?>"><?php echo storefront_icon('x', 18); ?></button>
    </div>
    <div class="sf-dialog__body">
        <p><?php _e(
            'Your profile, listings, comments and saved searches will be removed. Records of anything you paid for are kept, because the site has its own obligation to hold them.',
            'storefront'
        ); ?></p>
        <?php if ($sf_export_url !== '') { ?>
            <p><?php _e('If you want a copy of your data, download it before continuing.', 'storefront'); ?></p>
        <?php } ?>
        <div class="sf-dialog__actions">
            <button type="button" class="sf-btn sf-btn--ghost" data-dialog-close><?php _e('Keep my account', 'storefront'); ?></button>
            <a class="sf-btn sf-btn--danger"
               href="<?php echo osc_esc_html(osc_base_url(true) . '?page=user&amp;action=delete&amp;id=' . (int) osc_logged_user_id() . '&amp;secret=' . rawurlencode($sf_secret)); ?>"
               rel="nofollow"><?php _e('Delete my account', 'storefront'); ?></a>
        </div>
    </div>
</dialog>
<?php } ?>
<?php osc_current_web_theme_path('common/footer.php'); ?>
