<?php
/*
 * Storefront — a Shopclass public theme.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
osc_current_web_theme_path('common/header.php');
?>
<div class="sf-account">
    <?php osc_current_web_theme_path('common/account-header.php'); ?>
    <div class="sf-account__main">
        <section class="sf-section"><?php osc_render_file(); ?></section>
    </div>
</div>
<?php osc_current_web_theme_path('common/footer.php'); ?>
