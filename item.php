<?php
/*
 * Storefront — listing detail.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Layout is ONE CSS grid with named areas (media / aside / body). The aside is
 * named in both rows, so it spans them and stays sticky beside the scrolling
 * body on desktop; the same single DOM order stacks media -> aside -> body on
 * mobile, i.e. photo, then title/price/seller, then the description. No second
 * copy of the title, no order: juggling.
 *
 * Showcases the 6.0.0 platform: featured/related listings, the custom-fields
 * API, friendly-named resource downloads, widget zones and the standard hooks.
 * View counts are recorded by core's cache-safe beacon (injected on after_html),
 * so this template never counts a view at render time.
 *
 * PRESERVED CONTRACTS: osc_run_hook('item_meta' | 'item_detail' | 'item_before_related'
 * | 'item_after_related' | 'item_sidebar'), osc_show_widgets('item_sidebar'), and the
 * contact form field names (see item-contact.php).
 */
// Item detail behaviour: gallery + lightbox, share sheet, lazy map embed.
storefront_enqueue_bundle('item');
osc_current_web_theme_path('common/header.php');

$sf_item_id   = (int) osc_item_id();
$sf_seller_id = (int) osc_item_user_id();
$sf_expired   = osc_item_is_expired();
$sf_inactive  = osc_item_is_inactive();
$sf_is_owner  = ($sf_seller_id === (int) osc_logged_user_id()) && osc_logged_user_id() != 0;
$sf_price_state = storefront_price_state();

// Inline icon set (feather-style, one consistent stroke). Drawn SVG, never glyphs.
// The map lives in storefront_icon() (functions.php) so partials share it; this
// page-local alias keeps the terse $sf_icon(...) call sites below.
$sf_icon = static fn ($name, $size = 16) => storefront_icon($name, $size);

// Gather photos once (head/beacon may also walk the resource list, so leave the
// pointer where it started).
$sf_photos = array();
if (osc_images_enabled_at_items()) {
    while (osc_has_item_resources()) {
        $sf_photos[] = array(
            'thumb' => osc_resource_thumbnail_url(),
            'large' => osc_resource_preview_url(),
            'full'  => osc_resource_url(),
            'dl'    => function_exists('osc_resource_download_url') ? osc_resource_download_url() : osc_resource_url(),
        );
    }
    osc_reset_resources();
}

// One location string, built once — deduped so a same-named city/region reads
// "Delhi, India", not "Delhi, Delhi, India".
$sf_location = storefront_location_line(array(
    osc_item_city_area(), osc_item_city(), osc_item_region(), osc_item_country(),
));

// Location map — an interactive, lazy map (nothing external loads until "Show map"
// is pressed; tiles/APIs are billed or rate-limited). Honours the admin's provider
// (Settings → Listings → map type): the Google Maps JS API when a key is set, else
// Leaflet + OpenStreetMap (keyless). When a listing has no geocoded coordinates —
// the common case — the address is geocoded client-side so a map still appears. All
// the loading and rendering is in bindMap() (js/storefront-item.js); PHP only hands
// it the provider, key, coordinates and address.
$sf_map_type = osc_item_map_type();                       // '' | 'google' | 'openstreet'
$sf_map_key  = trim((string) osc_google_maps_api_key());
$sf_map_addr = storefront_location_line(array(
    osc_item_address(), osc_item_city(), osc_item_region(), osc_item_country(),
));
$sf_lat = osc_item_latitude();
$sf_lon = osc_item_longitude();
$sf_has_coords = ($sf_lat != 0.0 || $sf_lon != 0.0);
$sf_has_loc = $sf_has_coords || $sf_map_addr !== '';
// Google needs an API key; OpenStreetMap is keyless.
$sf_show_map = $sf_has_loc && ($sf_map_type === 'openstreet'
    || ($sf_map_type === 'google' && $sf_map_key !== ''));
// "Open in full map" — an external link under the map, per provider.
$sf_map_link = '';
if ($sf_show_map && $sf_map_type === 'google') {
    $sf_map_link = 'https://www.google.com/maps/search/?api=1&query='
        . rawurlencode($sf_has_coords ? ($sf_lat . ',' . $sf_lon) : $sf_map_addr);
} elseif ($sf_show_map && $sf_has_coords) {
    $sf_map_link = 'https://www.openstreetmap.org/?mlat=' . rawurlencode((string) $sf_lat)
        . '&mlon=' . rawurlencode((string) $sf_lon) . '#map=16/' . $sf_lat . '/' . $sf_lon;
} elseif ($sf_show_map) {
    $sf_map_link = 'https://www.openstreetmap.org/search?query=' . rawurlencode($sf_map_addr);
}

