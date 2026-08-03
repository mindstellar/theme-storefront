<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Mindstellar Community
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('ABS_PATH is not loaded. Direct access is not allowed.');
}

$formAction = osc_admin_render_theme_url('oc-content/themes/storefront/admin/header.php');

/**
 * One logo slot: its current preview (inline SVG or raster image), a remove form when set, and an
 * upload form. Primary and compact are the same control pointed at a different pair of
 * preferences via the `slot` field, so there is one piece of markup, not two that drift.
 */
$renderLogoSlot = static function ($slot, $svgKey, $rasterKey, $heading, $help) use ($formAction) {
    $svg    = (string) osc_get_preference($svgKey, 'storefront');
    $raster = (string) osc_get_preference($rasterKey, 'storefront');
    $hasLogo = ($svg !== '' || $raster !== '');
    ?>
    <h3 class="mb-2"><?php echo osc_esc_html($heading); ?></h3>
    <p class="form-text"><?php echo osc_esc_html($help); ?></p>

    <?php if ($hasLogo) { ?>
        <div class="sf-logo-preview mb-3">
            <?php if ($svg !== '') { ?>
                <?php echo $svg; /* already sanitized on save */ ?>
            <?php } else { ?>
                <img src="<?php echo osc_esc_html(osc_base_url() . 'oc-content/uploads/' . $raster); ?>"
                     alt="<?php echo osc_esc_html(osc_get_preference('header_logo_text', 'storefront') ?: osc_page_title()); ?>">
            <?php } ?>
        </div>
        <form action="<?php echo $formAction; ?>" method="post" class="mb-4">
            <input type="hidden" name="action_specific" value="storefront_remove_logo">
            <input type="hidden" name="slot" value="<?php echo osc_esc_html($slot); ?>">
            <button type="submit" class="btn btn-outline-danger">
                <?php _e('Remove', 'storefront'); ?>
            </button>
        </form>
    <?php } ?>

    <form action="<?php echo $formAction; ?>" method="post" enctype="multipart/form-data" class="mb-4">
        <input type="hidden" name="action_specific" value="storefront_upload_logo">
        <input type="hidden" name="slot" value="<?php echo osc_esc_html($slot); ?>">
        <div class="mb-2">
            <input type="file" class="form-control" name="logo_file"
                   id="logo_file_<?php echo osc_esc_html($slot); ?>"
                   accept="image/svg+xml,image/png,image/jpeg,.svg,.png,.jpg">
            <div class="form-text">
                <?php _e('SVG, PNG or JPG. SVG is stored inline and follows your text colour (correct in dark mode). Scripts and remote references are stripped from SVGs on upload.', 'storefront'); ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">
            <?php echo osc_esc_html($hasLogo ? __('Replace', 'storefront') : __('Upload', 'storefront')); ?>
        </button>
    </form>
    <?php
};
?>
<style type="text/css" media="screen">
    .sf-logo-preview {
        max-width: 320px;
        padding: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: #fff;
    }
    .sf-logo-preview img {
        max-width: 100%;
        height: auto;
        display: block;
    }
    .sf-logo-preview svg {
        max-width: 100%;
        height: auto;
        display: block;
    }
</style>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="mb-0"><?php _e('Brand &amp; Logo', 'storefront'); ?></h2>
    </div>
    <div class="card-body">

        <form action="<?php echo $formAction; ?>" method="post" class="mb-4">
            <input type="hidden" name="action_specific" value="storefront_brand">
            <div class="mb-3">
                <label class="form-label" for="header_logo_text"><?php _e('Site name', 'storefront'); ?></label>
                <input type="text" class="form-control" id="header_logo_text" name="header_logo_text"
                       value="<?php echo osc_esc_html(osc_get_preference('header_logo_text', 'storefront')); ?>">
                <div class="form-text">
                    <?php _e('The wordmark shown when no logo image is set, and the accessible site name in every case.', 'storefront'); ?>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?php _e('Save', 'storefront'); ?></button>
        </form>

        <hr class="my-4">

        <?php
        $renderLogoSlot(
            'primary',
            'brand_logo_svg',
            'logo',
            __('Logo', 'storefront'),
            __('Your main logo, shown in the site header.', 'storefront')
        );
        ?>

        <hr class="my-4">

        <?php
        $renderLogoSlot(
            'compact',
            'brand_logo_svg_compact',
            'logo_compact',
            __('Compact logo (optional)', 'storefront'),
            __('Shown on small screens in place of the main logo. Use a square-ish mark. Falls back to the main logo if left empty.', 'storefront')
        );
        ?>

    </div>
</div>
