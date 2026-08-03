<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Mindstellar Community
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('ABS_PATH is not loaded. Direct access is not allowed.');
}

$formAction = osc_admin_render_theme_url('oc-content/themes/storefront/admin/settings.php');

$palette          = (string) osc_get_preference('palette', 'storefront');
$colorScheme      = (string) osc_get_preference('color_scheme', 'storefront');
$locationType     = (string) osc_get_preference('location_input_type', 'storefront');
$searchLocation   = (string) osc_get_preference('search_location_type', 'storefront');
$showAs           = (string) osc_get_preference('defaultShowAs@all', 'storefront');

if ($palette === '') {
    $palette = 'teal';
}
if ($colorScheme === '') {
    $colorScheme = 'light';
}
if ($locationType === '') {
    $locationType = 'autocomplete';
}
if ($searchLocation === '') {
    $searchLocation = 'autocomplete';
}
if ($showAs === '') {
    $showAs = 'gallery';
}

$heroShowDecor = (osc_get_preference('hero_show_decor', 'storefront') !== '0');
$heroShowChips = (osc_get_preference('hero_show_chips', 'storefront') !== '0');
$footerLink    = (osc_get_preference('footer_link', 'storefront') !== '0');

$palettes = array(
    'teal'   => array('label' => __('Teal', 'storefront'),   'primary' => '#12a6a0', 'secondary' => '#ff6b4a'),
    'indigo' => array('label' => __('Indigo', 'storefront'), 'primary' => '#4f46e5', 'secondary' => '#f59e0b'),
    'violet' => array('label' => __('Violet', 'storefront'), 'primary' => '#7c3aed', 'secondary' => '#ec4899'),
);
?>
<style type="text/css" media="screen">
    .sf-palette-card {
        position: relative;
        display: block;
        cursor: pointer;
        border: 2px solid transparent;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }
    .sf-palette-card__input {
        position: absolute;
        top: .75rem;
        right: .75rem;
        margin: 0;
    }
    .sf-palette-card:has(.sf-palette-card__input:checked) {
        border-color: #0d6efd;
        box-shadow: 0 0 0 .15rem rgba(13, 110, 253, .15);
    }
    .sf-palette-card__swatches {
        display: flex;
        gap: .5rem;
    }
    .sf-palette-card__swatch {
        display: inline-block;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 50%;
        border: 1px solid rgba(0, 0, 0, .1);
    }
</style>

<h2 class="mb-4"><?php _e('Storefront settings', 'storefront'); ?></h2>

