<?php
/*
 * Storefront — contents of <head>. Included by header.php.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
?>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo meta_title(); ?></title>
<?php if (meta_description() !== '') { ?>
<meta name="description" content="<?php echo osc_esc_html(meta_description()); ?>" />
<?php } ?>
<?php if (meta_keywords() !== '') { ?>
<meta name="keywords" content="<?php echo osc_esc_html(meta_keywords()); ?>" />
<?php } ?>
<?php if (osc_get_canonical() !== '') { ?>
<link rel="canonical" href="<?php echo osc_get_canonical(); ?>" />
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
<?php // Enqueued styles and scripts (theme + plugins) are printed by this hook. ?>
<?php osc_run_hook('header'); ?>
