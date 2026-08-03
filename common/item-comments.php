<?php
/*
 * Storefront — item comments (list + post form). Partial, included from item.php
 * inside .sf-detail__body. Renders nothing when comments are disabled site-wide.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * CONTRACT (read by core's item controller, action `add_comment`): the field names
 * authorName / authorEmail / title / body, and the hidden action=add_comment +
 * page=item + id inputs. The form is NOT marked `nocsrf`, so core auto-injects the
 * CSRF token it verifies. On a failed post, core repopulates the fields from the
 * session (commentAuthorName / commentAuthorEmail / commentTitle / commentBody) and
 * flashes the reason; only presentation is ours.
 */
if (!osc_comments_enabled()) {
    return;
}

$sf_c_total = (int) osc_count_item_comments();
// When comments require a logged-in user, guests get an invitation to sign in
// instead of a form they cannot submit — the list still shows either way.
$sf_c_gated = osc_reg_user_post_comments() && !osc_is_web_user_logged_in();
$sf_c_form  = static function ($key) {
    return osc_esc_html((string) Session::newInstance()->_getForm($key));
};
?>
<section class="sf-comments sf-detail__section" id="comments" aria-label="<?php echo osc_esc_html(__('Comments', 'storefront')); ?>">
    <h2>
        <?php _e('Comments', 'storefront'); ?>
        <?php if ($sf_c_total > 0) { ?><span class="sf-comments__count"><?php echo $sf_c_total; ?></span><?php } ?>
    </h2>

    <?php if ($sf_c_total > 0) { ?>
    <ol class="sf-comments__list">
        <?php while (osc_has_item_comments()) {
            $sf_c_title = trim((string) osc_comment_title());
            $sf_c_own   = osc_comment_user_id() && osc_comment_user_id() == osc_logged_user_id();
        ?>
        <li class="sf-comment">
            <div class="sf-comment__head">
                <span class="sf-comment__author"><?php echo osc_esc_html(osc_comment_author_name()); ?></span>
                <time class="sf-comment__date" datetime="<?php echo osc_esc_html(osc_comment_pub_date()); ?>"><?php echo osc_esc_html(osc_format_date(osc_comment_pub_date())); ?></time>
            </div>
            <?php if ($sf_c_title !== '') { ?>
            <h3 class="sf-comment__title"><?php echo osc_esc_html($sf_c_title); ?></h3>
            <?php } ?>
            <p class="sf-comment__body"><?php echo nl2br(osc_esc_html(osc_comment_body())); ?></p>
            <?php if ($sf_c_own) { ?>
            <a class="sf-comment__delete" rel="nofollow" href="<?php echo osc_esc_html(osc_delete_comment_url()); ?>"
               data-confirm="<?php echo osc_esc_html(__('Delete your comment? This cannot be undone.', 'storefront')); ?>"
               data-confirm-title="<?php echo osc_esc_html(__('Delete comment', 'storefront')); ?>"
               data-confirm-ok="<?php echo osc_esc_html(__('Delete comment', 'storefront')); ?>">
                <?php echo storefront_icon('trash', 14); ?><span><?php _e('Delete', 'storefront'); ?></span>
            </a>
            <?php } ?>
        </li>
        <?php } ?>
    </ol>
    <?php
    View::newInstance()->_exportVariableToView('sf_pagination_html', osc_comments_pagination());
    osc_current_web_theme_path('common/pagination.php');
    ?>
    <?php } else { ?>
    <p class="sf-comments__empty"><?php _e('No comments yet — ask the seller a question to get the conversation started.', 'storefront'); ?></p>
    <?php } ?>

    <?php if ($sf_c_gated) { ?>
    <div class="sf-comments__gate">
        <h3 class="sf-comments__formhead"><?php _e('Join the conversation', 'storefront'); ?></h3>
        <p class="sf-comments__hint"><?php printf(
            /* translators: %s is a "Sign in" link. */
            __('%s to leave a comment on this listing.', 'storefront'),
            '<a href="' . osc_esc_html(osc_user_login_url()) . '">' . osc_esc_html(__('Sign in', 'storefront')) . '</a>'
        ); ?></p>
    </div>
    <?php } else { ?>
    <form class="sf-comment-form" action="<?php echo osc_base_url(true); ?>" method="post" id="sf-comment-form">
        <h3 class="sf-comments__formhead"><?php _e('Leave a comment', 'storefront'); ?></h3>
        <input type="hidden" name="action" value="add_comment" />
        <input type="hidden" name="page" value="item" />
        <input type="hidden" name="id" value="<?php echo (int) osc_item_id(); ?>" />
        <?php if (osc_is_web_user_logged_in()) { ?>
            <input type="hidden" name="authorName" value="<?php echo osc_esc_html(osc_logged_user_name()); ?>" />
            <input type="hidden" name="authorEmail" value="<?php echo osc_esc_html(osc_logged_user_email()); ?>" />
        <?php } else { ?>
            <div class="sf-field-grid">
                <div class="sf-field">
                    <label for="sf-comment-name"><?php _e('Your name', 'storefront'); ?></label>
                    <input class="sf-input" type="text" name="authorName" id="sf-comment-name" required
                           value="<?php echo $sf_c_form('commentAuthorName'); ?>" />
                </div>
                <div class="sf-field">
                    <label for="sf-comment-email"><?php _e('Your e-mail', 'storefront'); ?> <span class="sf-muted">(<?php _e('not published', 'storefront'); ?>)</span></label>
                    <input class="sf-input" type="email" name="authorEmail" id="sf-comment-email" required
                           value="<?php echo $sf_c_form('commentAuthorEmail'); ?>" />
                </div>
            </div>
        <?php } ?>
        <div class="sf-field">
            <label for="sf-comment-title"><?php _e('Title', 'storefront'); ?> <span class="sf-muted">(<?php _e('optional', 'storefront'); ?>)</span></label>
            <input class="sf-input" type="text" name="title" id="sf-comment-title" value="<?php echo $sf_c_form('commentTitle'); ?>" />
        </div>
        <div class="sf-field">
            <label for="sf-comment-body"><?php _e('Comment', 'storefront'); ?></label>
            <textarea class="sf-input" name="body" id="sf-comment-body" rows="4" required><?php echo $sf_c_form('commentBody'); ?></textarea>
        </div>
        <?php
        // Shown exactly when core will verify it in the add_comment action (the comment
        // captcha toggle AND an active provider). Guarded so older cores without the
        // comment-captcha preference simply never render a challenge.
        if (function_exists('osc_recaptcha_comments_enabled') && osc_recaptcha_comments_enabled()
            && function_exists('osc_captcha_enabled') && osc_captcha_enabled()) { ?>
        <div class="sf-field"><?php osc_show_captcha('comment'); ?></div>
        <?php } ?>
        <div class="sf-form__actions">
            <button type="submit" class="sf-btn sf-btn--primary"><?php echo storefront_icon('message-circle', 16); ?><?php _e('Post comment', 'storefront'); ?></button>
        </div>
    </form>
    <?php } ?>
</section>
