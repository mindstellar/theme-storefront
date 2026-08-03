<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
osc_current_web_theme_path('common/header.php');
?>
<article class="sf-section sf-page">
    <h1 class="sf-section__title"><?php echo osc_esc_html(osc_static_page_title()); ?></h1>
    <div class="sf-page__body"><?php echo osc_static_page_text(); ?></div>
</article>
<?php osc_current_web_theme_path('common/footer.php'); ?>