// Seller facts, read straight off the user row (no view-state mutation, so the
// contact form's own prefill is untouched).
$sf_seller = $sf_seller_id ? User::newInstance()->findByPrimaryKey($sf_seller_id) : array();
$sf_is_company = !empty($sf_seller['b_company']);
$sf_since      = isset($sf_seller['dt_reg_date']) ? $sf_seller['dt_reg_date'] : '';
$sf_seller_url = $sf_seller_id ? osc_user_public_profile_url($sf_seller_id) : '';
$sf_seller_count = $sf_seller_id ? (int) Item::newInstance()->countByUserIDEnabled($sf_seller_id) : 0;
// A days-old account with barely any listings shows a clear "New seller" note instead
// of a bare "Member since <this year>", so the buyer's trust expectation is set
// honestly. A young account that already has a real catalogue isn't framed as new.
$sf_seller_new = $sf_since !== '' && $sf_seller_count <= 2
    && (time() - strtotime($sf_since)) < 45 * 86400;
$sf_phone = '';
if (!empty($sf_seller['s_phone_mobile']))    { $sf_phone = $sf_seller['s_phone_mobile']; }
elseif (!empty($sf_seller['s_phone_land']))  { $sf_phone = $sf_seller['s_phone_land']; }

// Seller name as shown on the listing; its initial seeds the avatar.
$sf_seller_name = osc_item_contact_name();
$sf_initial = $sf_seller_name !== '' ? mb_strtoupper(mb_substr(trim($sf_seller_name), 0, 1)) : '?';

// Contact eligibility.
$sf_must_login  = osc_reg_user_can_contact() && !osc_is_web_user_logged_in();
$sf_can_contact = !$sf_expired && !$sf_is_owner && !$sf_must_login;

// Adjacent live listings (rendered only when a neighbour exists).
$sf_prev = storefront_adjacent_item_url('prev');
$sf_next = storefront_adjacent_item_url('next');
?>

<div class="sf-crumbs">
    <a href="<?php echo osc_base_url(); ?>"><?php _e('Home', 'storefront'); ?></a> &nbsp;/&nbsp;
    <a href="<?php echo osc_search_category_url(); ?>"><?php echo osc_esc_html(osc_item_category()); ?></a> &nbsp;/&nbsp;
    <?php echo osc_esc_html(osc_item_title()); ?>
</div>

<?php if ($sf_expired || $sf_inactive) { ?>
<p class="sf-alert sf-alert--warning" role="status">
    <?php echo $sf_icon('alert', 18); ?>
    <span><?php echo $sf_expired
        ? osc_esc_html(__('This listing has expired — you can no longer contact the seller.', 'storefront'))
        : osc_esc_html(__('This listing is not active yet.', 'storefront')); ?></span>
</p>
<?php } ?>

