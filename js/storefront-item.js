/*
 * Storefront — item-detail behaviour (loaded only on the listing page).
 * Photo gallery + lightbox, the share dialog, and the lazy map embed.
 * Vanilla JS only. No jQuery.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
(function () {
    'use strict';

    // Gallery: a stage image plus a thumbnail strip. Selecting a thumb swaps the
    // stage; clicking the stage opens the lightbox at the current photo. The photo
    // list is read from an inert JSON island so a title with quotes can't break JS.
    function bindGallery() {
        var gallery = document.querySelector('[data-gallery]');
        if (!gallery) { return; }

        var stage = gallery.querySelector('[data-gallery-stage]');
        var thumbs = gallery.querySelectorAll('[data-gallery-select]');
        var current = 0;

        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                current = parseInt(thumb.getAttribute('data-gallery-select'), 10) || 0;
                if (stage) { stage.src = thumb.getAttribute('data-large'); }
                thumbs.forEach(function (t) { t.setAttribute('aria-pressed', t === thumb ? 'true' : 'false'); });
            });
        });

        var open = gallery.querySelector('[data-gallery-open]');
        var box = document.getElementById('sf-dialog-lightbox');
        if (!open || !box || typeof box.showModal !== 'function') { return; }

        var data = [];
        var island = document.getElementById('sf-gallery-data');
        if (island) { try { data = JSON.parse(island.textContent) || []; } catch (e) { data = []; } }
        if (!data.length) { return; }

        var img = box.querySelector('[data-lightbox-img]');
        var count = box.querySelector('[data-lightbox-count]');
        var dl = box.querySelector('[data-lightbox-dl]');

        function show(i) {
            current = (i + data.length) % data.length;
            var photo = data[current];
            if (img) { img.src = photo.full || photo.large; }
            if (dl) {
                if (photo.dl) { dl.href = photo.dl; dl.hidden = false; }
                else { dl.hidden = true; }
            }
            if (count) { count.textContent = (current + 1) + ' / ' + data.length; }
        }

        open.addEventListener('click', function () { show(current); box.showModal(); });
        box.querySelectorAll('[data-lightbox-step]').forEach(function (nav) {
            nav.addEventListener('click', function () {
                show(current + (parseInt(nav.getAttribute('data-lightbox-step'), 10) || 1));
            });
        });
        box.addEventListener('keydown', function (e) {
            if (data.length < 2) { return; }
            if (e.key === 'ArrowLeft') { show(current - 1); }
            else if (e.key === 'ArrowRight') { show(current + 1); }
        });
    }

    // Share dialog: reveal the OS share sheet where navigator.share exists, and
    // wire the copy-link button. Everything degrades to the plain links + input.
    function bindShare() {
        var native = document.querySelector('[data-share-native]');
        if (native && navigator.share) {
            native.hidden = false;
            native.addEventListener('click', function () {
                navigator.share({
                    title: native.getAttribute('data-share-title') || document.title,
                    url: native.getAttribute('data-share-url') || location.href
                }).catch(function () {});
            });
        }
        var copy = document.querySelector('[data-share-copy]');
        var input = document.querySelector('[data-share-url]');
        var status = document.querySelector('[data-share-status]');
        if (copy && input) {
            copy.addEventListener('click', function () {
                var done = function (msg) { if (status) { status.textContent = msg; } };
                var ok = copy.getAttribute('data-copied') || 'Link copied.';
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(input.value).then(function () { done(ok); },
                        function () { input.select(); done(copy.getAttribute('data-select') || ''); });
                } else {
                    input.select(); try { document.execCommand('copy'); done(ok); } catch (e) {}
                }
            });
        }
    }

    // Location map. Interactive, and lazy on purpose — the map tiles/API are billed
    // or rate-limited, so nothing external is requested until "Show map" is pressed.
    // Honours the admin's provider: the Google Maps JS API when a key is set, else
    // Leaflet + OpenStreetMap (keyless). When the listing has no geocoded coordinates
    // (the common case) the address is geocoded client-side so a map still appears.
    // Each API loads once per page, behind a single shared promise.
    var googleApi = null;
    var leafletApi = null;

    function loadGoogle(key) {
        if (googleApi) { return googleApi; }
        googleApi = new Promise(function (resolve, reject) {
            var cb = '__sfMapsReady';
            window[cb] = function () { delete window[cb]; resolve(window.google); };
            var s = document.createElement('script');
            s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(key) + '&callback=' + cb;
            s.async = true;
            s.onerror = function () { reject(new Error('maps')); };
            document.head.appendChild(s);
        });
        return googleApi;
    }

    function loadLeaflet() {
        if (leafletApi) { return leafletApi; }
        leafletApi = new Promise(function (resolve, reject) {
            var css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            css.crossOrigin = '';
            document.head.appendChild(css);
            var s = document.createElement('script');
            s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            s.crossOrigin = '';
            s.onload = function () { resolve(window.L); };
            s.onerror = function () { reject(new Error('leaflet')); };
            document.head.appendChild(s);
        });
        return leafletApi;
    }

    function bindMap() {
        var box = document.querySelector('[data-map]');
        if (!box) { return; }
        var button = box.querySelector('[data-map-load]');
        var canvas = box.querySelector('[data-map-canvas]');
        if (!button || !canvas) { return; }

        var d = box.dataset;
        var lat = parseFloat(d.mapLat), lng = parseFloat(d.mapLng);
        var hasCoords = isFinite(lat) && isFinite(lng) && (lat !== 0 || lng !== 0);
        var address = d.mapAddress || '';
        var useGoogle = d.mapProvider === 'google' && !!d.mapKey;

        // A dead map must not leave a dead button — replace the whole box with the
        // translated failure message (never a hardcoded English string here).
        function fail() {
            var p = document.createElement('p');
            p.className = 'sf-map__error';
            p.setAttribute('role', 'status');
            p.textContent = d.mapError || '';
            box.replaceChildren(p);
        }

        function renderGoogle(google) {
            var map = new google.maps.Map(canvas, {
                zoom: 15, center: { lat: 0, lng: 0 },
                mapTypeControl: false, streetViewControl: false, fullscreenControl: false
            });
            var place = function (center) { map.setCenter(center); new google.maps.Marker({ map: map, position: center }); };
            if (hasCoords) { place({ lat: lat, lng: lng }); return; }
            if (!address) { fail(); return; }
            new google.maps.Geocoder().geocode({ address: address }, function (res, status) {
                if (status !== 'OK' || !res || !res.length) { fail(); return; }
                place(res[0].geometry.location);
            });
        }

        function renderOsm(L) {
            var teal = getComputedStyle(document.documentElement).getPropertyValue('--color-accent').trim() || '#12a6a0';
            var map = L.map(canvas, { scrollWheelZoom: false });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);
            var place = function (la, lo) {
                map.setView([la, lo], 15);
                L.circleMarker([la, lo], { radius: 8, weight: 2, color: teal, fillColor: teal, fillOpacity: 0.9 }).addTo(map);
            };
            if (hasCoords) { place(lat, lng); return; }
            if (!address) { fail(); return; }
            fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(address), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (list) {
                    if (!list || !list.length) { fail(); return; }
                    place(parseFloat(list[0].lat), parseFloat(list[0].lon));
                }).catch(fail);
        }

        button.addEventListener('click', function () {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            (useGoogle ? loadGoogle(d.mapKey) : loadLeaflet()).then(function (lib) {
                if (!lib) { fail(); return; }
                canvas.hidden = false;
                button.remove();
                if (useGoogle) { renderGoogle(lib); } else { renderOsm(lib); }
            }).catch(fail);
        });
    }

    // Sticky mobile contact bar. On mobile the buy-box (price + Message seller) isn't
    // sticky, so the primary action scrolls away just as interest peaks. Reveal a fixed
    // bottom bar once the in-rail seller card has scrolled above the viewport, and hide
    // it again near the footer so it never covers it. CSS keeps the bar mobile-only, so
    // this stays a no-op cost on desktop. The bar's button reuses the contact dialog
    // (wired by bindDialogs in storefront-core.js).
    function bindBuyBar() {
        var bar = document.querySelector('[data-buybar]');
        var anchor = document.querySelector('.sf-seller');
        if (!bar || !anchor) { return; }
        var footer = document.querySelector('.sf-footer');
        var ticking = false;
        function check() {
            ticking = false;
            // Show once the in-rail seller card has fully scrolled above the fold, but
            // not when the footer is within a bar's height of the viewport (so it never
            // covers the footer on short, related-less pages).
            var pastSeller = anchor.getBoundingClientRect().bottom < 0;
            var footerNear = footer ? footer.getBoundingClientRect().top < window.innerHeight + 96 : false;
            bar.hidden = !(pastSeller && !footerNear);
        }
        function onScroll() { if (!ticking) { ticking = true; requestAnimationFrame(check); } }
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        check();
    }

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {
        bindGallery();
        bindShare();
        bindMap();
        bindBuyBar();
    });
})();
