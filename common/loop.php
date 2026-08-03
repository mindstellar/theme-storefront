<?php
/*
 * Storefront — listing grid. Renders latest, premium (featured) or search
 * results depending on the `listType` exported by the calling view.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

$sf_type = View::newInstance()->_exists('listType') ? View::newInstance()->_get('listType') : 'items';

// The account dashboard shows the owner their own listings and opts into a
// per-card view count ("how many people looked") — the status signal that is
// too costly to sum globally. Off everywhere else, so public grids stay clean.
$sf_show_views = View::newInstance()->_exists('sfCardViews') && View::newInstance()->_get('sfCardViews');

// The dashboard shows the owner their own listings and opts into per-card owner
// actions (view / edit / delete). Off on every public grid, so those stay clean and
// never expose edit/delete on a listing the visitor doesn't own.
$sf_card_owner = View::newInstance()->_exists('sfCardOwner') && View::newInstance()->_get('sfCardOwner');

// Card for a regular listing (current item in an osc_has_items/latest loop).
$sf_item_card = static function () use ($sf_show_views, $sf_card_owner) {
    // On the owner's own grid, surface a status chip for anything that isn't live
    // (blocked / flagged / expired / pending), so a preview card carries the same
    // state signal the manage table does. Active listings stay chip-free.
    $sf_state_kind = ''; $sf_state_label = ''; $sf_state_ic = '';
    if ($sf_card_owner) {
        if (!osc_item_is_enabled())    { $sf_state_kind = 'danger'; $sf_state_label = __('Blocked', 'storefront'); $sf_state_ic = 'ban'; }
        elseif (osc_item_is_spam())    { $sf_state_kind = 'danger'; $sf_state_label = __('Flagged', 'storefront'); $sf_state_ic = 'flag'; }
        elseif (osc_item_is_expired()) { $sf_state_kind = 'warn';   $sf_state_label = __('Expired', 'storefront'); $sf_state_ic = 'alert'; }
        elseif (!osc_item_is_active()) { $sf_state_kind = 'muted';  $sf_state_label = __('Pending', 'storefront'); $sf_state_ic = 'clock'; }
    }
    ?>
    <li class="sf-card">
        <a class="sf-card__media" href="<?php echo osc_item_url(); ?>">
            <?php if (osc_images_enabled_at_items() && osc_has_item_resources()) { ?>
                <img loading="lazy" src="<?php echo osc_esc_html(osc_resource_thumbnail_url()); ?>"
                     alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
            <?php } else { ?>
                <span class="sf-card__noimg" aria-hidden="true"></span>
            <?php } ?>
            <?php if (osc_item_category() !== '') { ?>
                <span class="sf-card__cat"><?php echo osc_esc_html(osc_item_category()); ?></span>
            <?php } ?>
        </a>
        <div class="sf-card__body">
            <div class="sf-card__pricerow">
                <?php if (osc_item_formated_price() !== '') { ?>
                    <span class="sf-card__price"><?php echo osc_esc_html(osc_item_formated_price()); ?></span>
                <?php } else { ?>
                    <span class="sf-card__price"><?php _e('Check with seller', 'storefront'); ?></span>
                <?php } ?>
                <?php if ($sf_state_label !== '') { ?>
                    <span class="sf-state sf-state--<?php echo $sf_state_kind; ?>"><?php echo storefront_icon($sf_state_ic, 12); ?><?php echo osc_esc_html($sf_state_label); ?></span>
                <?php } ?>
            </div>
            <a class="sf-card__title" href="<?php echo osc_item_url(); ?>"><?php echo osc_esc_html(osc_item_title()); ?></a>
            <?php // Teaser: shown only in list view (css/storefront.css hides it in the grid). ?>
            <?php $sf_desc = storefront_excerpt(osc_item_description()); if ($sf_desc !== '') { ?>
                <p class="sf-card__desc"><?php echo osc_esc_html($sf_desc); ?></p>
            <?php } ?>
            <div class="sf-card__meta">
                <span class="sf-card__loc">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span><?php echo osc_item_city() !== '' ? osc_esc_html(osc_item_city()) : osc_esc_html(__('—', 'storefront')); ?></span>
                </span>
                <?php if ($sf_show_views) { ?>
                    <span class="sf-card__views">
                        <?php echo storefront_icon('eye', 12); ?><?php echo (int) osc_item_views(); ?>
                        <span class="sf-sr-only"><?php _e('views', 'storefront'); ?></span>
                    </span>
                <?php } ?>
                <span class="sf-card__date"><?php echo osc_esc_html(osc_format_date(osc_item_pub_date())); ?></span>
            </div>
        </div>
        <?php if ($sf_card_owner) { ?>
        <div class="sf-card__actions">
            <a class="sf-btn sf-btn--ghost sf-btn--sm" href="<?php echo osc_item_url(); ?>"><?php echo storefront_icon('eye', 14); ?><span><?php _e('View', 'storefront'); ?></span></a>
            <a class="sf-btn sf-btn--ghost sf-btn--sm" href="<?php echo osc_esc_html(osc_item_edit_url()); ?>"><?php echo storefront_icon('edit', 14); ?><span><?php _e('Edit', 'storefront'); ?></span></a>
            <a class="sf-btn sf-btn--ghost sf-btn--sm sf-card__delete" rel="nofollow" href="<?php echo osc_esc_html(osc_item_delete_url()); ?>" data-confirm="<?php echo osc_esc_html(sprintf(__('Delete “%s”? This cannot be undone.', 'storefront'), osc_item_title())); ?>" data-confirm-title="<?php echo osc_esc_html(__('Delete listing', 'storefront')); ?>" data-confirm-ok="<?php echo osc_esc_html(__('Delete listing', 'storefront')); ?>"><?php echo storefront_icon('trash', 14); ?><span><?php _e('Delete', 'storefront'); ?></span></a>
        </div>
        <?php } ?>
    </li>
    <?php
};

// Card for a featured/premium listing (current premium in an osc_has_premiums loop).
$sf_premium_card = static function () {
    ?>
    <li class="sf-card sf-card--featured">
        <a class="sf-card__media" href="<?php echo osc_premium_url(); ?>">
            <?php if (osc_images_enabled_at_items() && osc_count_premium_resources() && osc_has_premium_resources()) { ?>
                <img loading="lazy" src="<?php echo osc_esc_html(osc_resource_thumbnail_url()); ?>"
                     alt="<?php echo osc_esc_html(osc_premium_title()); ?>" />
            <?php } else { ?>
                <span class="sf-card__noimg" aria-hidden="true"></span>
            <?php } ?>
            <span class="sf-badge sf-badge--featured"><?php _e('Featured', 'storefront'); ?></span>
            <?php if (osc_premium_category() !== '') { ?>
                <span class="sf-card__cat"><?php echo osc_esc_html(osc_premium_category()); ?></span>
            <?php } ?>
        </a>
        <div class="sf-card__body">
            <div class="sf-card__pricerow">
                <?php if (osc_premium_formated_price() !== '') { ?>
                    <span class="sf-card__price"><?php echo osc_esc_html(osc_premium_formated_price()); ?></span>
                <?php } else { ?>
                    <span class="sf-card__price"><?php _e('Check with seller', 'storefront'); ?></span>
                <?php } ?>
            </div>
            <a class="sf-card__title" href="<?php echo osc_premium_url(); ?>"><?php echo osc_esc_html(osc_premium_title()); ?></a>
            <div class="sf-card__meta">
                <span class="sf-card__loc">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span><?php echo osc_premium_city() !== '' ? osc_esc_html(osc_premium_city()) : osc_esc_html(__('—', 'storefront')); ?></span>
                </span>
                <span class="sf-card__date"><?php echo osc_esc_html(osc_format_date(osc_premium_pub_date())); ?></span>
            </div>
        </div>
    </li>
    <?php
};
?>
<ul class="sf-cards">
    <?php
    if ($sf_type === 'latestItems') {
        while (osc_has_latest_items()) { $sf_item_card(); }
    } elseif ($sf_type === 'premiums') {
        while (osc_has_premiums()) { $sf_premium_card(); }
    } else {
        $sf_i = 0;
        while (osc_has_items()) {
            $sf_item_card();
            // Standard ad-injection slot every 6 results — the hook name existing
            // Osclass ad plugins already target.
            if (++$sf_i % 6 === 0) { osc_run_hook('search_ads_listing_medium'); }
        }
    }
    ?>
</ul>
