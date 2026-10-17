<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('ABS_PATH is not loaded. Direct access is not allowed.');
}

$formAction = osc_admin_render_theme_url('oc-content/themes/storefront/admin/header.php');

/**
 * One logo slot: its preview, a file picker, and Upload/Replace and Remove on one line.
 * Remove posts a separate form through the `form` attribute, since forms cannot nest.
 */
$renderLogoSlot = static function ($slot, $svgKey, $rasterKey, $label, $help) use ($formAction) {
    $svg     = (string) osc_get_preference($svgKey, 'storefront');
    $raster  = (string) osc_get_preference($rasterKey, 'storefront');
    $hasLogo = ($svg !== '' || $raster !== '');
    $inputId = 'logo_file_' . $slot;
    $removeId = 'sf-remove-logo-' . $slot;

    if ($hasLogo) {
        osc_admin_form_open(array(
            'url'        => $formAction,
            'id'         => $removeId,
            'horizontal' => false,
            'fields'     => array('action_specific' => 'storefront_remove_logo', 'slot' => $slot),
        ));
        osc_admin_form_close(null, array('horizontal' => false));
    }

    osc_admin_form_open(array(
        'url'    => $formAction,
        'upload' => true,
        'fields' => array('action_specific' => 'storefront_upload_logo', 'slot' => $slot),
    ));
    osc_admin_form_row_open($label, array('for' => $inputId));
    if ($hasLogo) { ?>
        <div class="sf-logo-preview">
            <?php if ($svg !== '') { ?>
                <?php echo $svg; /* already sanitized on save */ ?>
            <?php } else { ?>
                <img src="<?php echo osc_esc_html(osc_base_url() . 'oc-content/uploads/' . $raster); ?>"
                     alt="<?php echo osc_esc_html(osc_get_preference('header_logo_text', 'storefront') ?: osc_page_title()); ?>">
            <?php } ?>
        </div>
    <?php } ?>
    <div class="sf-logo-controls">
        <input type="file" class="form-control form-control-sm" name="logo_file" id="<?php echo osc_esc_html($inputId); ?>"
               accept="image/svg+xml,image/png,image/jpeg,.svg,.png,.jpg">
        <?php
        osc_admin_action_button(array(
            'label'   => $hasLogo ? __('Replace', 'storefront') : __('Upload', 'storefront'),
            'type'    => 'submit',
            'variant' => 'primary',
        ));
        if ($hasLogo) {
            osc_admin_action_button(array(
                'label'   => __('Remove', 'storefront'),
                'type'    => 'submit',
                'variant' => 'outline-danger',
                'attrs'   => array('form' => $removeId),
            ));
        }
        ?>
    </div>
    <?php
    osc_admin_field_help(array('help' => $help));
    osc_admin_form_row_close();
    osc_admin_form_close();
};
?>
<style type="text/css" media="screen">
    .sf-logo-preview {
        max-width: 320px;
        margin-bottom: .75rem;
        padding: 1rem;
        border: 1px solid var(--osc-rule);
        border-radius: .375rem;
        background-color: #fff;
    }
    .sf-logo-preview img,
    .sf-logo-preview svg {
        max-width: 100%;
        height: auto;
        display: block;
    }
    .sf-logo-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
        max-width: 36rem;
    }
    .sf-logo-controls input[type="file"] {
        flex: 1 1 14rem;
        width: auto;
    }
</style>

<?php
osc_admin_form_open(array(
    'url'    => $formAction,
    'fields' => array('action_specific' => 'storefront_brand'),
));
osc_admin_text(array(
    'name'  => 'header_logo_text',
    'label' => __('Site name', 'storefront'),
    'value' => (string) osc_get_preference('header_logo_text', 'storefront'),
    'help'  => __('The wordmark shown when no logo image is set, and the accessible site name in every case.', 'storefront'),
));
osc_admin_form_close(array(array('label' => __('Save', 'storefront'), 'type' => 'submit')));

osc_admin_form_section(__('Logos', 'storefront'), array(
    'spaced' => true,
    'intro'  => __('SVG, PNG or JPG. SVG is stored inline and follows your text colour (correct in dark mode). Scripts and remote references are stripped from SVGs on upload.', 'storefront'),
));

$renderLogoSlot(
    'primary',
    'brand_logo_svg',
    'logo',
    __('Logo', 'storefront'),
    __('Your main logo, shown in the site header.', 'storefront')
);

$renderLogoSlot(
    'compact',
    'brand_logo_svg_compact',
    'logo_compact',
    __('Compact logo (optional)', 'storefront'),
    __('Shown on small screens in place of the main logo. Use a square-ish mark. Falls back to the main logo if left empty.', 'storefront')
);
