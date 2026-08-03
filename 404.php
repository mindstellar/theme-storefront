<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
osc_current_web_theme_path('common/header.php');
?>
<section class="sf-error" style="max-width:760px;">
    <div class="sf-error__code">404</div>
    <h1><?php _e("This page couldn't be found.", 'storefront'); ?></h1>
    <p class="text-muted" style="font-size:16px; max-width:48ch;">
        <?php _e('The listing may have sold, expired or been removed — or the link was mistyped. Try searching for what you were after.', 'storefront'); ?>
    </p>

    <form class="sf-searchbox nocsrf" action="<?php echo osc_base_url(true); ?>" method="get" role="search" style="max-width:520px; margin:var(--space-6) 0;">
        <input type="hidden" name="page" value="search" />
        <input class="sf-search__input" type="text" name="sPattern" id="query" value=""
               placeholder="<?php echo osc_esc_html(__('Search listings', 'storefront')); ?>" />
        <button type="submit" class="sf-btn sf-btn--primary"><?php _e('Search', 'storefront'); ?></button>
    </form>

    <div style="display:flex; gap:var(--space-2); flex-wrap:wrap;">
        <a class="sf-btn sf-btn--secondary" href="<?php echo osc_base_url(); ?>"><?php _e('Back to home', 'storefront'); ?></a>
        <a class="sf-btn sf-btn--secondary" href="<?php echo osc_search_show_all_url(); ?>"><?php _e('Browse all listings', 'storefront'); ?></a>
        <a class="sf-btn sf-btn--secondary" href="<?php echo osc_contact_url(); ?>"><?php _e('Contact support', 'storefront'); ?></a>
    </div>

    <?php if (osc_count_categories()) { ?>
    <h2 style="margin-top:var(--space-8); font-size:20px;"><?php _e('Browse popular categories', 'storefront'); ?></h2>
    <nav class="sf-cats" aria-label="<?php echo osc_esc_html(__('Categories', 'storefront')); ?>">
        <?php osc_goto_first_category(); while (osc_has_categories()) { ?>
            <a class="sf-cat-chip" href="<?php echo osc_search_category_url(); ?>">
                <?php echo osc_esc_html(osc_category_name()); ?>
                <span class="sf-cat-chip__count"><?php echo (int) osc_category_total_items(); ?></span>
            </a>
        <?php } ?>
    </nav>
    <?php } ?>
</section>
<?php osc_current_web_theme_path('common/footer.php'); ?>
