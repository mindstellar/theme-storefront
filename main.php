<?php
/*
 * Storefront — home page.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
// The home hero's location field uses the location autocomplete — load the bundle.
storefront_enqueue_search();
osc_current_web_theme_path('common/header.php');

// Category accent hues cycle through the palette's roles, one strong colour per
// tile top-border, mirroring the design's category board.
$sf_hues = array(
    'var(--color-accent)', 'var(--color-accent-2)', 'var(--color-text)',
    'var(--color-neutral-700)', 'var(--color-accent-700)', 'var(--color-accent-2-600)',
    'var(--color-accent-600)', 'var(--color-neutral-800)',
);
?>
<section class="sf-hero">
    <div class="sf-container sf-hero__inner">
        <?php if (storefront_setting('hero_show_decor', '1') !== '0') { ?>
        <svg class="sf-hero__decor" viewBox="0 0 460 420" aria-hidden="true">
            <circle cx="330" cy="120" r="96" fill="none" stroke="var(--color-accent-200)" stroke-width="2"></circle>
            <g><rect x="286" y="76" width="88" height="88" rx="18" fill="var(--color-accent)"></rect><path d="M310 120l14 14 26-30" fill="none" stroke="var(--color-fixed-light)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"></path></g>
            <g><rect x="120" y="220" width="70" height="70" rx="16" fill="var(--color-accent-2-500)"></rect><circle cx="155" cy="255" r="15" fill="none" stroke="var(--color-fixed-light)" stroke-width="5"></circle></g>
            <g><circle cx="380" cy="300" r="40" fill="var(--color-accent)"></circle><path d="M366 300h28M380 286v28" stroke="var(--color-fixed-light)" stroke-width="5" stroke-linecap="round"></path></g>
            <g><rect x="222" y="250" width="58" height="58" rx="14" fill="var(--color-accent-2)"></rect><path d="M236 279l10 10 18-22" fill="none" stroke="var(--color-fixed-light)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"></path></g>
        </svg>
        <?php } ?>

        <div class="sf-hero__content">
        <h1 class="sf-hero__title"><?php echo osc_esc_html(storefront_setting('hero_headline', osc_page_title())); ?></h1>
        <p class="sf-hero__sub">
            <?php
            $sf_hero_tagline = storefront_setting('hero_tagline', '');
            if ($sf_hero_tagline !== '') {
                echo osc_esc_html($sf_hero_tagline);
            } else {
                echo osc_page_description() !== ''
                    ? osc_esc_html(osc_page_description())
                    : osc_esc_html(__('Buy, sell and rent near you. Browse a clean grid of local listings, or post your own for free in a couple of minutes.', 'storefront'));
            }
            ?>
        </p>

        <div class="sf-hero__glass">
            <form class="sf-hero__search nocsrf" action="<?php echo osc_base_url(true); ?>" method="get" role="search">
                <input type="hidden" name="page" value="search" />
                <div class="sf-hero__field">
                    <svg class="sf-hero__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
                    <input type="text" name="sPattern" value="<?php echo osc_esc_html(osc_search_pattern()); ?>"
                           placeholder="<?php echo osc_esc_html(__('What are you looking for?', 'storefront')); ?>" />
                </div>
                <div class="sf-hero__field">
                    <svg class="sf-hero__icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <?php // Same vanilla autocomplete the search rail uses (js/storefront.js binds
                          // input[data-ac] against core's location_cities ajax). No region scope here —
                          // the hero is a global quick-search — so it suggests cities everywhere. ?>
                    <input type="text" name="sCity" autocomplete="off"
                           data-ac="location_cities" data-ac-url="<?php echo osc_esc_html(osc_base_url(true)); ?>"
                           placeholder="<?php echo osc_esc_html(__('Location', 'storefront')); ?>" />
                </div>
                <?php if (osc_count_categories()) { ?>
                <div class="sf-hero__cat">
                    <?php osc_categories_select('sCategory', null, __('All categories', 'storefront')); ?>
                </div>
                <?php } ?>
                <button class="sf-btn sf-btn--primary" type="submit">
                    <?php _e('Search', 'storefront'); ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                </button>
            </form>
        </div>

        <?php if (osc_count_categories() && storefront_setting('hero_show_chips', '1') !== '0') { ?>
        <div class="sf-hero__popular">
            <span><?php _e('Popular:', 'storefront'); ?></span>
            <?php osc_goto_first_category(); $sf_n = 0; while (osc_has_categories() && $sf_n < 5) { $sf_n++; ?>
                <a class="sf-hero__chip" href="<?php echo osc_search_category_url(); ?>"><?php echo osc_esc_html(osc_category_name()); ?></a>
            <?php } ?>
        </div>
        <?php } ?>
        </div><!-- /.sf-hero__content -->
    </div>
</section>

<?php osc_run_hook('search_ads_listing_top'); ?>

<?php if (osc_count_categories()) { ?>
<section class="sf-section">
    <div class="sf-container">
        <div class="sf-section__head">
            <h2 class="sf-section__title"><?php _e('Browse categories', 'storefront'); ?></h2>
            <a href="<?php echo osc_search_show_all_url(); ?>"><?php _e('View all', 'storefront'); ?> &rarr;</a>
        </div>
        <ul class="sf-catgrid">
            <?php osc_goto_first_category(); $sf_i = 0; while (osc_has_categories()) { $sf_i++;
                $sf_hue = $sf_hues[($sf_i - 1) % count($sf_hues)]; ?>
                <li>
                    <a class="sf-catgrid__item" href="<?php echo osc_search_category_url(); ?>" style="border-top-color: <?php echo $sf_hue; ?>">
                        <span class="sf-catgrid__num" style="color: <?php echo $sf_hue; ?>"><?php echo str_pad((string) $sf_i, 2, '0', STR_PAD_LEFT); ?></span>
                        <span class="sf-catgrid__name"><?php echo osc_esc_html(osc_category_name()); ?></span>
                        <span class="sf-catgrid__count"><?php printf(__('%d listings', 'storefront'), (int) osc_category_total_items()); ?></span>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </div>
</section>
<?php } ?>

<?php
// Featured (premium) listings — only render when the site actually has them.
osc_get_premiums(4);
if (osc_count_premiums() > 0) { ?>
<section class="sf-section">
    <div class="sf-container">
        <div class="sf-section__head">
            <h2 class="sf-section__title"><?php _e('Featured', 'storefront'); ?></h2>
            <span class="sf-section__aside"><?php _e('Promoted listings', 'storefront'); ?></span>
        </div>
        <?php osc_run_hook('home_before_featured'); ?>
        <?php
        View::newInstance()->_exportVariableToView('listType', 'premiums');
        osc_current_web_theme_path('common/loop.php');
        ?>
        <?php osc_run_hook('home_after_featured'); ?>
    </div>
</section>
<?php } ?>

<section class="sf-section">
    <div class="sf-container">
        <div class="sf-section__head">
            <h2 class="sf-section__title"><?php _e('Recent listings', 'storefront'); ?></h2>
            <a href="<?php echo osc_search_show_all_url(); ?>"><?php _e('See all', 'storefront'); ?> &rarr;</a>
        </div>
        <?php osc_run_hook('home_before_listings'); ?>
        <?php if (osc_count_latest_items() === 0) { ?>
            <p class="sf-empty"><?php _e('No listings yet — be the first to post one.', 'storefront'); ?></p>
        <?php } else {
            View::newInstance()->_exportVariableToView('listType', 'latestItems');
            osc_current_web_theme_path('common/loop.php');
            if (osc_count_latest_items() === osc_max_latest_items()) { ?>
                <p class="sf-more">
                    <a class="sf-btn sf-btn--secondary" href="<?php echo osc_search_show_all_url(); ?>"><?php _e('See all listings', 'storefront'); ?></a>
                </p>
            <?php }
        } ?>
        <?php osc_run_hook('home_after_listings'); ?>
    </div>
</section>

<?php osc_current_web_theme_path('common/footer.php'); ?>
