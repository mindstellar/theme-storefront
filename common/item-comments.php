<?php
/*
 * Storefront: item comments. Core prints the block (list, rating stars, form) and
 * css/storefront.css styles its .oe-* classes. Nothing prints when comments are off.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
// A listing waiting for payment is not live, so it takes no comments yet.
if (!osc_comments_enabled() || (function_exists('osc_item_is_unpaid') && osc_item_is_unpaid())) {
    return;
}
// The delete link opens the theme's confirm dialog, naming what goes; core 7.0 sets no prompt of its own.
ob_start();
osc_show_item_comments();
$sf_comments = str_replace('class="oe-comment-delete"', 'class="oe-comment-delete" data-confirm="'
    . osc_esc_html(__('Delete your comment? This cannot be undone.', 'storefront')) . '" data-confirm-title="'
    . osc_esc_html(__('Delete comment', 'storefront')) . '" data-confirm-ok="'
    . osc_esc_html(__('Delete comment', 'storefront')) . '"', (string) ob_get_clean());
?>
<div class="sf-comments sf-detail__section"><?php echo $sf_comments; ?></div>
