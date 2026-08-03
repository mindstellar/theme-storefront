<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * The account landing. Identity + tabs come from common/account-header.php;
 * this view owns the overview — an active-listings figure, the latest few
 * listings, and the first-run empty state.
 *
 * The overview stays O(1): one scoped Search whose count() is the active total
 * and whose first rows are the preview, so a seller with a thousand listings
 * pays the same as one with three. A global view-sum is deliberately NOT here —
 * it would hydrate every listing the user owns to render one integer — so the
 * "how many people looked" signal lives per card instead (osc_item_views()).
 */
osc_current_web_theme_path('common/header.php');

$sf_uid = osc_logged_user_id();

$sf_search = new Search();
$sf_search->fromUser($sf_uid);
$sf_search->order('dt_pub_date', 'DESC');
$sf_search->limit(0, 3);
$sf_items  = $sf_search->doSearch();
$sf_active = (int) $sf_search->count();

View::newInstance()->_exportVariableToView('items', $sf_items);
View::newInstance()->_exportVariableToView('sfCardViews', true);
// The dashboard's listings are all the signed-in user's own — turn on the per-card
// owner actions (view / edit / delete). loop.php gates them behind this flag.
View::newInstance()->_exportVariableToView('sfCardOwner', true);
?>
<div class="sf-account">
    <?php osc_current_web_theme_path('common/account-header.php'); ?>
    <div class="sf-account__main">
        <section class="sf-section">
            <?php if ($sf_active === 0 && count($sf_items) === 0) { ?>
                <div class="sf-empty-state">
                    <span class="sf-empty-state__icon" aria-hidden="true"><?php echo storefront_icon('tag', 30); ?></span>
                    <h2 class="sf-empty-state__title"><?php _e('Post your first listing', 'storefront'); ?></h2>
                    <p class="sf-empty-state__text"><?php _e('It\'s free and goes live within minutes. Once it\'s up, this is where you\'ll watch the views add up.', 'storefront'); ?></p>
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
                <div class="sf-section__head">
                    <div>
                        <h2 class="sf-section__title"><?php _e('Your latest listings', 'storefront'); ?></h2>
                        <p class="sf-dash-lead">
                            <strong class="sf-dash-lead__count"><?php echo $sf_active; ?></strong>
                            <?php echo osc_esc_html($sf_active === 1 ? __('active listing', 'storefront') : __('active listings', 'storefront')); ?>
                        </p>
                    </div>
                    <a class="sf-account-more" href="<?php echo osc_esc_html(osc_user_list_items_url()); ?>">
                        <?php _e('Manage all', 'storefront'); ?><?php echo storefront_icon('chevron-right', 15); ?>
                    </a>
                </div>
                <?php osc_current_web_theme_path('common/loop.php'); ?>
            <?php } ?>
        </section>
        <?php
            // PLUGIN CONTRACT — the user-dashboard widget slot. A plugin that
            // registers a panel here still gets one, below the overview.
            osc_show_widgets('user-dashboard');
        ?>
    </div>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
