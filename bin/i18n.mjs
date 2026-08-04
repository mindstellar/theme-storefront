/*
 * Storefront i18n tooling — pure Node (no system gettext), matching the esbuild-only
 * build. Two modes:
 *
 *   node bin/i18n.mjs extract   Scan the theme's PHP for translatable strings and
 *                               (re)write languages/storefront.pot plus the default
 *                               languages/en_US/theme.po. Run this after adding or
 *                               changing UI strings, then commit the .pot / .po.
 *
 *   node bin/i18n.mjs compile   Compile every languages/<locale>/theme.po to a
 *                               theme.mo sibling. Part of `npm run build`; the .mo is
 *                               what core actually loads (into the 'storefront' domain,
 *                               matching the theme folder name), so it ships in releases.
 *
 * Strings are the first argument of __()/_e() and the first two of _n() (singular,
 * plural), domain 'storefront'. The default en_US catalogue is the identity map
 * (msgstr = msgid) — real English is the source — so it doubles as the base other
 * locales copy and translate.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
import fs from 'node:fs';
import path from 'node:path';
import url from 'node:url';
import gettextParser from 'gettext-parser';

const ROOT = path.resolve(path.dirname(url.fileURLToPath(import.meta.url)), '..');
const LANG_DIR = path.join(ROOT, 'languages');
const POT = path.join(LANG_DIR, 'storefront.pot');
const SCAN_DIRS = ['', 'common', 'admin'];
const PLURAL_FORMS = 'nplurals=2; plural=(n != 1);';
const SILENCE = '<?php // Silence.';

// The catalogue version tracks the theme version — read from index.php's theme
// header (the canonical source), so Project-Id-Version always matches the release.
function themeVersion() {
    const header = fs.readFileSync(path.join(ROOT, 'index.php'), 'utf8');
    const m = header.match(/^\s*Version:\s*(.+?)\s*$/m);
    return m ? m[1] : '0.0.0';
}
const PROJECT_ID = 'Storefront ' + themeVersion();

// Unescape a PHP quoted-string literal body for the given quote character.
function phpUnescape(quote, raw) {
    if (quote === "'") {
        return raw.replace(/\\(['\\])/g, '$1');
    }
    const map = { '"': '"', '\\': '\\', '$': '$', n: '\n', r: '\r', t: '\t', v: '\v', f: '\f', e: '\x1b' };
    return raw.replace(/\\([\\"$nrtvfe])/g, (m, c) => (c in map ? map[c] : m));
}

function phpSourceFiles() {
    const files = [];
    for (const dir of SCAN_DIRS) {
        const abs = path.join(ROOT, dir);
        if (!fs.existsSync(abs)) continue;
        for (const name of fs.readdirSync(abs)) {
            if (name.endsWith('.php')) files.push(path.join(dir, name));
        }
    }
    return files.sort();
}

const lineAt = (text, index) => text.slice(0, index).split('\n').length;

// Extract strings → Map keyed by msgid ('\u0000' separates singular\0plural).
function extract() {
    // A quoted string: opening quote (captured), body of escapes-or-non-quote, closing quote.
    const q = (s = 2) => `(['"])((?:\\\\.|(?!\\${s}).)*)\\${s}`;
    const single = new RegExp(`(?<![\\w$>])(?:__|_e)\\s*\\(\\s*${q(1)}`, 'gs');
    const plural = new RegExp(`(?<![\\w$>])_n\\s*\\(\\s*${q(1)}\\s*,\\s*${q(3)}`, 'gs');

    const entries = new Map();
    const add = (key, data, ref) => {
        const existing = entries.get(key);
        if (existing) { existing.refs.add(ref); return; }
        entries.set(key, { ...data, refs: new Set([ref]) });
    };

    for (const rel of phpSourceFiles()) {
        const text = fs.readFileSync(path.join(ROOT, rel), 'utf8');
        for (const m of text.matchAll(plural)) {
            const msgid = phpUnescape(m[1], m[2]);
            const msgidPlural = phpUnescape(m[3], m[4]);
            if (msgid === '') continue;
            add(msgid + '\u0000' + msgidPlural, { msgid, msgidPlural }, `${rel}:${lineAt(text, m.index)}`);
        }
        for (const m of text.matchAll(single)) {
            const msgid = phpUnescape(m[1], m[2]);
            if (msgid === '') continue;
            if (entries.has(msgid)) { add(msgid, {}, `${rel}:${lineAt(text, m.index)}`); continue; }
            add(msgid, { msgid }, `${rel}:${lineAt(text, m.index)}`);
        }
    }
    return entries;
}

// Build a gettext-parser table. `fill` maps an entry to its msgstr array.
function table(entries, headers, fill) {
    const translations = { '': { '': { msgid: '', msgstr: [''] } } };
    for (const key of [...entries.keys()].sort((a, b) => a.localeCompare(b))) {
        const e = entries.get(key);
        const node = {
            msgid: e.msgid,
            msgstr: fill(e),
            comments: { reference: [...e.refs].sort().join('\n') },
        };
        if (e.msgidPlural !== undefined) node.msgid_plural = e.msgidPlural;
        translations[''][e.msgid] = node;
    }
    return { charset: 'UTF-8', headers, translations };
}

const potHeaders = {
    'project-id-version': PROJECT_ID,
    'report-msgid-bugs-to': 'https://github.com/mindstellar/theme-storefront/issues',
    'MIME-Version': '1.0',
    'content-type': 'text/plain; charset=UTF-8',
    'content-transfer-encoding': '8bit',
    'plural-forms': PLURAL_FORMS,
};

// Compile a PO catalogue. gettext-parser lower-cases the Content-Type charset to
// "utf-8"; every gettext tool writes "UTF-8", so normalise it back for the .po/.pot.
function compilePo(tbl) {
    const text = gettextParser.po.compile(tbl, { sort: false })
        .toString('utf8')
        .replace('charset=utf-8', 'charset=UTF-8');
    return Buffer.from(text, 'utf8');
}

function writeFileIfChanged(file, buf) {
    const next = Buffer.isBuffer(buf) ? buf : Buffer.from(buf);
    if (fs.existsSync(file) && Buffer.compare(fs.readFileSync(file), next) === 0) return false;
    fs.writeFileSync(file, next);
    return true;
}

function doExtract() {
    const entries = extract();

    // Template: empty msgstrs.
    const pot = table(entries, { ...potHeaders, language: '' }, (e) =>
        e.msgidPlural !== undefined ? ['', ''] : ['']);
    writeFileIfChanged(POT, compilePo(pot));

    // Default en_US: identity (English is the source).
    const enDir = path.join(LANG_DIR, 'en_US');
    fs.mkdirSync(enDir, { recursive: true });
    writeFileIfChanged(path.join(enDir, 'index.php'), SILENCE + '\n');
    const en = table(entries, { ...potHeaders, language: 'en_US' }, (e) =>
        e.msgidPlural !== undefined ? [e.msgid, e.msgidPlural] : [e.msgid]);
    writeFileIfChanged(path.join(enDir, 'theme.po'), compilePo(en));

    console.log(`i18n: extracted ${entries.size} strings -> languages/storefront.pot, languages/en_US/theme.po`);
}

function doCompile() {
    if (!fs.existsSync(LANG_DIR)) return;
    let count = 0;
    for (const locale of fs.readdirSync(LANG_DIR)) {
        const poFile = path.join(LANG_DIR, locale, 'theme.po');
        if (!fs.existsSync(poFile)) continue;
        const parsed = gettextParser.po.parse(fs.readFileSync(poFile));
        // Stamp the current theme version onto the compiled catalogue, so a shipped
        // .mo always matches the release even if the .po was not re-extracted.
        parsed.headers = { ...(parsed.headers || {}), 'project-id-version': PROJECT_ID };
        const moFile = path.join(LANG_DIR, locale, 'theme.mo');
        fs.writeFileSync(moFile, gettextParser.mo.compile(parsed));
        count++;
        console.log(`i18n: compiled ${locale}/theme.po -> ${locale}/theme.mo`);
    }
    if (count === 0) console.log('i18n: no languages/<locale>/theme.po found to compile');
}

const mode = process.argv[2];
if (mode === 'extract') doExtract();
else if (mode === 'compile') doCompile();
else { console.error('usage: node bin/i18n.mjs <extract|compile>'); process.exit(1); }
