<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
// The manage list's status filter lives in the account bundle.
storefront_enqueue_bundle('account');
osc_current_web_theme_path('common/header.php');
?>
<div class="sf-account">
    <?php osc_current_web_theme_path('common/account-header.php'); ?>
    <div class="sf-account__main">
        <section class="sf-section">
            <?php osc_run_hook('search_ads_listing_top'); ?>
            <?php if (osc_count_items() == 0) { ?>
                <div class="sf-empty-state">
                    <span class="sf-empty-state__icon" aria-hidden="true"><?php echo storefront_icon('list', 30); ?></span>
                    <h2 class="sf-empty-state__title"><?php _e('No listings yet', 'storefront'); ?></h2>
                    <p class="sf-empty-state__text"><?php _e('Everything you post shows up here, where you can edit it and watch the views add up.', 'storefront'); ?></p>
                    <div class="sf-empty-state__actions">
                        <a class="sf-btn sf-btn--primary sf-btn--lg" href="<?php echo osc_esc_html(osc_item_post_url_in_category()); ?>">
                            <?php echo storefront_icon('plus', 16); ?><span><?php _e('Post a listing', 'storefront'); ?></span>
                        </a>
                        <a class="sf-btn sf-btn--secondary sf-btn--lg" href="<?php echo osc_esc_html(osc_search_url()); ?>">
                            <?php echo storefront_icon('search', 16); ?><span><?php _e('Browse listings', 'storefront'); ?></span>
                        </a>
                    </div>
                </div>
            <?php } else { ?>
                <div class="sf-manage-head">
                    <h2 class="sf-section__title"><?php _e('My listings', 'storefront'); ?></h2>
                    <a class="sf-btn sf-btn--primary sf-btn--sm" href="<?php echo osc_esc_html(osc_item_post_url_in_category()); ?>">
                        <?php echo storefront_icon('plus', 15); ?><span><?php _e('Post a listing', 'storefront'); ?></span>
                    </a>
                </div>

                <?php // A responsive manage list — a table on desktop, stacked rows on mobile.
                      // This view is owner-only (core renders it for page=user&action=items,
                      // behind login), so edit/delete are always safe here. Owners also see
                      // the listing's REAL state, which a public visitor never does. ?>
                <ul class="sf-manage" data-filter-all="<?php echo osc_esc_html(__('All', 'storefront')); ?>" data-filter-label="<?php echo osc_esc_html(__('Filter listings by status', 'storefront')); ?>">
                    <li class="sf-manage__headrow" aria-hidden="true">
                        <span><?php _e('Listing', 'storefront'); ?></span>
                        <span><?php _e('Status', 'storefront'); ?></span>
                        <span class="sf-manage__col-views"><?php _e('Views', 'storefront'); ?></span>
                        <span class="sf-manage__col-actions"><?php _e('Actions', 'storefront'); ?></span>
                    </li>
                    <?php while (osc_has_items()) {
                        // Four distinct treatments, because two of these states ask for
                        // opposite responses: Pending is benign (just wait) so it reads
                        // muted, while Expired needs action so it stays warm/loud. Each
                        // also carries an icon + a slug so state survives grayscale and
                        // the client-side filter can group rows.
                        $sf_kind = 'ok'; $sf_status = __('Active', 'storefront');  $sf_ic = 'check'; $sf_slug = 'active';
                        if (!osc_item_is_enabled())    { $sf_kind = 'danger'; $sf_status = __('Blocked', 'storefront'); $sf_ic = 'ban';   $sf_slug = 'blocked'; }
                        elseif (osc_item_is_spam())    { $sf_kind = 'danger'; $sf_status = __('Flagged', 'storefront'); $sf_ic = 'flag';  $sf_slug = 'flagged'; }
                        elseif (osc_item_is_expired()) { $sf_kind = 'warn';   $sf_status = __('Expired', 'storefront'); $sf_ic = 'alert'; $sf_slug = 'expired'; }
                        elseif (!osc_item_is_active()) { $sf_kind = 'muted';  $sf_status = __('Pending', 'storefront'); $sf_ic = 'clock'; $sf_slug = 'pending'; }
                    ?>
                    <li class="sf-manage__row" data-status="<?php echo $sf_slug; ?>">
                        <a class="sf-manage__listing" href="<?php echo osc_item_url(); ?>">
                            <span class="sf-manage__thumb">
                                <?php if (osc_images_enabled_at_items() && osc_has_item_resources()) { ?>
                                    <img loading="lazy" src="<?php echo osc_esc_html(osc_resource_thumbnail_url()); ?>" alt="" width="56" height="56" />
                                <?php } else { ?>
                                    <span class="sf-card__noimg" aria-hidden="true"></span>
                                <?php } ?>
                            </span>
                            <span class="sf-manage__info">
                                <span class="sf-manage__title"><?php echo osc_esc_html(osc_item_title()); ?></span>
                                <span class="sf-manage__sub">
                                    <span class="sf-manage__price"><?php echo osc_item_formated_price() !== '' ? osc_esc_html(osc_item_formated_price()) : osc_esc_html(__('Check with seller', 'storefront')); ?></span>
                                    <span class="sf-manage__date"><?php echo osc_esc_html(osc_format_date(osc_item_pub_date())); ?></span>
                                </span>
                            </span>
                        </a>
                        <span class="sf-manage__status"><span class="sf-state sf-state--<?php echo $sf_kind; ?>"><?php echo storefront_icon($sf_ic, 12); ?><?php echo osc_esc_html($sf_status); ?></span></span>
                        <span class="sf-manage__views"><?php echo storefront_icon('eye', 14); ?><?php echo (int) osc_item_views(); ?><span class="sf-sr-only"> <?php _e('views', 'storefront'); ?></span></span>
                        <?php // Icon-only on the desktop grid; on mobile the row reflows and the
                              // buttons gain visible labels — a `title` tooltip never fires on
                              // touch, so the glyph alone would be the only cue otherwise. ?>
                        <span class="sf-manage__actions">
                            <a class="sf-btn sf-btn--ghost sf-btn--icon-only" href="<?php echo osc_item_url(); ?>" aria-label="<?php echo osc_esc_html(__('View listing', 'storefront')); ?>" title="<?php echo osc_esc_html(__('View', 'storefront')); ?>"><?php echo storefront_icon('eye', 16); ?><span class="sf-manage__actlabel"><?php _e('View', 'storefront'); ?></span></a>
                            <a class="sf-btn sf-btn--ghost sf-btn--icon-only" href="<?php echo osc_esc_html(osc_item_edit_url()); ?>" aria-label="<?php echo osc_esc_html(__('Edit listing', 'storefront')); ?>" title="<?php echo osc_esc_html(__('Edit', 'storefront')); ?>"><?php echo storefront_icon('edit', 16); ?><span class="sf-manage__actlabel"><?php _e('Edit', 'storefront'); ?></span></a>
                            <a class="sf-btn sf-btn--ghost sf-btn--icon-only sf-manage__delete" rel="nofollow" href="<?php echo osc_esc_html(osc_item_delete_url()); ?>" data-confirm="<?php echo osc_esc_html(sprintf(__('Delete “%s”? This cannot be undone.', 'storefront'), osc_item_title())); ?>" data-confirm-title="<?php echo osc_esc_html(__('Delete listing', 'storefront')); ?>" data-confirm-ok="<?php echo osc_esc_html(__('Delete listing', 'storefront')); ?>" aria-label="<?php echo osc_esc_html(__('Delete listing', 'storefront')); ?>" title="<?php echo osc_esc_html(__('Delete', 'storefront')); ?>"><?php echo storefront_icon('trash', 16); ?><span class="sf-manage__actlabel"><?php _e('Delete', 'storefront'); ?></span></a>
                        </span>
                    </li>
                    <?php } ?>
                </ul>

                <?php
                View::newInstance()->_exportVariableToView('sf_pagination_html', osc_pagination_items());
                osc_current_web_theme_path('common/pagination.php');
            } ?>
        </section>
    </div>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
