# Storefront

The default public theme for [Shopclass](https://github.com/mindstellar/shopclass) — a modern,
responsive, **vanilla-JS** (no jQuery) classifieds front end.

## Design

- Brand palette (Deep Navy / Teal / Slate / Off-White / Coral), light **and** dark mode via
  `prefers-color-scheme`.
- Card-grid listings, sticky search, accessible mobile nav, logical CSS properties for RTL.
- Consumes only the public Shopclass theme API (`osc_*` helpers and hooks) — no reach into core
  internals.

## Install

Shopclass 6.0.0+ bundles Storefront in the release zip and activates it on a fresh install. To
install manually, drop this folder into `oc-content/themes/storefront/` and activate it under
**Settings → Appearance**.

## Local development

Clone next to your Shopclass checkout and symlink it in:

```bash
git clone git@github.com:mindstellar/theme-storefront.git
ln -s "$(pwd)/theme-storefront" /path/to/shopclass/oc-content/themes/storefront
```

`oc-content/themes/*` is gitignored in the core repo, so the symlink won't show up there.

## Assets & build

Source `css/*.css` and `js/*.js` are committed and readable; the theme runs straight from them in
local dev — **no build step is required to develop**. For production, a minify pass emits
`*.min.css` / `*.min.js` siblings that `functions.php` loads automatically when present (falling back
to source otherwise):

```bash
npm install     # once — installs esbuild (the only dev dependency)
npm run build   # writes css/storefront.min.css and js/storefront-*.min.js
```

Generated `*.min.*` files and `node_modules/` are gitignored; the release workflow builds them fresh.

**JS is split per surface and loaded only where it is used** — `storefront-core` (site chrome, every
page), `storefront-item` (listing gallery/lightbox/map), `storefront-search` (filter drawer,
location autocomplete, search-alert — search page, home hero, post/edit form), and
`storefront-account` (avatar editor). Each view enqueues its own bundle; all scripts are deferred.

## Releases

Pushing a `vX.Y.Z` tag runs `.github/workflows/release.yml`, which builds the minified assets,
packages the theme as `storefront_X.Y.Z.zip` (source + `*.min.*`, without the build tooling), and
publishes a GitHub release. Shopclass's `.build.sh` fetches the **latest** release asset at build
time and bundles it into the core zip.

## Showcases Shopclass 6.0.0

Storefront is meant to double as a reference for what the platform can do:

- **Featured (premium) listings** on the home page via `osc_get_premiums()`.
- **Related listings** on the item page (same category, live only) — see `storefront_related_items()`.
- **Custom fields** rendered on the item page through the item-meta API (`osc_has_item_meta()`).
- **Friendly-named resource downloads** (`osc_resource_download_url()`) in the gallery.
- **Cache-safe view counts** — the theme is stateless and never counts a view at render time, so
  core's JS beacon does the counting and full-page caching keeps working.
- **Widget zones** (`header`, `footer`, `item_sidebar`) for drop-in plugin components.
- **Vanilla JS only**, light + dark mode, RTL via logical properties.

## Plugin hooks

Storefront fires the hook names existing Osclass plugins already target, plus a few of its own so
plugins can inject content into the new sections:

| Hook | Where |
|---|---|
| `header`, `footer` | `<head>` end / before `</body>` (asset + ad injection) |
| `before-main`, `after-main` | Around the main content region |
| `search_ads_listing_top` | Top of home and search results |
| `search_ads_listing_medium` | Interspersed every 6 search results |
| `home_before_featured`, `home_after_featured` | Around the home featured row |
| `home_before_listings`, `home_after_listings` | Around the home latest-listings row |
| `item_meta`, `item_detail` | Item page body |
| `item_before_related`, `item_after_related` | Around the item related-listings row |
| `item_sidebar` | Item page aside (alongside the `item_sidebar` widget zone) |
| `item_contact_form` | Inside the seller-contact form |

## License

GPL-3.0-or-later. © Mindstellar Community.
