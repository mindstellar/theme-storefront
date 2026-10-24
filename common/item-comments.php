<?php
/*
 * Storefront: item comments. Core prints the block (list, rating stars, form) and
 * css/storefront.css styles its .oe-* classes. Nothing prints when comments are off.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
if (!osc_comments_enabled()) {
    return;
}
?>
<div class="sf-comments sf-detail__section"><?php osc_show_item_comments(); ?></div>
