/*
 * Storefront — core behaviour (loaded on every page).
 * Site chrome + generic DOM utilities: header nav drawer, account menus, toggles,
 * URL-select navigation, native <dialog> open/close, confirm guards, flash dismiss.
 * Vanilla JS only. No jQuery.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
(function () {
    'use strict';

    // Generic toggle: a control with [data-sf-toggle="CLASS"] toggles CLASS on the
    // element named by its [aria-controls], and flips its own aria-expanded.
    function bindToggles() {
        document.querySelectorAll('[data-sf-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var cls = btn.getAttribute('data-sf-toggle');
                var targetId = btn.getAttribute('aria-controls');
                var target = targetId ? document.getElementById(targetId) : null;
                if (!target) { return; }
                var open = target.classList.toggle(cls === 'sf-nav-open' ? 'is-open' : cls);
                btn.setAttribute('aria-expanded', String(open));
            });
        });
    }

    // Mobile nav drawer as a modal: opening locks body scroll, shows a scrim, moves
    // focus into the drawer and traps Tab; the scrim, Escape, the toggle, or following
    // a link dismisses it and restores focus to the toggle. Above the breakpoint the
    // toggle is hidden, so open() is never reached there.
    function bindNav() {
        var nav = document.getElementById('sf-nav');
        var toggle = document.querySelector('[data-sf-nav-toggle]');
        var backdrop = document.querySelector('[data-sf-nav-close]');
        if (!nav || !toggle) { return; }
        var isOpen = function () { return nav.classList.contains('is-open'); };
        function focusables() {
            return Array.prototype.filter.call(
                nav.querySelectorAll('a[href], button:not([disabled])'),
                function (el) { return el.offsetParent !== null; }
            );
        }
        function open() {
            nav.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            if (backdrop) { backdrop.hidden = false; }
            document.body.classList.add('sf-noscroll');
            var f = focusables();
            if (f.length) { f[0].focus(); }
        }
        function close(returnFocus) {
            if (!isOpen()) { return; }
            nav.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            if (backdrop) { backdrop.hidden = true; }
            document.body.classList.remove('sf-noscroll');
            if (returnFocus !== false) { toggle.focus(); }
        }
        toggle.addEventListener('click', function () { isOpen() ? close() : open(); });
        if (backdrop) { backdrop.addEventListener('click', function () { close(); }); }
        // Following a link navigates away; drop the lock first so a same-page anchor
        // never leaves the body scroll-locked.
        nav.addEventListener('click', function (e) {
            if (e.target.closest('a[href]')) { close(false); }
        });
        document.addEventListener('keydown', function (e) {
            if (!isOpen()) { return; }
            if (e.key === 'Escape') { close(); return; }
            if (e.key === 'Tab') {
                var f = focusables();
                if (!f.length) { return; }
                var first = f[0], last = f[f.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        });
        // Resized back to desktop while open: reset so the inline nav isn't stuck locked.
        window.addEventListener('resize', function () {
            if (isOpen() && window.innerWidth > 860) { close(false); }
        });
    }

    // Dropdown menus (the account cluster). Click toggles the panel; outside-click and
    // Escape close it, Escape returning focus to the trigger. Inside the mobile drawer
    // the panel is shown inline by CSS, so these handlers are a desktop concern and stay
    // harmless there.
    function bindMenus() {
        document.querySelectorAll('[data-sf-menu]').forEach(function (menu) {
            var toggle = menu.querySelector('.sf-account__toggle');
            var panel = menu.querySelector('.sf-account__menu');
            if (!toggle || !panel) { return; }
            function openM() { panel.hidden = false; toggle.setAttribute('aria-expanded', 'true'); }
            function closeM(returnFocus) {
                panel.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
                if (returnFocus) { toggle.focus(); }
            }
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                panel.hidden ? openM() : closeM(false);
            });
            document.addEventListener('click', function (e) {
                if (!menu.contains(e.target)) { closeM(false); }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !panel.hidden) { closeM(true); }
            });
            panel.addEventListener('click', function (e) {
                if (e.target.closest('a[href]')) { closeM(false); }
            });
        });
    }

    // A <select data-sf-nav> whose option values are URLs navigates on change —
    // used by the search results "Sort" control.
    function bindNavSelects() {
        document.querySelectorAll('select[data-sf-nav]').forEach(function (sel) {
            sel.addEventListener('change', function () {
                if (sel.value) { window.location.href = sel.value; }
            });
        });
    }

    // Native <dialog>: [data-dialog-open="id"] opens, [data-dialog-close] closes
    // the nearest dialog, and a click on the backdrop (the dialog element itself)
    // closes it. Escape is handled natively by <dialog>.
    function bindDialogs() {
        document.querySelectorAll('[data-dialog-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var dlg = document.getElementById(btn.getAttribute('data-dialog-open'));
                if (dlg && typeof dlg.showModal === 'function') { dlg.showModal(); }
            });
        });
        document.querySelectorAll('dialog').forEach(function (dlg) {
            dlg.addEventListener('click', function (e) {
                if (e.target === dlg) { dlg.close(); }
            });
            dlg.querySelectorAll('[data-dialog-close]').forEach(function (btn) {
                btn.addEventListener('click', function () { dlg.close(); });
            });
        });
    }

    // Confirm guard for destructive links (e.g. owner delete). A real CSP blocks
    // inline onclick, so the confirmation lives here, not in the markup. When the
    // shared branded dialog (#sf-confirm, in the footer) is present it carries the
    // message — and, via data-confirm-title/-ok, names the exact item being removed
    // — so the trust-critical moment is styled and specific, not a bare browser
    // prompt. Falls back to window.confirm when the dialog isn't on the page.
    function bindConfirms() {
        var links = document.querySelectorAll('[data-confirm]');
        if (!links.length) { return; }
        var dlg = document.getElementById('sf-confirm');
        var canDialog = dlg && typeof dlg.showModal === 'function';
        var titleEl, msgEl, goEl, defTitle, defOk;
        if (canDialog) {
            titleEl = dlg.querySelector('.sf-confirm__title');
            msgEl = dlg.querySelector('[data-confirm-msg]');
            goEl = dlg.querySelector('[data-confirm-go]');
            defTitle = titleEl ? titleEl.textContent : '';
            defOk = goEl ? goEl.textContent : '';
        }
        links.forEach(function (el) {
            el.addEventListener('click', function (e) {
                var msg = el.getAttribute('data-confirm');
                if (!canDialog) {
                    if (!window.confirm(msg)) { e.preventDefault(); }
                    return;
                }
                e.preventDefault();
                if (titleEl) { titleEl.textContent = el.getAttribute('data-confirm-title') || defTitle; }
                if (msgEl) { msgEl.textContent = msg; }
                if (goEl) {
                    goEl.textContent = el.getAttribute('data-confirm-ok') || defOk;
                    goEl.setAttribute('href', el.getAttribute('href'));
                }
                dlg.showModal();
            });
        });
    }

    // Flash messages: core prints a `.ico-close` dismiss anchor with no behaviour of
    // its own and no href (so not focusable) — wire it up and make it keyboard-operable.
    function bindFlashDismiss() {
        document.querySelectorAll('.flashmessage a.ico-close').forEach(function (btn) {
            btn.setAttribute('role', 'button');
            btn.setAttribute('tabindex', '0');
            var label = btn.getAttribute('data-oc-close-label');
            if (label) { btn.setAttribute('aria-label', label); }
            function dismiss() {
                var box = btn.closest('.flashmessage');
                if (box && box.parentNode) { box.parentNode.removeChild(box); }
            }
            btn.addEventListener('click', function (e) { e.preventDefault(); dismiss(); });
            btn.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); dismiss(); }
            });
        });
    }

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {
        bindToggles();
        bindNav();
        bindMenus();
        bindNavSelects();
        bindDialogs();
        bindConfirms();
        bindFlashDismiss();
    });
})();
