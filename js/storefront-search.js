/*
 * Storefront — search-surface & location behaviour.
 * Loaded on the search results page, the home hero, and the item post/edit form —
 * the off-canvas filter drawer, location autocomplete, and the search-alert subscribe.
 * Vanilla JS only. No jQuery.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
(function () {
    'use strict';

    // Off-canvas filter drawer (search, mobile). The rail is one DOM node: sticky
    // column on desktop, slide-in drawer below the breakpoint. A [data-offcanvas-open]
    // button reveals it; the close button and the backdrop (both [data-offcanvas-close])
    // and Escape dismiss it. aria-expanded and body scroll-lock stay in sync.
    function bindOffcanvas() {
        var opener = document.querySelector('[data-offcanvas-open]');
        var panelId = opener && opener.getAttribute('data-offcanvas-open');
        var panel = panelId ? document.getElementById(panelId) : null;
        if (!opener || !panel) { return; }
        var backdrop = document.querySelector('.sf-filters__backdrop');

        function open() {
            panel.classList.add('is-open');
            opener.setAttribute('aria-expanded', 'true');
            if (backdrop) { backdrop.hidden = false; }
            document.body.classList.add('sf-noscroll');
            var first = panel.querySelector('[data-offcanvas-close]');
            if (first) { first.focus(); }
        }
        function close() {
            if (!panel.classList.contains('is-open')) { return; }
            panel.classList.remove('is-open');
            opener.setAttribute('aria-expanded', 'false');
            if (backdrop) { backdrop.hidden = true; }
            document.body.classList.remove('sf-noscroll');
            opener.focus();
        }
        opener.addEventListener('click', open);
        document.querySelectorAll('[data-offcanvas-close]').forEach(function (el) {
            el.addEventListener('click', close);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { close(); }
        });
    }

    // Location autocomplete. The combobox itself is core's shared oscAutocomplete
    // (oc-includes/assets/osclass/ui-common.js — the same engine the custom-field
    // filters use), so the theme ships no combobox of its own; it only supplies the
    // location-specific behaviour core's data-attribute auto-wiring doesn't cover:
    // country/region scoping, the resolved-id hidden target, and clearing dependent
    // fields. Fields opt in with data-ac (search rail, hero, item-post). functions.php
    // makes this bundle depend on 'osc-ui-common', so the engine is present and parsed
    // first on these pages; this still guards, degrading to a plain field if it isn't.
    function bindLocationAutocomplete() {
        if (typeof oscAutocomplete !== 'function') { return; }
        // Item-post location fields are core-rendered plain inputs we can't annotate
        // inline; copy any wrapper [data-ac-field] config onto the inner input first.
        document.querySelectorAll('[data-ac-field]').forEach(function (wrap) {
            var input = wrap.querySelector('input[type="text"]');
            if (!input || input.hasAttribute('data-ac')) { return; }
            input.setAttribute('data-ac', wrap.getAttribute('data-ac-field'));
            ['url', 'target', 'scope', 'scope-param', 'clears'].forEach(function (k) {
                var v = wrap.getAttribute('data-ac-' + k);
                if (v !== null) { input.setAttribute('data-ac-' + k, v); }
            });
        });
        document.querySelectorAll('input[data-ac]').forEach(function (input) {
            var action = input.getAttribute('data-ac');
            var base = input.getAttribute('data-ac-url');
            if (!base) { return; }
            var hidden = input.getAttribute('data-ac-target') ? document.querySelector(input.getAttribute('data-ac-target')) : null;
            var scopeSel = input.getAttribute('data-ac-scope');
            var scopeParam = input.getAttribute('data-ac-scope-param');
            var clears = (input.getAttribute('data-ac-clears') || '').split(',').filter(Boolean);
            function clearDependents() {
                if (hidden) { hidden.value = ''; }
                clears.forEach(function (sel) { var el = document.querySelector(sel); if (el) { el.value = ''; } });
            }
            oscAutocomplete(input, {
                minLength: 2,
                // Resolved per fetch (core appends &term=) so a dependent scope — city
                // needs the current region, region the current country — is up to date.
                source: function () {
                    var u = base + (base.indexOf('?') > -1 ? '&' : '?') + 'page=ajax&action=' + encodeURIComponent(action);
                    if (scopeSel && scopeParam) {
                        var s = document.querySelector(scopeSel);
                        if (s && s.value) { u += '&' + scopeParam + '=' + encodeURIComponent(s.value); }
                    }
                    return u;
                },
                onSearch: clearDependents,               // editing invalidates the resolved id
                onSelect: function (item) {
                    if (hidden) { hidden.value = item.id != null ? item.id : ''; }
                    clears.forEach(function (sel) { var el = document.querySelector(sel); if (el) { el.value = ''; } });
                    // returning undefined lets core write item.value into the input
                }
            });
        });
    }

    // Search-alert subscribe: posts to core's ajax `alerts` action (no jQuery) and
    // announces the outcome. Core reads `alert` (the encrypted search) and `email`;
    // it returns "1" on success, "-4" when login is required, "-1" for a bad email.
    function bindSearchAlert() {
        var form = document.querySelector('[data-search-alert]');
        if (!form) { return; }
        var msg = form.querySelector('[data-alert-msg]');
        function say(text, state) {
            if (!msg) { return; }
            msg.textContent = text || '';
            if (state) { msg.setAttribute('data-state', state); } else { msg.removeAttribute('data-state'); }
        }
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var tok = form.querySelector('[name="alert"]');
            var email = form.querySelector('[name="alert_email"]');
            var body = new URLSearchParams();
            body.set('page', 'ajax'); body.set('action', 'alerts');
            if (tok) { body.set('alert', tok.value); }
            if (email) { body.set('email', email.value); }
            say(form.getAttribute('data-wait'));
            fetch(form.action, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: body.toString()
            }).then(function (r) { return r.text(); }).then(function (t) {
                t = (t || '').trim();
                if (t === '1') {
                    say(form.getAttribute('data-ok'), 'ok');
                    form.querySelectorAll('input, button').forEach(function (el) { el.disabled = true; });
                } else if (t === '-4') { say(form.getAttribute('data-login'), 'err'); }
                else if (t === '-1') { say(form.getAttribute('data-bademail'), 'err'); }
                else { say(form.getAttribute('data-fail'), 'err'); }
            }).catch(function () { say(form.getAttribute('data-fail'), 'err'); });
        });
    }

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {
        bindOffcanvas();
        bindLocationAutocomplete();
        bindSearchAlert();
    });
})();
