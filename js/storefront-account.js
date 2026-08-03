/*
 * Storefront — account behaviour (profile page: avatar editor; manage page: the
 * listings status filter). Vanilla JS only. No jQuery.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
(function () {
    'use strict';

    // Avatar editor: live-preview a chosen file before upload, and let the "remove"
    // checkbox swap the preview back to the monogram. Progressive enhancement — the
    // <label for> file picker and the checkbox both work with JS off; this only adds
    // the instant preview so the user sees the crop before they save.
    function bindAvatarEditor() {
        var root = document.querySelector('[data-avatar]');
        if (!root) { return; }
        var input = root.querySelector('[data-avatar-input]');
        var preview = root.querySelector('[data-avatar-preview]');
        var remove = root.querySelector('[data-avatar-remove]');
        if (!input || !preview) { return; }
        var original = preview.innerHTML;
        var mono = preview.getAttribute('data-monogram') || '?';
        var objUrl = null;
        function clearUrl() { if (objUrl) { URL.revokeObjectURL(objUrl); objUrl = null; } }
        function showImg(src) {
            preview.textContent = '';
            var img = document.createElement('img');
            img.alt = '';
            img.src = src;
            preview.appendChild(img);
        }
        function showMono() {
            clearUrl();
            preview.textContent = '';
            var span = document.createElement('span');
            span.className = 'sf-avatar__monogram';
            span.textContent = mono;
            preview.appendChild(span);
        }
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) { return; }
            if (remove) { remove.checked = false; }
            clearUrl();
            objUrl = URL.createObjectURL(file);
            showImg(objUrl);
        });
        if (remove) {
            remove.addEventListener('change', function () {
                if (remove.checked) { input.value = ''; showMono(); }
                else { clearUrl(); preview.innerHTML = original; }
            });
        }
    }

    // Manage-listings status filter: a toolbar of chips (All / Active / Pending / …)
    // built from the statuses actually present, filtering the loaded rows in place.
    // Progressive enhancement — with JS off the full list still renders. It filters
    // the rows on this page, so the server pager is hidden while a status filter is
    // active (it would page the unfiltered set); "All" restores it.
    function bindManageFilter() {
        var list = document.querySelector('.sf-manage');
        if (!list) { return; }
        var rows = Array.prototype.slice.call(list.querySelectorAll('.sf-manage__row'));
        if (rows.length < 2) { return; }

        var order = ['active', 'pending', 'expired', 'flagged', 'blocked'];
        var seen = {};
        rows.forEach(function (r) {
            var s = r.getAttribute('data-status');
            if (!s) { return; }
            if (!seen[s]) { var chip = r.querySelector('.sf-state'); seen[s] = { n: 0, label: chip ? chip.textContent.trim() : s }; }
            seen[s].n++;
        });
        var present = order.filter(function (s) { return seen[s]; });
        if (present.length < 2) { return; }  // one status only — a filter adds nothing

        var allLabel = list.getAttribute('data-filter-all') || 'All';
        var pager = document.querySelector('.sf-pagination');

        var bar = document.createElement('div');
        bar.className = 'sf-manage-filter';
        bar.setAttribute('role', 'group');
        var barLabel = list.getAttribute('data-filter-label');
        if (barLabel) { bar.setAttribute('aria-label', barLabel); }

        function makeChip(filter, label, n, active) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'sf-filter-chip' + (active ? ' is-active' : '');
            b.setAttribute('data-filter', filter);
            b.setAttribute('aria-pressed', active ? 'true' : 'false');
            b.appendChild(document.createTextNode(label + ' '));
            var count = document.createElement('span');
            count.className = 'sf-filter-chip__n';
            count.textContent = n;
            b.appendChild(count);
            return b;
        }

        bar.appendChild(makeChip('all', allLabel, rows.length, true));
        present.forEach(function (s) { bar.appendChild(makeChip(s, seen[s].label, seen[s].n, false)); });
        list.parentNode.insertBefore(bar, list);

        bar.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('.sf-filter-chip') : null;
            if (!btn) { return; }
            var f = btn.getAttribute('data-filter');
            Array.prototype.forEach.call(bar.querySelectorAll('.sf-filter-chip'), function (c) {
                var on = c === btn;
                c.classList.toggle('is-active', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            rows.forEach(function (r) {
                r.classList.toggle('is-hidden', f !== 'all' && r.getAttribute('data-status') !== f);
            });
            if (pager) { pager.hidden = (f !== 'all'); }
        });
    }

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(bindAvatarEditor);
    ready(bindManageFilter);
})();
