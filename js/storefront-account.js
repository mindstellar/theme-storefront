/*
 * Storefront — account behaviour: the profile photo preview. Vanilla JS only. No jQuery.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
(function () {
    'use strict';

    // Avatar editor: live-preview a chosen file before upload, and let the "remove"
    // checkbox swap the preview back to the monogram. Progressive enhancement — the
    // <label for> file picker and the checkbox both work with JS off; this only adds
    // the instant preview so the user sees the crop before they save.
    function bindAvatarEditor() {
        // Core's profile form: an .oe-avatar picture beside the #oe-avatar file input.
        var field = document.querySelector('.oe-avatar-field');
        if (!field) { return; }
        var input = field.querySelector('#oe-avatar');
        var remove = field.querySelector('[name="remove_avatar"]');
        var badge = document.querySelector('[data-sf-monogram]');
        var current = field.querySelector('.oe-avatar');
        if (!input || !current) { return; }
        // No picture yet: the theme's letter badge instead of core's placeholder.
        if (badge && current.classList.contains('oe-avatar-empty')) {
            badge.hidden = false;
            current.replaceWith(badge);
            current = badge;
        }
        var original = current;
        var objUrl = null;
        function show(el) { var now = field.firstElementChild; if (now !== el) { now.replaceWith(el); } }
        function clearUrl() { if (objUrl) { URL.revokeObjectURL(objUrl); objUrl = null; } }
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) { return; }
            if (remove) { remove.checked = false; }
            clearUrl();
            objUrl = URL.createObjectURL(file);
            var img = document.createElement('img');
            img.className = 'oe-avatar';
            img.alt = '';
            img.src = objUrl;
            show(img);
        });
        if (remove && badge) {
            remove.addEventListener('change', function () {
                clearUrl();
                if (remove.checked) { input.value = ''; badge.hidden = false; show(badge); } else { show(original); }
            });
        }
    }

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(bindAvatarEditor);
})();
