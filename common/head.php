<?php
/*
 * Storefront — contents of <head>. Included by header.php.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
?>
<?php
// Core writes charset, viewport, title, description, keywords, canonical and the prev/next
// links on Shopclass 6.3.0 and later; before that the theme writes the same tags itself.
$sf_core_head = function_exists('osc_head');
if ($sf_core_head) {
    // Also prints the enqueued styles and scripts: it runs the `header` hook itself.
    osc_head();
} else { ?>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo osc_esc_html(meta_title()); ?></title>
<?php if (meta_description() !== '') { ?>
<meta name="description" content="<?php echo osc_esc_html(meta_description()); ?>" />
<?php } ?>
<?php if (meta_keywords() !== '') { ?>
<meta name="keywords" content="<?php echo osc_esc_html(meta_keywords()); ?>" />
<?php } ?>
<?php if (osc_get_canonical() !== '') { ?>
<link rel="canonical" href="<?php echo osc_esc_html(osc_get_canonical()); ?>" />
<?php } ?>
<?php } ?>
<?php
// Feed discoverability: core serves RSS on the search route when sFeed=rss
// (CWebSearch). Point readers at the current search's feed on a results page,
// else the site-wide latest-listings feed.
$sf_on_search = function_exists('osc_is_search_page') && osc_is_search_page();
$sf_feed_url  = $sf_on_search
    ? osc_update_search_url(array('sFeed' => 'rss'))
    : osc_search_url(array('sFeed' => 'rss'));
$sf_feed_ttl  = $sf_on_search ? __('Search results', 'storefront') : __('Latest listings', 'storefront');
?>
<link rel="alternate" type="application/rss+xml" title="<?php echo osc_esc_html($sf_feed_ttl); ?>" href="<?php echo osc_esc_html($sf_feed_url); ?>" />
<link rel="preload" href="<?php echo osc_esc_html(osc_current_web_theme_url('fonts/archivo-latin.woff2')); ?>" as="font" type="font/woff2" crossorigin />
<?php // OpenGraph, Twitter card and JSON-LD for whatever page this is. ?>
<?php osc_current_web_theme_path('common/head-seo.php'); ?>
<?php // Enqueued styles and scripts (theme + plugins), when osc_head() did not print them. ?>
<?php if (!$sf_core_head) {
    osc_run_hook('header');
} ?>
