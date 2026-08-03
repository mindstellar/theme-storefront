<?php
/*
 * Storefront — search results. Two-column: refine filters beside the grid.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
// Filter drawer, location autocomplete, and search-alert subscribe.
storefront_enqueue_search();
osc_current_web_theme_path('common/header.php');

// Current sort, for preselecting the sort control.
$sf_types    = Search::getAllowedTypesForSorting();
$sf_cur_type = isset($sf_types[osc_search_order_type()]) ? strtolower((string) $sf_types[osc_search_order_type()]) : 'desc';
$sf_cur_ord  = osc_search_order();
$sf_sort_url = static function ($field, $type) {
    return osc_esc_html(osc_update_search_url(array('sOrder' => $field, 'iOrderType' => $type)));
};

// search_title() is empty on the browse-all view (no query, no category), which
// would leave an empty <h1> — a page needs a meaningful top heading for its
// document outline and for screen readers landing here. Fall back to a label.
$sf_title = trim((string) search_title());
if ($sf_title === '') { $sf_title = __('Browse listings', 'storefront'); }
?>
<div class="sf-crumbs">
    <a href="<?php echo osc_base_url(); ?>"><?php _e('Home', 'storefront'); ?></a> &nbsp;/&nbsp; <?php _e('Browse', 'storefront'); ?>
</div>

<?php osc_run_hook('search_ads_listing_top'); ?>

<?php $sf_filters = storefront_active_filters(); ?>

<div class="sf-searchlayout">
    <?php // Main column is first in the DOM so the results <h1> precedes the rail's
          // sub-headings for a screen reader; CSS places the rail on the left. ?>
    <div class="sf-searchlayout__main">
        <div class="sf-results__head">
            <?php // Mobile only: opens the filter rail as a drawer. Carries the applied count so
                  // "why am I only seeing 3?" has an answer once the rail is off-screen. ?>
            <button type="button" class="sf-filterbtn" data-offcanvas-open="sf-filters" aria-expanded="false" aria-controls="sf-filters">
                <?php echo storefront_icon('sliders', 16); ?>
                <span><?php _e('Filters', 'storefront'); ?></span>
                <?php if ($sf_filters) { ?>
                    <span class="sf-filterbtn__count"><?php echo count($sf_filters); ?></span>
                <?php } ?>
            </button>
            <div class="sf-results__headline">
                <h1 class="sf-results__title"><?php echo osc_esc_html($sf_title); ?></h1>
                <span class="sf-results__count"><?php printf(__('%d results', 'storefront'), (int) osc_search_total_items()); ?></span>
                <?php // Subscribe to this exact search — core serves it as RSS (sFeed=rss). ?>
                <a class="sf-results__feed" rel="alternate nofollow" type="application/rss+xml"
                   href="<?php echo osc_esc_html(osc_update_search_url(array('sFeed' => 'rss'))); ?>"
                   title="<?php echo osc_esc_html(__('Subscribe to this search (RSS)', 'storefront')); ?>">
                    <?php echo storefront_icon('rss', 14); ?><span><?php _e('Subscribe', 'storefront'); ?></span>
                </a>
            </div>
            <?php if (osc_count_items() > 0) { ?>
            <div class="sf-results__tools">
                <div class="sf-results__views" role="group" aria-label="<?php echo osc_esc_html(__('View as', 'storefront')); ?>">
                    <a class="sf-viewbtn <?php echo osc_search_show_as() === 'gallery' ? 'is-active' : ''; ?>"
                       href="<?php echo osc_esc_html(osc_update_search_url(array('sShowAs' => 'gallery'))); ?>"
                       aria-label="<?php echo osc_esc_html(__('Grid view', 'storefront')); ?>"
                       <?php echo osc_search_show_as() === 'gallery' ? 'aria-current="true"' : ''; ?>>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    </a>
                    <a class="sf-viewbtn <?php echo osc_search_show_as() === 'list' ? 'is-active' : ''; ?>"
                       href="<?php echo osc_esc_html(osc_update_search_url(array('sShowAs' => 'list'))); ?>"
                       aria-label="<?php echo osc_esc_html(__('List view', 'storefront')); ?>"
                       <?php echo osc_search_show_as() === 'list' ? 'aria-current="true"' : ''; ?>>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    </a>
                </div>
                <label class="sf-sort">
                    <span><?php _e('Sort', 'storefront'); ?></span>
                    <select class="sf-select" data-sf-nav>
                        <option value="<?php echo $sf_sort_url('dt_pub_date', 'desc'); ?>" <?php echo ($sf_cur_ord === 'dt_pub_date' && $sf_cur_type === 'desc') ? 'selected' : ''; ?>><?php _e('Most recent', 'storefront'); ?></option>
                        <option value="<?php echo $sf_sort_url('i_price', 'asc'); ?>" <?php echo ($sf_cur_ord === 'i_price' && $sf_cur_type === 'asc') ? 'selected' : ''; ?>><?php _e('Price: low to high', 'storefront'); ?></option>
                        <option value="<?php echo $sf_sort_url('i_price', 'desc'); ?>" <?php echo ($sf_cur_ord === 'i_price' && $sf_cur_type === 'desc') ? 'selected' : ''; ?>><?php _e('Price: high to low', 'storefront'); ?></option>
                    </select>
                </label>
            </div>
            <?php } ?>
        </div>

        <?php if ($sf_filters) { ?>
        <ul class="sf-chips" aria-label="<?php echo osc_esc_html(__('Active filters', 'storefront')); ?>">
            <?php foreach ($sf_filters as $sf_f) { ?>
            <li>
                <a class="sf-chip" href="<?php echo osc_esc_html($sf_f['url']); ?>">
                    <span class="sf-chip__label"><?php echo osc_esc_html($sf_f['label']); ?></span>
                    <?php echo storefront_icon('x', 13); ?>
                    <span class="sf-sr-only"><?php _e('Remove filter', 'storefront'); ?></span>
                </a>
            </li>
            <?php } ?>
            <?php if (count($sf_filters) > 1) { ?>
            <li><a class="sf-chip sf-chip--clear" href="<?php echo osc_esc_html(osc_search_show_all_url()); ?>"><?php _e('Clear all', 'storefront'); ?></a></li>
            <?php } ?>
        </ul>
        <?php } ?>

        <?php if (osc_count_items() === 0) { ?>
            <div class="sf-empty">
                <h4><?php _e('No listings match', 'storefront'); ?></h4>
                <p><?php printf(__('No results for "%s". Try widening your filters or clearing the search.', 'storefront'), osc_esc_html(osc_search_pattern())); ?></p>
                <p><a class="sf-btn sf-btn--secondary" href="<?php echo osc_search_show_all_url(); ?>"><?php _e('Clear filters', 'storefront'); ?></a></p>
            </div>
        <?php } else {
            View::newInstance()->_exportVariableToView('listType', 'items');
            osc_current_web_theme_path('common/loop.php');
            ?>
            <?php
            View::newInstance()->_exportVariableToView('sf_pagination_html', osc_search_pagination());
            osc_current_web_theme_path('common/pagination.php');
            ?>
        <?php } ?>
    </div>

    <?php // The rail's own <aside> carries .sf-searchlayout__side + #sf-filters; it is the left
          // column on desktop and the off-canvas drawer on mobile. ?>
    <?php osc_current_web_theme_path('common/search-sidebar.php'); ?>
    <div class="sf-filters__backdrop" data-offcanvas-close hidden></div>
</div>

<?php osc_current_web_theme_path('common/footer.php'); ?>