<article class="sf-detail__grid">

    <!-- ── media ─────────────────────────────────────────────────────────── -->
    <div class="sf-detail__media">
        <?php if ($sf_photos) { ?>
        <figure class="sf-gallery" data-gallery>
            <button type="button" class="sf-gallery__stage" data-gallery-open
                    aria-label="<?php echo osc_esc_html(__('View photo full size', 'storefront')); ?>">
                <img src="<?php echo osc_esc_html($sf_photos[0]['large']); ?>"
                     alt="<?php echo osc_esc_html(osc_item_title()); ?>"
                     data-gallery-stage width="800" height="600" />
                <span class="sf-gallery__zoom"><?php echo $sf_icon('image', 14); ?><?php echo osc_esc_html(count($sf_photos) > 1 ? sprintf(__('%d photos', 'storefront'), count($sf_photos)) : __('View', 'storefront')); ?></span>
            </button>

            <?php if (count($sf_photos) > 1) { ?>
            <ul class="sf-gallery__thumbs">
                <?php foreach ($sf_photos as $sf_i => $sf_p) { ?>
                <li>
                    <button type="button" class="sf-gallery__thumb"
                            data-gallery-select="<?php echo (int) $sf_i; ?>"
                            data-large="<?php echo osc_esc_html($sf_p['large']); ?>"
                            aria-pressed="<?php echo $sf_i === 0 ? 'true' : 'false'; ?>"
                            aria-label="<?php echo osc_esc_html(sprintf(__('Photo %1$d of %2$d', 'storefront'), $sf_i + 1, count($sf_photos))); ?>">
                        <img src="<?php echo osc_esc_html($sf_p['thumb']); ?>" alt="" loading="lazy" width="140" height="140" />
                    </button>
                </li>
                <?php } ?>
            </ul>
            <?php } ?>
        </figure>
        <?php } else { ?>
        <div class="sf-gallery__empty">
            <?php echo $sf_icon('image', 40); ?>
            <span><?php _e('No photo for this listing', 'storefront'); ?></span>
        </div>
        <?php } ?>
    </div>

    <!-- ── aside: the sticky column ──────────────────────────────────────── -->
    <aside class="sf-detail__aside">
        <div class="sf-detail__meta">
            <?php if (osc_item_category() !== '') { ?>
                <a class="tag tag-neutral" href="<?php echo osc_search_category_url(); ?>"><?php echo osc_esc_html(osc_item_category()); ?></a>
            <?php } ?>
            <time class="sf-detail__date" datetime="<?php echo osc_esc_html(osc_item_pub_date()); ?>"><?php echo osc_esc_html(osc_format_date(osc_item_pub_date())); ?></time>
        </div>

        <h1 class="sf-detail__title"><?php echo osc_esc_html(osc_item_title()); ?></h1>

        <?php if ($sf_price_state === 'price') { ?>
            <p class="sf-detail__price" data-price-state="price"><?php echo osc_esc_html(osc_item_formated_price()); ?></p>
        <?php } elseif ($sf_price_state === 'free') { ?>
            <p class="sf-detail__price" data-price-state="free"><?php _e('Free', 'storefront'); ?></p>
        <?php } else { ?>
            <p class="sf-detail__price" data-price-state="ask"><?php _e('Check with seller', 'storefront'); ?></p>
        <?php } ?>

        <?php if ($sf_location !== '') { ?>
            <p class="sf-detail__location"><?php echo $sf_icon('map-pin', 15); ?><span><?php echo osc_esc_html($sf_location); ?></span></p>
        <?php } ?>

        <div class="sf-detail__actions">
            <button type="button" class="sf-btn sf-btn--ghost" data-dialog-open="sf-dialog-share">
                <?php echo $sf_icon('share'); ?><?php _e('Share', 'storefront'); ?>
            </button>
            <?php if (!$sf_is_owner) { ?>
            <button type="button" class="sf-btn sf-btn--ghost" data-dialog-open="sf-dialog-report">
                <?php echo $sf_icon('flag'); ?><?php _e('Report', 'storefront'); ?>
            </button>
            <?php } ?>
        </div>

        <?php if ($sf_is_owner) { ?>
        <div class="sf-detail__owner">
            <p class="sf-detail__owner-label"><?php _e('This is your listing.', 'storefront'); ?></p>
            <div class="sf-detail__owner-actions">
                <a class="sf-btn sf-btn--ghost" href="<?php echo osc_item_edit_url(); ?>"><?php echo $sf_icon('edit'); ?><?php _e('Edit', 'storefront'); ?></a>
                <?php if (function_exists('osc_item_delete_url')) { ?>
                <a class="sf-btn sf-btn--ghost" rel="nofollow" href="<?php echo osc_item_delete_url(); ?>"
                   data-confirm="<?php echo osc_esc_html(sprintf(__('Delete “%s”? This cannot be undone.', 'storefront'), osc_item_title())); ?>"
                   data-confirm-title="<?php echo osc_esc_html(__('Delete listing', 'storefront')); ?>"
                   data-confirm-ok="<?php echo osc_esc_html(__('Delete listing', 'storefront')); ?>">
                    <?php echo $sf_icon('trash'); ?><?php _e('Delete', 'storefront'); ?>
                </a>
                <?php } ?>
            </div>
        </div>
        <?php } ?>

        <?php
        // Admin moderation bar — shown only to a signed-in administrator, on the
        // page where the judgement is actually made. Shows the listing's REAL state
        // (which a visitor never sees) and acts through the CSRF-guarded, admin-gated
        // storefront-item-admin route. Each button is a POST, never a bot-hittable GET.
        if (osc_is_admin_user_logged_in()) {
            $sf_admin_action = static function ($action, $label, $icon, $danger = false) use ($sf_item_id, $sf_icon) {
                ?>
                <form method="post" action="<?php echo osc_esc_html(osc_base_url(true)); ?>" class="sf-admin__form">
                    <input type="hidden" name="route" value="storefront-item-admin" />
                    <input type="hidden" name="id" value="<?php echo (int) $sf_item_id; ?>" />
                    <input type="hidden" name="admin_action" value="<?php echo osc_esc_html($action); ?>" />
                    <button type="submit" class="sf-btn <?php echo $danger ? 'sf-btn--danger' : 'sf-btn--ghost'; ?>">
                        <?php echo $sf_icon($icon, 15); ?><?php echo osc_esc_html($label); ?>
                    </button>
                </form>
                <?php
            };
        ?>
        <section class="sf-admin">
            <h2 class="sf-admin__head"><?php echo $sf_icon('settings', 13); ?><?php _e('Admin', 'storefront'); ?></h2>

            <ul class="sf-admin__state">
                <?php if (!osc_item_is_enabled()) { ?><li class="sf-state sf-state--danger"><?php _e('Blocked', 'storefront'); ?></li><?php } ?>
                <?php if ($sf_inactive) { ?><li class="sf-state sf-state--warn"><?php _e('Inactive', 'storefront'); ?></li><?php } ?>
                <?php if (osc_item_is_spam()) { ?><li class="sf-state sf-state--danger"><?php _e('Spam', 'storefront'); ?></li><?php } ?>
                <?php if ($sf_expired) { ?><li class="sf-state sf-state--warn"><?php _e('Expired', 'storefront'); ?></li><?php } ?>
                <?php if (osc_item_is_premium()) { ?><li class="sf-state sf-state--ok"><?php _e('Featured', 'storefront'); ?></li><?php } ?>
                <?php if (osc_item_is_enabled() && osc_item_is_active() && !osc_item_is_spam() && !$sf_expired) { ?><li class="sf-state sf-state--ok"><?php _e('Live', 'storefront'); ?></li><?php } ?>
            </ul>

            <div class="sf-admin__actions">
                <?php
                osc_item_is_enabled() ? $sf_admin_action('block', __('Block', 'storefront'), 'lock', true)
                                      : $sf_admin_action('unblock', __('Unblock', 'storefront'), 'check');
                osc_item_is_active()  ? $sf_admin_action('deactivate', __('Deactivate', 'storefront'), 'ban')
                                      : $sf_admin_action('activate', __('Activate', 'storefront'), 'check');
                osc_item_is_spam()    ? $sf_admin_action('unspam', __('Not spam', 'storefront'), 'check')
                                      : $sf_admin_action('spam', __('Mark spam', 'storefront'), 'flag', true);
                osc_item_is_premium() ? $sf_admin_action('unpremium', __('Unfeature', 'storefront'), 'star')
                                      : $sf_admin_action('premium', __('Feature', 'storefront'), 'star');
                ?>
            </div>

            <a class="sf-admin__link" href="<?php echo osc_esc_html(osc_admin_base_url(true) . '?page=items&action=item_edit&id=' . $sf_item_id); ?>">
                <?php echo $sf_icon('edit', 14); ?><?php _e('Open in admin', 'storefront'); ?>
            </a>
        </section>
        <?php } ?>

        <section class="sf-seller">
            <h2 class="sf-seller__head"><?php _e('Seller', 'storefront'); ?></h2>

            <div class="sf-seller__id">
                <span class="sf-seller__avatar" aria-hidden="true">
                    <?php if (sf_has_avatar($sf_seller_id)) { ?>
                        <img src="<?php echo osc_esc_html(osc_user_avatar_url($sf_seller_id, 'thumbnail')); ?>" alt="" width="44" height="44" />
                    <?php } else { ?>
                        <?php echo osc_esc_html($sf_initial); ?>
                    <?php } ?>
                </span>
                <div class="sf-seller__name">
                    <?php if ($sf_seller_url !== '') { ?>
                        <a href="<?php echo osc_esc_html($sf_seller_url); ?>"><?php echo osc_esc_html($sf_seller_name); ?></a>
                    <?php } else { ?>
                        <span><?php echo osc_esc_html($sf_seller_name); ?></span>
                    <?php } ?>
                    <span class="sf-seller__kind"><?php echo $sf_is_company ? osc_esc_html(__('Company', 'storefront')) : osc_esc_html(__('Individual', 'storefront')); ?></span>
                </div>
            </div>

            <?php if ($sf_seller_new || $sf_since !== '' || $sf_seller_count > 0) { ?>
            <ul class="sf-seller__facts">
                <?php if ($sf_seller_new) { ?>
                    <li><span class="sf-seller__badge"><?php _e('New seller', 'storefront'); ?></span></li>
                <?php } elseif ($sf_since !== '') { ?>
                    <li><?php printf(osc_esc_html(__('Member since %s', 'storefront')), osc_esc_html(date('Y', strtotime($sf_since)))); ?></li>
                <?php } ?>
                <?php if ($sf_seller_count > 0) {
                    $sf_count_label = sprintf(_n('%d listing', '%d listings', $sf_seller_count, 'storefront'), $sf_seller_count); ?>
                    <li><?php echo $sf_seller_url !== ''
                        ? '<a href="' . osc_esc_html($sf_seller_url) . '">' . osc_esc_html($sf_count_label) . '</a>'
                        : osc_esc_html($sf_count_label); ?></li>
                <?php } ?>
            </ul>
            <?php } ?>

            <div class="sf-seller__actions">
                <?php if ($sf_expired) { ?>
                    <p class="sf-seller__note"><?php _e('This listing has expired.', 'storefront'); ?></p>
                <?php } elseif ($sf_is_owner) { ?>
                    <p class="sf-seller__note"><?php _e("It's your own listing — you can't contact yourself.", 'storefront'); ?></p>
                <?php } elseif ($sf_must_login) { ?>
                    <p class="sf-seller__note"><?php _e('Log in to contact the seller.', 'storefront'); ?></p>
                    <a class="sf-btn sf-btn--primary" href="<?php echo osc_user_login_url(); ?>"><?php _e('Log in', 'storefront'); ?></a>
                    <a class="sf-btn sf-btn--ghost" href="<?php echo osc_register_account_url(); ?>"><?php _e('Create a free account', 'storefront'); ?></a>
                <?php } else { ?>
                    <button type="button" class="sf-btn sf-btn--primary" data-dialog-open="sf-dialog-contact">
                        <?php echo $sf_icon('mail'); ?><?php _e('Message seller', 'storefront'); ?>
                    </button>
                    <?php if ($sf_phone !== '') { ?>
                    <details class="sf-phone">
                        <summary class="sf-btn sf-btn--ghost"><?php echo $sf_icon('phone'); ?><?php _e('Show phone number', 'storefront'); ?></summary>
                        <a class="sf-phone__num" href="tel:<?php echo osc_esc_html(preg_replace('/[^0-9+]/', '', $sf_phone)); ?>"><?php echo osc_esc_html($sf_phone); ?></a>
                    </details>
                    <?php } ?>
                <?php } ?>
            </div>

            <p class="sf-safetynote"><?php _e('Never pay in advance. Meet in a public place and inspect the item before paying.', 'storefront'); ?></p>
        </section>

        <?php // Widget zone + hook (registered widgets / plugins render into the seller rail). ?>
        <?php osc_show_widgets('item_sidebar'); ?>
        <?php osc_run_hook('item_sidebar', osc_item()); ?>
    </aside>

    <!-- ── body: description, details, map, related ───────────────────────── -->
    <div class="sf-detail__body">
        <section class="sf-detail__section">
            <h2><?php _e('Description', 'storefront'); ?></h2>
            <div class="sf-prose"><?php echo osc_item_description(); // core-formatted; not escaped ?></div>
        </section>

        <?php
        // Details: the buyer's spec sheet — the listing's custom fields, first and
        // alone. System metadata (listing id, view count) moves to the muted reference
        // line below, so "0 views" and "#141" no longer lead a buyer's scan ahead of
        // Make/Year/Condition. Gather first so a listing with no custom fields skips the
        // whole section instead of rendering an empty table. Values keyed by field id;
        // osc_item_meta_value() already formats each by type (URL -> link, checkbox,
        // date, dropdown).
        $sf_vals  = array();  // field id => formatted value
        $sf_names = array();  // field id => field name
        if (osc_count_item_meta() > 0) {
            while (osc_has_item_meta()) {
                // Most types render correctly straight from core's osc_item_meta_value()
                // (URL -> anchor, DATE, TEXTAREA, dropdown). Three are fixed/upgraded by
                // resolving the real type: CHECKBOX (core emits <img images/tick.png>, a
                // file this theme does not ship -> a broken image), and EMAIL/PHONE
                // (registry types stored as TEXT, so the legacy helper prints them plain).
                $sf_row  = osc_item_meta();
                $sf_type = function_exists('osc_field_resolve_type')
                    ? osc_field_resolve_type($sf_row) : ($sf_row['e_type'] ?? 'TEXT');
                $sf_raw  = isset($sf_row['s_value']) ? (string) $sf_row['s_value'] : '';

                if ($sf_type === 'CHECKBOX') {
                    if ($sf_raw === '' || $sf_raw === '0') { continue; } // show only ticked features
                    $sf_disp = '<span class="sf-spec-yes">' . storefront_icon('check', 15)
                        . osc_esc_html(__('Yes', 'storefront')) . '</span>';
                } elseif ($sf_type === 'EMAIL') {
                    if (trim($sf_raw) === '') { continue; }
                    $sf_disp = '<a href="mailto:' . osc_esc_html($sf_raw) . '">' . osc_esc_html($sf_raw) . '</a>';
                } elseif ($sf_type === 'PHONE') {
                    if (trim($sf_raw) === '') { continue; }
                    $sf_disp = '<a href="tel:' . osc_esc_html(preg_replace('/[^0-9+]/', '', $sf_raw)) . '">'
                        . osc_esc_html($sf_raw) . '</a>';
                } else {
                    $sf_disp = osc_item_meta_value();
                    if (trim((string) $sf_disp) === '') { continue; }
                }

                $sf_fid = (int) osc_item_meta_id();
                $sf_vals[$sf_fid]  = $sf_disp;
                $sf_names[$sf_fid] = osc_item_meta_name();
            }
        }

        // Partition into the category's field groups (6.0 field groups, category
        // inheritance honoured by core), each a titled block in the admin's order;
        // whatever no group claims stays a loose block in field-loop order. Installs
        // without groups leave $sf_groups empty and render one flat table as before.
        $sf_groups = array();
        if (!empty($sf_vals) && function_exists('osc_get_category_field_groups')) {
            foreach (osc_get_category_field_groups(osc_item_category_id()) as $sf_g) {
                $sf_rows = array();
                foreach ((array) ($sf_g['fields'] ?? array()) as $sf_f) {
                    $sf_fid = (int) ($sf_f['pk_i_id'] ?? 0);
                    if (isset($sf_vals[$sf_fid])) {
                        $sf_rows[] = array('name' => $sf_f['s_name'], 'value' => $sf_vals[$sf_fid]);
                        unset($sf_vals[$sf_fid]); // claimed — keep out of the loose remainder
                    }
                }
                if (!empty($sf_rows)) {
                    $sf_groups[] = array('title' => (string) $sf_g['s_name'], 'rows' => $sf_rows);
                }
            }
        }
        $sf_loose = array();  // ungrouped remainder, original order preserved
        foreach ($sf_vals as $sf_fid => $sf_val) {
            $sf_loose[] = array('name' => $sf_names[$sf_fid], 'value' => $sf_val);
        }
        $sf_has_specs = !empty($sf_groups) || !empty($sf_loose);
        ?>
        <?php
        // The listing-id + view-count reference — a footnote, rendered once: under the
        // spec table when there is one (so it reads as a note to the specs, not a stray
        // line between sections), else as a standalone muted line.
        $sf_ref = static function () use ($sf_item_id) { ?>
            <p class="sf-detail__ref">
                <?php printf(osc_esc_html(__('Listing #%d', 'storefront')), $sf_item_id); ?>
                <?php if (osc_item_views() > 0) { ?>
                    <span class="sf-detail__ref-sep" aria-hidden="true">&middot;</span>
                    <?php printf(osc_esc_html(_n('%s view', '%s views', osc_item_views(), 'storefront')), number_format(osc_item_views())); ?>
                <?php } ?>
            </p>
        <?php }; ?>
        <?php
        // One table printer, shared by grouped and loose blocks.
        $sf_spec_table = static function (array $rows) { ?>
            <table class="table sf-detail__table">
                <tbody>
                    <?php foreach ($rows as $sf_s) { ?>
                    <tr>
                        <th scope="row"><?php echo osc_esc_html($sf_s['name']); ?></th>
                        <td><?php echo $sf_s['value']; // core-formatted (may be an anchor for URL fields) ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php }; ?>
        <?php if ($sf_has_specs) { ?>
        <section class="sf-detail__section">
            <h2><?php _e('Details', 'storefront'); ?></h2>
            <?php if (empty($sf_groups)) { ?>
                <?php $sf_spec_table($sf_loose); ?>
            <?php } else { ?>
                <?php foreach ($sf_groups as $sf_grp) { ?>
                <div class="sf-spec-group">
                    <h3 class="sf-spec-group__title"><?php echo osc_esc_html($sf_grp['title']); ?></h3>
                    <?php $sf_spec_table($sf_grp['rows']); ?>
                </div>
                <?php } ?>
                <?php if (!empty($sf_loose)) { ?>
                <div class="sf-spec-group">
                    <h3 class="sf-spec-group__title"><?php _e('Other details', 'storefront'); ?></h3>
                    <?php $sf_spec_table($sf_loose); ?>
                </div>
                <?php } ?>
            <?php } ?>
            <?php $sf_ref(); ?>
        </section>
        <?php } else { ?>
            <?php $sf_ref(); ?>
        <?php } ?>

        <?php osc_run_hook('item_meta', osc_item()); // plugin contract: custom-field plugins inject here ?>

        <?php if ($sf_show_map) { ?>
        <section class="sf-detail__section">
            <h2><?php _e('Location', 'storefront'); ?></h2>
            <div class="sf-map" data-map
                 data-map-provider="<?php echo osc_esc_html($sf_map_type); ?>"
                 data-map-key="<?php echo osc_esc_html($sf_map_key); ?>"
                 data-map-lat="<?php echo osc_esc_html((string) $sf_lat); ?>"
                 data-map-lng="<?php echo osc_esc_html((string) $sf_lon); ?>"
                 data-map-address="<?php echo osc_esc_html($sf_map_addr); ?>"
                 data-map-error="<?php echo osc_esc_html(__("We couldn't load the map for this location.", 'storefront')); ?>">
                <button type="button" class="sf-map__load" data-map-load>
                    <?php echo $sf_icon('map-pin'); ?><span><?php _e('Show map', 'storefront'); ?></span>
                </button>
                <div class="sf-map__canvas" data-map-canvas hidden></div>
            </div>
            <?php if ($sf_map_link !== '') { ?>
            <p class="sf-map__link">
                <a href="<?php echo osc_esc_html($sf_map_link); ?>" target="_blank" rel="noopener nofollow"><?php _e('Open in full map', 'storefront'); ?><?php echo $sf_icon('external-link', 13); ?></a>
            </p>
            <?php } ?>
        </section>
        <?php } ?>

        <?php osc_run_hook('item_detail', osc_item()); // plugin contract: gateways, custom-field plugins ?>

        <?php // Comments (list + post form). Renders nothing when comments are disabled.
        osc_current_web_theme_path('common/item-comments.php'); ?>

        <?php // Related listings: same category, live only. Hook-wrapped for plugins.
        $sf_related = storefront_related_items(4);
        if (!empty($sf_related)) { ?>
        <section class="sf-related">
            <h2 class="sf-related__title"><?php _e('Related listings', 'storefront'); ?></h2>
            <?php osc_run_hook('item_before_related', osc_item()); ?>
            <?php
            View::newInstance()->_exportVariableToView('items', $sf_related);
            View::newInstance()->_reset('items');
            View::newInstance()->_exportVariableToView('listType', 'items');
            osc_current_web_theme_path('common/loop.php');
            ?>
            <?php osc_run_hook('item_after_related', osc_item()); ?>
        </section>
        <?php } ?>
    </div>
</article>

<?php if ($sf_prev !== '' || $sf_next !== '') { ?>
<nav class="sf-itemnav" aria-label="<?php echo osc_esc_html(__('Listing navigation', 'storefront')); ?>">
    <?php if ($sf_prev !== '') { ?>
    <a class="sf-itemnav__link sf-itemnav__link--prev" href="<?php echo osc_esc_html($sf_prev); ?>" rel="prev">
        <?php echo $sf_icon('chevron-left'); ?><span><?php _e('Previous listing', 'storefront'); ?></span>
    </a>
    <?php } ?>
    <?php if ($sf_next !== '') { ?>
    <a class="sf-itemnav__link sf-itemnav__link--next" href="<?php echo osc_esc_html($sf_next); ?>" rel="next">
        <span><?php _e('Next listing', 'storefront'); ?></span><?php echo $sf_icon('chevron-right'); ?>
    </a>
    <?php } ?>
</nav>
<?php } ?>

<?php // Sticky mobile contact bar — the primary action follows the buyer down the page
      // once the in-rail seller card scrolls off (js/storefront-item.js reveals it).
      // Desktop keeps the sticky rail instead, so this is mobile-only in CSS. ?>
<?php if ($sf_can_contact) { ?>
<div class="sf-buybar" data-buybar hidden>
    <div class="sf-buybar__price">
        <?php if ($sf_price_state === 'price') { ?>
            <span class="sf-buybar__amount"><?php echo osc_esc_html(osc_item_formated_price()); ?></span>
        <?php } elseif ($sf_price_state === 'free') { ?>
            <span class="sf-buybar__amount"><?php _e('Free', 'storefront'); ?></span>
        <?php } else { ?>
            <span class="sf-buybar__amount sf-buybar__amount--ask"><?php _e('Check with seller', 'storefront'); ?></span>
        <?php } ?>
        <span class="sf-buybar__title"><?php echo osc_esc_html(osc_item_title()); ?></span>
    </div>
    <button type="button" class="sf-btn sf-btn--primary" data-dialog-open="sf-dialog-contact">
        <?php echo $sf_icon('mail'); ?><span><?php _e('Message seller', 'storefront'); ?></span>
    </button>
</div>
<?php } ?>

<?php // ════════════════ dialogs ════════════════ ?>

<?php if ($sf_can_contact) { ?>
<dialog class="sf-dialog" id="sf-dialog-contact" aria-labelledby="sf-dialog-contact-title">
    <div class="sf-dialog__head">
        <h2 class="sf-dialog__title" id="sf-dialog-contact-title"><?php _e('Message seller', 'storefront'); ?></h2>
        <button type="button" class="sf-dialog__close" data-dialog-close aria-label="<?php echo osc_esc_html(__('Close', 'storefront')); ?>"><?php echo $sf_icon('x', 18); ?></button>
    </div>
    <div class="sf-dialog__body">
        <?php osc_current_web_theme_path('item-contact.php'); ?>
    </div>
</dialog>
<?php } ?>

<dialog class="sf-dialog" id="sf-dialog-share" aria-labelledby="sf-dialog-share-title">
    <div class="sf-dialog__head">
        <h2 class="sf-dialog__title" id="sf-dialog-share-title"><?php _e('Share this listing', 'storefront'); ?></h2>
        <button type="button" class="sf-dialog__close" data-dialog-close aria-label="<?php echo osc_esc_html(__('Close', 'storefront')); ?>"><?php echo $sf_icon('x', 18); ?></button>
    </div>
    <div class="sf-dialog__body">
        <div class="sf-share__copy">
            <input class="sf-input" type="text" readonly data-share-url value="<?php echo osc_esc_html(osc_item_url()); ?>"
                   aria-label="<?php echo osc_esc_html(__('Listing link', 'storefront')); ?>" />
            <button type="button" class="sf-btn sf-btn--primary" data-share-copy
                    data-copied="<?php echo osc_esc_html(__('Link copied to clipboard.', 'storefront')); ?>"
                    data-select="<?php echo osc_esc_html(__('Link selected — press Ctrl+C to copy.', 'storefront')); ?>">
                <?php echo $sf_icon('copy'); ?><?php _e('Copy', 'storefront'); ?>
            </button>
        </div>
        <p class="sf-share__status" role="status" data-share-status></p>

        <button type="button" class="sf-btn sf-btn--primary sf-btn--block" data-share-native hidden
                data-share-title="<?php echo osc_esc_html(osc_item_title()); ?>"
                data-share-url="<?php echo osc_esc_html(osc_item_url()); ?>">
            <?php echo $sf_icon('share'); ?><?php _e('Share…', 'storefront'); ?>
        </button>

        <?php // The device share sheet (above, mobile) covers everything else; keep just
              // the two channels that carry local classifieds — WhatsApp and Facebook. ?>
        <div class="sf-share__targets">
            <a class="sf-btn sf-btn--ghost" target="_blank" rel="noopener noreferrer" href="https://wa.me/?text=<?php echo urlencode(osc_item_title() . "\n" . osc_item_url()); ?>">WhatsApp</a>
            <a class="sf-btn sf-btn--ghost" target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(osc_item_url()); ?>">Facebook</a>
        </div>
    </div>
</dialog>

<?php if (!$sf_is_owner) { ?>
<dialog class="sf-dialog" id="sf-dialog-report" aria-labelledby="sf-dialog-report-title">
    <div class="sf-dialog__head">
        <h2 class="sf-dialog__title" id="sf-dialog-report-title"><?php _e('Report this listing', 'storefront'); ?></h2>
        <button type="button" class="sf-dialog__close" data-dialog-close aria-label="<?php echo osc_esc_html(__('Close', 'storefront')); ?>"><?php echo $sf_icon('x', 18); ?></button>
    </div>
    <div class="sf-dialog__body">
        <p class="sf-muted" style="margin-top:0;"><?php _e("Tell us what's wrong. Reports are confidential.", 'storefront'); ?></p>
        <?php osc_current_web_theme_path('common/item-sidebar.php'); ?>
    </div>
</dialog>
<?php } ?>

<?php if ($sf_photos) { ?>
<dialog class="sf-dialog sf-lightbox" id="sf-dialog-lightbox" aria-label="<?php echo osc_esc_html(__('Photo viewer', 'storefront')); ?>">
    <button type="button" class="sf-lightbox__btn sf-lightbox__close" data-dialog-close aria-label="<?php echo osc_esc_html(__('Close', 'storefront')); ?>"><?php echo $sf_icon('x', 20); ?></button>
    <?php // Seeded with the first photo (a real fallback if JS is off); the closed
          // dialog is display:none so nothing loads until opened. gallery.js swaps
          // in the selected photo on open and on prev/next. ?>
    <img src="<?php echo osc_esc_html($sf_photos[0]['full']); ?>" class="sf-lightbox__img" data-lightbox-img alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
    <?php if (count($sf_photos) > 1) { ?>
    <button type="button" class="sf-lightbox__btn sf-lightbox__nav sf-lightbox__nav--prev" data-lightbox-step="-1" aria-label="<?php echo osc_esc_html(__('Previous photo', 'storefront')); ?>"><?php echo $sf_icon('chevron-left', 24); ?></button>
    <button type="button" class="sf-lightbox__btn sf-lightbox__nav sf-lightbox__nav--next" data-lightbox-step="1" aria-label="<?php echo osc_esc_html(__('Next photo', 'storefront')); ?>"><?php echo $sf_icon('chevron-right', 24); ?></button>
    <?php } ?>
    <div class="sf-lightbox__bar">
        <?php if (count($sf_photos) > 1) { ?><span class="sf-lightbox__count" data-lightbox-count aria-live="polite"></span><?php } ?>
        <a class="sf-lightbox__dl sf-btn sf-btn--ghost" data-lightbox-dl target="_blank" rel="noopener" href="#"><?php echo $sf_icon('download'); ?><?php _e('Download', 'storefront'); ?></a>
    </div>
</dialog>
<script type="application/json" id="sf-gallery-data"><?php
    echo json_encode(array_map(static function ($p) {
        return array('full' => $p['full'], 'large' => $p['large'], 'dl' => $p['dl']);
    }, $sf_photos), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
?></script>
<?php } ?>

<?php osc_current_web_theme_path('common/footer.php'); ?>
