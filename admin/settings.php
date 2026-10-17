<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
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
$homeShowFeatured = (osc_get_preference('home_show_featured', 'storefront') !== '0');
$footerLink    = (osc_get_preference('footer_link', 'storefront') !== '0');

$palettes = array(
    'teal'   => array('label' => __('Teal', 'storefront'),   'primary' => '#12a6a0', 'secondary' => '#ff6b4a'),
    'indigo' => array('label' => __('Indigo', 'storefront'), 'primary' => '#4f46e5', 'secondary' => '#f59e0b'),
    'violet' => array('label' => __('Violet', 'storefront'), 'primary' => '#7c3aed', 'secondary' => '#ec4899'),
);
$paletteOptions = array();
foreach ($palettes as $value => $info) {
    $paletteOptions[$value] = array(
        'label'       => $info['label'],
        'custom_html' => '<span class="sf-swatches" aria-hidden="true">'
            . '<span class="sf-swatch" style="background-color: ' . osc_esc_html($info['primary']) . ';"></span>'
            . '<span class="sf-swatch" style="background-color: ' . osc_esc_html($info['secondary']) . ';"></span>'
            . '</span>',
    );
}

$pref = static fn ($key) => (string) osc_get_preference($key, 'storefront');
?>
<style type="text/css" media="screen">
    .sf-swatches {
        display: inline-flex;
        gap: .35rem;
        margin-inline-start: .5rem;
        vertical-align: middle;
    }
    .sf-swatch {
        display: inline-block;
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 50%;
        border: 1px solid var(--osc-rule);
    }
</style>

<?php
osc_admin_form_open(array(
    'url'    => $formAction,
    'fields' => array('action_specific' => 'storefront_settings'),
));

osc_admin_form_section(__('Appearance', 'storefront'));
osc_admin_radio_group(array(
    'name'     => 'palette',
    'label'    => __('Colour palette', 'storefront'),
    'options'  => $paletteOptions,
    'selected' => $palette,
    'help'     => __('Sets the primary and secondary accent colours across the site.', 'storefront'),
));
osc_admin_radio_group(array(
    'name'     => 'color_scheme',
    'label'    => __('Colour scheme', 'storefront'),
    'options'  => array(
        'light' => __('Light', 'storefront'),
        'dark'  => __('Dark', 'storefront'),
        'auto'  => __('Auto (follow device)', 'storefront'),
    ),
    'selected' => $colorScheme,
    'help'     => __('"Auto" switches between light and dark to match each visitor\'s device setting.', 'storefront'),
));

osc_admin_form_section(__('Home / Hero', 'storefront'), array('spaced' => true));
osc_admin_text(array(
    'name'  => 'hero_headline',
    'label' => __('Headline', 'storefront'),
    'value' => $pref('hero_headline'),
    'help'  => __('Overrides the big home headline. Leave empty to use the site title.', 'storefront'),
));
osc_admin_textarea(array(
    'name'  => 'hero_tagline',
    'label' => __('Tagline', 'storefront'),
    'value' => $pref('hero_tagline'),
    'rows'  => 3,
    'help'  => __('Sub-text shown under the headline. Leave empty to use the site description.', 'storefront'),
));
osc_admin_field_row(__('Home page', 'storefront'), array(
    array('type' => 'checkbox', 'name' => 'hero_show_decor', 'id' => 'hero_show_decor',
          'label' => __('Show decorative graphic', 'storefront'), 'checked' => $heroShowDecor),
    array('type' => 'checkbox', 'name' => 'hero_show_chips', 'id' => 'hero_show_chips',
          'label' => __('Show popular-category chips', 'storefront'), 'checked' => $heroShowChips),
    array('type' => 'checkbox', 'name' => 'home_show_featured', 'id' => 'home_show_featured',
          'label' => __('Show featured (promoted) listings on the home page', 'storefront'), 'checked' => $homeShowFeatured),
));

osc_admin_form_section(__('Search & Location', 'storefront'), array('spaced' => true));
osc_admin_select(array(
    'name'     => 'location_input_type',
    'width'    => 'text',
    'label'    => __('Post & edit form location', 'storefront'),
    'options'  => array(
        'autocomplete' => __('Autocomplete (type-ahead)', 'storefront'),
        'dropdown'     => __('Dropdowns (cascading selects)', 'storefront'),
    ),
    'selected' => $locationType,
    'help'     => __('How the location field behaves on the listing post/edit form.', 'storefront'),
));
osc_admin_select(array(
    'name'     => 'search_location_type',
    'width'    => 'text',
    'label'    => __('Search page location', 'storefront'),
    'options'  => array(
        'country_region_city' => __('Country, region & city', 'storefront'),
        'region_city'         => __('Region & city', 'storefront'),
        'autocomplete'        => __('Autocomplete (type-ahead)', 'storefront'),
    ),
    'selected' => $searchLocation,
    'help'     => __('How the location filter behaves on the search page. "Region & city" suits single-country sites — it hides the country choice and scopes results to your site\'s default country.', 'storefront'),
));
osc_admin_text(array(
    'name'  => 'keyword_placeholder',
    'label' => __('Search box placeholder', 'storefront'),
    'value' => $pref('keyword_placeholder'),
    'help'  => __('Leave empty to use the default placeholder.', 'storefront'),
));
osc_admin_select(array(
    'name'     => 'defaultShowAs@all',
    'id'       => 'defaultShowAs',
    'label'    => __('Default results view', 'storefront'),
    'options'  => array(
        'gallery' => __('Gallery', 'storefront'),
        'list'    => __('List', 'storefront'),
    ),
    'selected' => $showAs,
    'help'     => __('How search results are displayed by default.', 'storefront'),
));

osc_admin_form_section(__('Footer & Social', 'storefront'), array(
    'spaced' => true,
    'intro'  => __('Leave any field empty to hide that icon from the footer.', 'storefront'),
));
osc_admin_textarea(array(
    'name'  => 'footer_message',
    'label' => __('Footer tagline', 'storefront'),
    'value' => $pref('footer_message'),
    'rows'  => 2,
    'help'  => __('Brand tagline shown in the footer. Leave empty to use the default.', 'storefront'),
));
$socials = array(
    'social_facebook'  => array(__('Facebook', 'storefront'), 'https://facebook.com/…'),
    'social_twitter'   => array(__('X (Twitter)', 'storefront'), 'https://x.com/…'),
    'social_instagram' => array(__('Instagram', 'storefront'), 'https://instagram.com/…'),
    'social_youtube'   => array(__('YouTube', 'storefront'), 'https://youtube.com/…'),
    'social_linkedin'  => array(__('LinkedIn', 'storefront'), 'https://linkedin.com/…'),
);
foreach ($socials as $key => [$label, $placeholder]) {
    osc_admin_text(array(
        'type'        => 'url',
        'name'        => $key,
        'label'       => $label,
        'value'       => $pref($key),
        'placeholder' => $placeholder,
    ));
}
osc_admin_field_row('', array(
    array('type' => 'checkbox', 'name' => 'footer_link', 'id' => 'footer_link',
          'label' => __('Show "Powered by Shopclass" credit', 'storefront'), 'checked' => $footerLink),
));

osc_admin_form_close(
    array(array('label' => __('Save settings', 'storefront'), 'type' => 'submit')),
    array('dirty' => true)
);