<form action="<?php echo $formAction; ?>" method="post">
    <input type="hidden" name="action_specific" value="storefront_settings">

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="mb-0"><?php _e('Appearance', 'storefront'); ?></h2>
        </div>
        <div class="card-body">

            <label class="form-label d-block mb-2"><?php _e('Colour palette', 'storefront'); ?></label>
            <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
                <?php foreach ($palettes as $value => $info) { ?>
                    <div class="col">
                        <label class="sf-palette-card card h-100">
                            <input type="radio" class="sf-palette-card__input" name="palette"
                                   value="<?php echo osc_esc_html($value); ?>"
                                <?php echo ($palette === $value) ? 'checked' : ''; ?>>
                            <div class="card-body">
                                <div class="fw-semibold mb-2"><?php echo osc_esc_html($info['label']); ?></div>
                                <div class="sf-palette-card__swatches">
                                    <span class="sf-palette-card__swatch" style="background-color: <?php echo osc_esc_html($info['primary']); ?>;"></span>
                                    <span class="sf-palette-card__swatch" style="background-color: <?php echo osc_esc_html($info['secondary']); ?>;"></span>
                                </div>
                            </div>
                        </label>
                    </div>
                <?php } ?>
            </div>
            <div class="form-text mb-4"><?php _e('Sets the primary and secondary accent colours across the site.', 'storefront'); ?></div>

            <label class="form-label d-block mb-2"><?php _e('Colour scheme', 'storefront'); ?></label>
            <div class="d-flex flex-wrap gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="color_scheme" id="color_scheme_light"
                           value="light" <?php echo ($colorScheme === 'light') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="color_scheme_light"><?php _e('Light', 'storefront'); ?></label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="color_scheme" id="color_scheme_dark"
                           value="dark" <?php echo ($colorScheme === 'dark') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="color_scheme_dark"><?php _e('Dark', 'storefront'); ?></label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="color_scheme" id="color_scheme_auto"
                           value="auto" <?php echo ($colorScheme === 'auto') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="color_scheme_auto"><?php _e('Auto (follow device)', 'storefront'); ?></label>
                </div>
            </div>
            <div class="form-text"><?php _e('"Auto" switches between light and dark to match each visitor\'s device setting.', 'storefront'); ?></div>

        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="mb-0"><?php _e('Home / Hero', 'storefront'); ?></h2>
        </div>
        <div class="card-body">

            <div class="mb-3">
                <label class="form-label" for="hero_headline"><?php _e('Headline', 'storefront'); ?></label>
                <input type="text" class="form-control" id="hero_headline" name="hero_headline"
                       value="<?php echo osc_esc_html(osc_get_preference('hero_headline', 'storefront')); ?>">
                <div class="form-text"><?php _e('Overrides the big home headline. Leave empty to use the site title.', 'storefront'); ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="hero_tagline"><?php _e('Tagline', 'storefront'); ?></label>
                <textarea class="form-control" id="hero_tagline" name="hero_tagline" rows="3"><?php echo osc_esc_html(osc_get_preference('hero_tagline', 'storefront')); ?></textarea>
                <div class="form-text"><?php _e('Sub-text shown under the headline. Leave empty to use the site description.', 'storefront'); ?></div>
            </div>

            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="hero_show_decor" name="hero_show_decor"
                       value="1" <?php echo $heroShowDecor ? 'checked' : ''; ?>>
                <label class="form-check-label" for="hero_show_decor"><?php _e('Show decorative graphic', 'storefront'); ?></label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="hero_show_chips" name="hero_show_chips"
                       value="1" <?php echo $heroShowChips ? 'checked' : ''; ?>>
                <label class="form-check-label" for="hero_show_chips"><?php _e('Show popular-category chips', 'storefront'); ?></label>
            </div>

        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="mb-0"><?php _e('Search &amp; Location', 'storefront'); ?></h2>
        </div>
        <div class="card-body">

            <div class="mb-3">
                <label class="form-label" for="location_input_type"><?php _e('Post &amp; edit form location', 'storefront'); ?></label>
                <select class="form-select" id="location_input_type" name="location_input_type">
                    <option value="autocomplete" <?php echo ($locationType === 'autocomplete') ? 'selected' : ''; ?>>
                        <?php _e('Autocomplete (type-ahead)', 'storefront'); ?>
                    </option>
                    <option value="dropdown" <?php echo ($locationType === 'dropdown') ? 'selected' : ''; ?>>
                        <?php _e('Dropdowns (cascading selects)', 'storefront'); ?>
                    </option>
                </select>
                <div class="form-text"><?php _e('How the location field behaves on the listing post/edit form.', 'storefront'); ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="search_location_type"><?php _e('Search page location', 'storefront'); ?></label>
                <select class="form-select" id="search_location_type" name="search_location_type">
                    <option value="country_region_city" <?php echo ($searchLocation === 'country_region_city') ? 'selected' : ''; ?>>
                        <?php _e('Country, region &amp; city', 'storefront'); ?>
                    </option>
                    <option value="region_city" <?php echo ($searchLocation === 'region_city') ? 'selected' : ''; ?>>
                        <?php _e('Region &amp; city', 'storefront'); ?>
                    </option>
                    <option value="autocomplete" <?php echo ($searchLocation === 'autocomplete') ? 'selected' : ''; ?>>
                        <?php _e('Autocomplete (type-ahead)', 'storefront'); ?>
                    </option>
                </select>
                <div class="form-text"><?php _e('How the location filter behaves on the search page. "Region &amp; city" suits single-country sites — it hides the country choice and scopes results to your site\'s default country.', 'storefront'); ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="keyword_placeholder"><?php _e('Search box placeholder', 'storefront'); ?></label>
                <input type="text" class="form-control" id="keyword_placeholder" name="keyword_placeholder"
                       value="<?php echo osc_esc_html(osc_get_preference('keyword_placeholder', 'storefront')); ?>">
                <div class="form-text"><?php _e('Leave empty to use the default placeholder.', 'storefront'); ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="defaultShowAs"><?php _e('Default results view', 'storefront'); ?></label>
                <select class="form-select" id="defaultShowAs" name="defaultShowAs@all">
                    <option value="gallery" <?php echo ($showAs === 'gallery') ? 'selected' : ''; ?>>
                        <?php _e('Gallery', 'storefront'); ?>
                    </option>
                    <option value="list" <?php echo ($showAs === 'list') ? 'selected' : ''; ?>>
                        <?php _e('List', 'storefront'); ?>
                    </option>
                </select>
                <div class="form-text"><?php _e('How search results are displayed by default.', 'storefront'); ?></div>
            </div>

        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="mb-0"><?php _e('Footer &amp; Social', 'storefront'); ?></h2>
        </div>
        <div class="card-body">

            <div class="mb-3">
                <label class="form-label" for="footer_message"><?php _e('Footer tagline', 'storefront'); ?></label>
                <textarea class="form-control" id="footer_message" name="footer_message" rows="2"><?php echo osc_esc_html(osc_get_preference('footer_message', 'storefront')); ?></textarea>
                <div class="form-text"><?php _e('Brand tagline shown in the footer. Leave empty to use the default.', 'storefront'); ?></div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="social_facebook"><?php _e('Facebook', 'storefront'); ?></label>
                    <input type="url" class="form-control" id="social_facebook" name="social_facebook"
                           placeholder="https://facebook.com/…"
                           value="<?php echo osc_esc_html(osc_get_preference('social_facebook', 'storefront')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="social_twitter"><?php _e('X (Twitter)', 'storefront'); ?></label>
                    <input type="url" class="form-control" id="social_twitter" name="social_twitter"
                           placeholder="https://x.com/…"
                           value="<?php echo osc_esc_html(osc_get_preference('social_twitter', 'storefront')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="social_instagram"><?php _e('Instagram', 'storefront'); ?></label>
                    <input type="url" class="form-control" id="social_instagram" name="social_instagram"
                           placeholder="https://instagram.com/…"
                           value="<?php echo osc_esc_html(osc_get_preference('social_instagram', 'storefront')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="social_youtube"><?php _e('YouTube', 'storefront'); ?></label>
                    <input type="url" class="form-control" id="social_youtube" name="social_youtube"
                           placeholder="https://youtube.com/…"
                           value="<?php echo osc_esc_html(osc_get_preference('social_youtube', 'storefront')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="social_linkedin"><?php _e('LinkedIn', 'storefront'); ?></label>
                    <input type="url" class="form-control" id="social_linkedin" name="social_linkedin"
                           placeholder="https://linkedin.com/…"
                           value="<?php echo osc_esc_html(osc_get_preference('social_linkedin', 'storefront')); ?>">
                </div>
            </div>
            <div class="form-text mb-3"><?php _e('Leave any field empty to hide that icon from the footer.', 'storefront'); ?></div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="footer_link" name="footer_link"
                       value="1" <?php echo $footerLink ? 'checked' : ''; ?>>
                <label class="form-check-label" for="footer_link"><?php _e('Show &quot;Powered by Shopclass&quot; credit', 'storefront'); ?></label>
            </div>

        </div>
    </div>

    <button type="submit" class="btn btn-primary"><?php _e('Save settings', 'storefront'); ?></button>
</form>
