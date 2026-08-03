/*
 * Storefront theme build: minify the source CSS/JS into `.min.*` siblings.
 * Run `npm install && npm run build`. functions.php loads the `.min` file when
 * present (releases) and falls back to the readable source in local dev.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
import { build } from 'esbuild';

// One entry per page bundle (see functions.php for which pages load which).
const JS = ['storefront-core', 'storefront-item', 'storefront-search', 'storefront-account'];

// Minify only — no `target`, so modern CSS the theme depends on at runtime
// (color-mix(), custom properties, logical properties) is preserved verbatim and
// the remote Google Fonts @import is left external (bundle:false).
const common = { minify: true, bundle: false, legalComments: 'none', charset: 'utf8' };

await Promise.all(
    JS.map((name) => build({ ...common, entryPoints: [`js/${name}.js`], outfile: `js/${name}.min.js` }))
);

await build({ ...common, entryPoints: ['css/storefront.css'], outfile: 'css/storefront.min.css' });

console.log('Built ' + JS.map((n) => 'js/' + n + '.min.js').join(', ') + ', css/storefront.min.css');
