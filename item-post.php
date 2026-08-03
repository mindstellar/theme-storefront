<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

$sf_action = 'item_add_post';
$sf_edit = false;
if (Params::getParam('action') === 'item_edit') {
    $sf_action = 'item_edit_post';
    $sf_edit = true;
}
$sf_loc = $sf_edit ? osc_item() : osc_user();

// Region/City location autocomplete + core's vanilla photo uploader for this form
// (shared by publish and edit, which renders through this view).
storefront_enqueue_search();
osc_enqueue_script('osc-uploader');
osc_enqueue_style('osc-uploader');
osc_current_web_theme_path('common/header.php');
?>
<section class="sf-form">
    <?php if (!$sf_edit) { ?>
        <div class="sf-hero__kicker" style="margin-bottom:var(--space-2);"><?php _e('Free to post', 'storefront'); ?></div>
    <?php } ?>
    <h1>
        <?php echo $sf_edit ? osc_esc_html(__('Edit your listing', 'storefront')) : osc_esc_html(__('Post a listing', 'storefront')); ?>
    </h1>
    <p class="text-muted" style="font-size:15px; margin-bottom:var(--space-6);">
        <?php _e('Fill in the details below. Listings are reviewed and go live within minutes.', 'storefront'); ?>
    </p>

    <ul id="error_list"></ul>
    <form name="item" action="<?php echo osc_base_url(true); ?>" method="post" enctype="multipart/form-data" id="item-post">
        <input type="hidden" name="action" value="<?php echo osc_esc_html($sf_action); ?>" />
        <input type="hidden" name="page" value="item" />
        <?php if ($sf_edit) { ?>
            <input type="hidden" name="id" value="<?php echo osc_item_id(); ?>" />
            <input type="hidden" name="secret" value="<?php echo osc_esc_html(osc_item_secret()); ?>" />
        <?php } ?>

        <h2 class="sf-form__legend"><?php _e('General information', 'storefront'); ?></h2>

        <div class="sf-field">
            <label for="select_1"><?php _e('Category', 'storefront'); ?></label>
            <?php ItemForm::category_select(null, null, __('Select a category', 'storefront')); ?>
        </div>
        <div class="sf-field">
            <label for="title[<?php echo osc_current_user_locale(); ?>]"><?php _e('Title', 'storefront'); ?></label>
            <?php ItemForm::title_input('title', osc_current_user_locale(), osc_esc_html(osc_item_title())); ?>
        </div>
        <div class="sf-field">
            <label for="description[<?php echo osc_current_user_locale(); ?>]"><?php _e('Description', 'storefront'); ?></label>
            <?php ItemForm::description_textarea('description', osc_current_user_locale(), osc_esc_html(osc_item_description())); ?>
        </div>
        <?php if (osc_price_enabled_at_items()) { ?>
        <div class="sf-field control-group-price">
            <label for="price"><?php _e('Price', 'storefront'); ?></label>
            <?php ItemForm::price_input_text(); ?>
            <?php ItemForm::currency_select(); ?>
        </div>
        <?php } ?>

        <?php if (osc_images_enabled_at_items()) { ?>
        <div class="sf-field">
            <?php ItemForm::ajax_photos(); ?>
        </div>
        <?php } ?>

        <h2 class="sf-form__legend"><?php _e('Listing location', 'storefront'); ?></h2>
        <div class="sf-field">
            <label for="country"><?php _e('Country', 'storefront'); ?></label>
            <?php ItemForm::country_select(osc_get_countries(), $sf_loc); ?>
        </div>
        <?php // Region/City are core-rendered plain #region/#city text inputs. This theme is
              // jQuery-free, so core's own autocomplete never binds; instead we declare the same
              // data-ac config the search rail uses (js/storefront.js promotes it onto the inner
              // input and wires the shared vanilla autocomplete against core's location_* ajax). ?>
        <div class="sf-field" data-ac-field="location_regions"
             data-ac-url="<?php echo osc_esc_html(osc_base_url(true)); ?>"
             data-ac-target="#regionId" data-ac-scope="#countryId" data-ac-scope-param="country"
             data-ac-clears="#city,#cityId">
            <label for="region"><?php _e('Region', 'storefront'); ?></label>
            <?php ItemForm::region_text($sf_loc); ?>
        </div>
        <div class="sf-field" data-ac-field="location_cities"
             data-ac-url="<?php echo osc_esc_html(osc_base_url(true)); ?>"
             data-ac-target="#cityId" data-ac-scope="#regionId" data-ac-scope-param="region">
            <label for="city"><?php _e('City', 'storefront'); ?></label>
            <?php ItemForm::city_text($sf_loc); ?>
        </div>
        <div class="sf-field">
            <label for="cityArea"><?php _e('City area', 'storefront'); ?></label>
            <?php ItemForm::city_area_text($sf_loc); ?>
        </div>
        <div class="sf-field">
            <label for="address"><?php _e('Address', 'storefront'); ?></label>
            <?php ItemForm::address_text($sf_loc); ?>
        </div>

        <?php if (!osc_is_web_user_logged_in()) { ?>
        <h2 class="sf-form__legend"><?php _e("Seller's information", 'storefront'); ?></h2>
        <div class="sf-field">
            <label for="contactName"><?php _e('Name', 'storefront'); ?></label>
            <?php ItemForm::contact_name_text(); ?>
        </div>
        <div class="sf-field">
            <label for="contactEmail"><?php _e('E-mail', 'storefront'); ?></label>
            <?php ItemForm::contact_email_text(); ?>
        </div>
        <div class="sf-field sf-field--check">
            <label for="showEmail"><?php ItemForm::show_email_checkbox(); ?> <?php _e('Show e-mail on the listing page', 'storefront'); ?></label>
        </div>
        <?php } ?>

        <?php
        if ($sf_edit) {
            ItemForm::plugin_edit_item();
        } else {
            ItemForm::plugin_post_item();
        }
        ?>

        <?php
        // Show the challenge exactly when core will verify it: the item-post toggle
        // AND an active provider. Passing a context lets Turnstile label the action;
        // osc_show_captcha() emits nothing when no provider is configured.
        $sf_item_captcha = osc_recaptcha_items_enabled()
            && (!function_exists('osc_captcha_enabled') || osc_captcha_enabled());
        if ($sf_item_captcha) { ?>
        <div class="sf-field">
            <?php osc_show_captcha('item_post'); ?>
        </div>
        <?php } ?>

        <div class="sf-form__actions">
            <button type="submit" class="sf-btn sf-btn--primary">
                <?php echo $sf_edit ? osc_esc_html(__('Update', 'storefront')) : osc_esc_html(__('Publish', 'storefront')); ?>
            </button>
        </div>
    </form>
</section>
<?php osc_current_web_theme_path('common/footer.php'); ?>
