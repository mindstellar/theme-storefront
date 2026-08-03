<?php
/*
 * Storefront — page-builder canvas. Core renders this for a static page whose
 * template is the block builder (core.page_builder): the page's content is widget
 * blocks stored at its builder location, not in s_text. Core require()s this file
 * (in the page controller scope) when the active theme ships it, so the theme owns
 * the page's chrome instead of core's bare fallback. Mirrors page.php's shell.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
osc_current_web_theme_path('common/header.php');
?>
<article class="sf-section sf-page sf-page--builder">
    <h1 class="sf-section__title"><?php echo osc_esc_html(osc_static_page_title()); ?></h1>
    <div class="sf-page__body sf-page__blocks"><?php osc_show_page_widgets(); ?></div>
</article>
<?php osc_current_web_theme_path('common/footer.php'); ?>
