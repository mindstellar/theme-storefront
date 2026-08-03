<?php
/*
 * Storefront — pagination wrapper (shared chrome around core's Pagination output).
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Core's Pagination::doPagination() emits the accessible list itself: a
 * <ul role="navigation" aria-label> with aria-current on the current page and an
 * aria-label on every control. So this wrapper is presentation only (the top rule
 * + spacing); it must NOT add a second navigation landmark of its own.
 *
 * Core still renders a lone current-page <span> (no links) when the results fit a
 * single page. We suppress the whole block then — no dead one-page pager, empty
 * landmark or stray divider rule — by rendering only when there is an <a> to
 * navigate to.
 *
 * Callers export the pre-rendered string and include this partial, e.g.:
 *   View::newInstance()->_exportVariableToView('sf_pagination_html', osc_search_pagination());
 *   osc_current_web_theme_path('common/pagination.php');
 */
$sf_pagination_html = (string) View::newInstance()->_get('sf_pagination_html');
if (strpos($sf_pagination_html, '<a ') !== false) { ?>
    <div class="sf-pagination"><?php echo $sf_pagination_html; ?></div>
<?php } ?>
