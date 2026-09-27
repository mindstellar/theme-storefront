<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Partial: "My listings" as the theme's manage table, drawn through core's
 * listing_list_html filter. The actions are core's listing_row_actions list, so a
 * plugin's action (Renew) and the paid upgrades show here as they do in core's rows.
 */

/** A POST action's token goes only to this site. */
$sf_local = static function (string $url): bool {
    return !preg_match('/[\x00-\x20\\\\]/', $url)
        && (strpos($url, rtrim(osc_base_url(), '/') . '/') === 0 || preg_match('#^/(?!/)#', $url));
};

$sf_action = static function (array $action, string $class, string $icon = '') use ($sf_local) {
    $label   = (string) $action['label'];
    $confirm = !empty($action['confirm'])
        ? ' data-confirm="' . osc_esc_html((string) $action['confirm']) . '" data-confirm-title="' . osc_esc_html((string) ($action['confirm_label'] ?? $label))
          . '" data-confirm-ok="' . osc_esc_html((string) ($action['confirm_label'] ?? $label)) . '"'
        : '';
    // Icon buttons keep their label for phones only; a text button always shows it.
    $inner = $icon !== ''
        ? storefront_icon($icon, 16) . '<span class="sf-manage__actlabel">' . osc_esc_html($label) . '</span>'
        : '<span>' . osc_esc_html($label) . '</span>';
    $aria  = ' aria-label="' . osc_esc_html($label) . '" title="' . osc_esc_html($label) . '"';

    if (($action['method'] ?? 'get') === 'post') {
        $url = (string) $action['url'];
        echo '<form class="sf-manage__form nocsrf" method="post" action="' . osc_esc_html($url) . '">';
        if ($sf_local($url)) {
            echo osc_csrf_token_form();
        }
        foreach ((array) ($action['fields'] ?? array()) as $name => $value) {
            echo '<input type="hidden" name="' . osc_esc_html((string) $name) . '" value="' . osc_esc_html((string) $value) . '">';
        }
        echo '<button type="submit" class="' . $class . '"' . $aria . $confirm . '>' . $inner . '</button></form>';

        return;
    }
    echo '<a class="' . $class . '" rel="nofollow" href="' . osc_esc_html((string) $action['url']) . '"' . $aria . $confirm . '>' . $inner . '</a>';
};
?>
<ul class="sf-manage">
    <li class="sf-manage__headrow" aria-hidden="true">
        <span><?php _e('Listing', 'storefront'); ?></span>
        <span><?php _e('Status', 'storefront'); ?></span>
        <span class="sf-manage__col-views"><?php _e('Views', 'storefront'); ?></span>
        <span class="sf-manage__col-actions"><?php _e('Actions', 'storefront'); ?></span>
    </li>
    <?php while (osc_has_items()) {
        // Pending reads muted (just wait); Expired stays loud (it needs action).
        $sf_kind = 'ok'; $sf_status = __('Active', 'storefront'); $sf_ic = 'check';
        if (!osc_item_is_enabled())    { $sf_kind = 'danger'; $sf_status = __('Blocked', 'storefront'); $sf_ic = 'ban'; }
        elseif (osc_item_is_spam())    { $sf_kind = 'danger'; $sf_status = __('Flagged', 'storefront'); $sf_ic = 'flag'; }
        elseif (osc_item_is_expired()) { $sf_kind = 'warn';   $sf_status = __('Expired', 'storefront'); $sf_ic = 'alert'; }
        elseif (!osc_item_is_active()) { $sf_kind = 'muted';  $sf_status = __('Pending', 'storefront'); $sf_ic = 'clock'; }

        $sf_item    = osc_item();
        $sf_actions = array(
            'edit'   => array('label' => __('Edit', 'storefront'), 'url' => osc_item_edit_url()),
            'delete' => array(
                'label'   => __('Delete', 'storefront'),
                'confirm_label' => __('Delete listing', 'storefront'),
                'url'     => osc_base_url(true),
                'method'  => 'post',
                'fields'  => array('page' => 'item', 'action' => 'item_delete', 'id' => (int) osc_item_id()),
                'confirm' => sprintf(__('Delete “%s”? This cannot be undone.', 'storefront'), osc_item_title()),
            ),
        );
        foreach (osc_item_upgrade_offers($sf_item) as $sf_offer) {
            $sf_actions['upgrade_' . $sf_offer['feature']] = array(
                'label'  => $sf_offer['credits'] > 0
                    ? sprintf(__('%1$s · %2$d credits', 'storefront'), $sf_offer['label'], $sf_offer['credits'])
                    : sprintf(__('%s · free', 'storefront'), $sf_offer['label']),
                'url'    => osc_item_upgrade_url((int) osc_item_id(), $sf_offer['feature']),
                'method' => 'post',
                'group'  => 'promote',
            );
        }
        $sf_actions = (array) osc_apply_filter('listing_row_actions', $sf_actions, $sf_item, 'user_items');
        $sf_promote = array_filter($sf_actions, static fn ($a) => is_array($a) && ($a['group'] ?? '') === 'promote');
    ?>
    <li class="sf-manage__row">
        <a class="sf-manage__listing" href="<?php echo osc_esc_html(osc_item_url()); ?>">
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
        <span class="sf-manage__status">
            <span class="sf-state sf-state--<?php echo $sf_kind; ?>"><?php echo storefront_icon($sf_ic, 12); ?><?php echo osc_esc_html($sf_status); ?></span>
            <?php if (osc_item_is_premium()) { ?>
                <span class="sf-state sf-state--ok"><?php echo storefront_icon('star', 12); ?><?php _e('Featured', 'storefront'); ?></span>
            <?php } ?>
        </span>
        <span class="sf-manage__views"><?php echo storefront_icon('eye', 14); ?><?php echo (int) osc_item_views(); ?><span class="sf-sr-only"> <?php _e('views', 'storefront'); ?></span></span>
        <span class="sf-manage__actions">
            <a class="sf-btn sf-btn--ghost sf-btn--icon-only" href="<?php echo osc_esc_html(osc_item_url()); ?>" aria-label="<?php echo osc_esc_html(__('View listing', 'storefront')); ?>" title="<?php echo osc_esc_html(__('View', 'storefront')); ?>"><?php echo storefront_icon('eye', 16); ?><span class="sf-manage__actlabel"><?php _e('View', 'storefront'); ?></span></a>
            <?php foreach ($sf_actions as $sf_key => $sf_act) {
                if (!is_array($sf_act) || !isset($sf_act['label'], $sf_act['url']) || ($sf_act['group'] ?? '') === 'promote') {
                    continue;
                }
                if ($sf_key === 'edit') {
                    $sf_action($sf_act, 'sf-btn sf-btn--ghost sf-btn--icon-only', 'edit');
                } elseif ($sf_key === 'delete') {
                    $sf_action($sf_act, 'sf-btn sf-btn--ghost sf-btn--icon-only sf-manage__delete', 'trash');
                } else {
                    $sf_action($sf_act, 'sf-btn sf-btn--ghost sf-btn--sm');
                }
            } ?>
        </span>
        <?php if ($sf_promote !== array()) { ?>
            <span class="sf-manage__promote">
                <span class="sf-manage__promote-label"><?php _e('Promote', 'storefront'); ?></span>
                <?php foreach ($sf_promote as $sf_act) {
                    $sf_action($sf_act, 'sf-btn sf-btn--secondary sf-btn--sm');
                } ?>
            </span>
        <?php } ?>
    </li>
    <?php } ?>
</ul>
