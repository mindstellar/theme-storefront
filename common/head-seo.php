<?php
/*
 * Storefront — the machine-readable description of the current page:
 * OpenGraph, Twitter card and schema.org JSON-LD. Included by common/head.php.
 *
 * Everything here restates what the page already shows a visitor. Structured
 * data that claims more than the markup does is a manual action waiting to
 * happen, so the breadcrumb list mirrors .sf-crumbs exactly and a price is
 * emitted only when the listing really carries one.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

$sf_is_item = osc_is_ad_page();
$sf_site    = osc_page_title();
$sf_desc    = meta_description();
$sf_url     = osc_get_canonical() !== '' ? osc_get_canonical() : osc_base_url();

// The listing's own photos on an item page, else the operator's raster logo. An
// SVG logo is skipped: og:image needs a bitmap with real dimensions.
$sf_images = array();
if ($sf_is_item && osc_images_enabled_at_items()) {
    while (osc_has_item_resources()) {
        $sf_images[] = osc_resource_url();
    }
    osc_reset_resources();
}
$sf_logo_file = osc_get_preference('logo', 'storefront');
$sf_logo_url  = ($sf_logo_file !== '' && $sf_logo_file !== false)
    ? osc_base_url() . 'oc-content/uploads/' . $sf_logo_file
    : '';
if (!$sf_images && $sf_logo_url !== '') {
    $sf_images[] = $sf_logo_url;
}
?>
<meta property="og:type" content="<?php echo $sf_is_item ? 'product' : 'website'; ?>" />
<meta property="og:site_name" content="<?php echo osc_esc_html($sf_site); ?>" />
<meta property="og:locale" content="<?php echo osc_esc_html(osc_current_user_locale()); ?>" />
<meta property="og:url" content="<?php echo osc_esc_html($sf_url); ?>" />
<meta property="og:title" content="<?php echo osc_esc_html(meta_title()); ?>" />
<?php if ($sf_desc !== '') { ?>
<meta property="og:description" content="<?php echo osc_esc_html($sf_desc); ?>" />
<?php } ?>
<?php if ($sf_images) { ?>
<meta property="og:image" content="<?php echo osc_esc_html($sf_images[0]); ?>" />
<?php } ?>
<meta name="twitter:card" content="<?php echo $sf_images ? 'summary_large_image' : 'summary'; ?>" />
<?php
// One @graph rather than a script tag per type — the nodes describe the same
// page and Google reads them together either way.
$sf_organization = array(
    '@type' => 'Organization',
    'name'  => $sf_site,
    'url'   => osc_base_url(),
);
if ($sf_logo_url !== '') {
    $sf_organization['logo'] = $sf_logo_url;
}

// Built through osc_search_url() rather than hand-written, so the template
// follows whatever permalink structure the install actually uses. The
// placeholder is alphanumeric so it survives urlencode() intact.
$sf_search_template = str_replace(
    'STOREFRONTQUERY',
    '{sPattern}',
    osc_search_url(array('sPattern' => 'STOREFRONTQUERY'))
);

$sf_graph = array(
    array(
        '@type'           => 'WebSite',
        'name'            => $sf_site,
        'url'             => osc_base_url(),
        'potentialAction' => array(
            '@type'       => 'SearchAction',
            'target'      => array('@type' => 'EntryPoint', 'urlTemplate' => $sf_search_template),
            'query-input' => 'required name=sPattern',
        ),
    ),
    $sf_organization,
);

if ($sf_is_item) {
    $sf_offer = array(
        '@type'  => 'Offer',
        'url'    => osc_item_url(),
        'seller' => array(
            '@type' => osc_user_is_company() ? 'Organization' : 'Person',
            'name'  => osc_item_contact_name(),
        ),
    );

    // A price needs a currency to mean anything, and an empty priceCurrency
    // invalidates the whole Offer. i_price is 0 — not null — on listings posted
    // without a price, so an is-set test alone lets those through.
    $sf_currency = (string) osc_item_currency();
    $sf_price    = osc_item_field('i_price');
    if ($sf_currency !== '' && $sf_price !== '' && $sf_price !== null) {
        // Core stores i_price as the real price x 1,000,000.
        $sf_offer['priceCurrency'] = $sf_currency;
        $sf_offer['price']         = (string) (osc_item_price() / 1000000);
    }

    // A crawler that reaches an expired listing should see it is gone, not a
    // false InStock. No itemCondition: core has no new/used field, and inventing
    // one would be fabricated structured data.
    $sf_offer['availability'] = (osc_item_is_expired() || osc_item_is_inactive())
        ? 'https://schema.org/OutOfStock'
        : 'https://schema.org/InStock';

    $sf_product = array(
        '@type'  => 'Product',
        'name'   => osc_item_title(),
        'offers' => $sf_offer,
    );
    if ($sf_desc !== '') {
        $sf_product['description'] = $sf_desc;
    }
    if (osc_item_category() !== '') {
        $sf_product['category'] = osc_item_category();
    }
    if ($sf_images) {
        $sf_product['image'] = $sf_images;
    }
    $sf_graph[] = $sf_product;

    // Mirrors the .sf-crumbs trail at the top of item.php. The last crumb is the
    // page itself, so it carries no url — same as the markup, which does not
    // link it.
    $sf_graph[] = array(
        '@type'           => 'BreadcrumbList',
        'itemListElement' => array(
            array('@type' => 'ListItem', 'position' => 1, 'name' => __('Home', 'storefront'), 'item' => osc_base_url()),
            array('@type' => 'ListItem', 'position' => 2, 'name' => osc_item_category(), 'item' => osc_search_category_url()),
            array('@type' => 'ListItem', 'position' => 3, 'name' => osc_item_title()),
        ),
    );
}

// JSON_HEX_TAG is the load-bearing flag: a listing title containing "</script>"
// would otherwise close this tag and inject markup.
$sf_json = json_encode(
    array('@context' => 'https://schema.org', '@graph' => $sf_graph),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
);
?>
<script type="application/ld+json"><?php echo $sf_json; ?></script>
