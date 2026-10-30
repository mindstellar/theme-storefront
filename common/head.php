<?php
/*
 * Storefront — contents of <head>. Included by header.php.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
?>
<?php
// Charset, viewport, title, meta and canonical tags, then the enqueued styles and scripts (it runs the `header` hook).
osc_head();
?>
<?php
// Feed discoverability: core serves RSS on the search route when sFeed=rss
// (CWebSearch). Point readers at the current search's feed on a results page,
// else the site-wide latest-listings feed.
$sf_on_search = osc_is_search_page();
$sf_feed_url  = $sf_on_search
    ? osc_update_search_url(array('sFeed' => 'rss'))
    : osc_search_url(array('sFeed' => 'rss'));
$sf_feed_ttl  = $sf_on_search ? __('Search results', 'storefront') : __('Latest listings', 'storefront');
?>
<link rel="alternate" type="application/rss+xml" title="<?php echo osc_esc_html($sf_feed_ttl); ?>" href="<?php echo osc_esc_html($sf_feed_url); ?>" />
<link rel="preload" href="<?php echo osc_esc_html(osc_current_web_theme_url('fonts/archivo-latin.woff2')); ?>" as="font" type="font/woff2" crossorigin />
<?php // Archivo has no Devanagari, so Hindi and its neighbours get a matching sans. ?>
<?php if (in_array(strtolower((string) strtok(osc_current_user_locale(), '_')), array('hi', 'mr', 'ne', 'sa', 'kok', 'mai', 'bho', 'doi'), true)) { ?>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400..800&amp;display=swap" />
<?php } ?>
<?php // OpenGraph, Twitter card and JSON-LD for whatever page this is. ?>
<?php osc_current_web_theme_path('common/head-seo.php'); ?>
