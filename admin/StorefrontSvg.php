<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Mindstellar Community
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('ABS_PATH')) {
    exit('ABS_PATH is not loaded. Direct access is not allowed.');
}

/**
 * Sanitize an operator-uploaded SVG so it is safe to print INLINE on every page of the
 * site (that inline placement is the whole point — it is how the mark inherits
 * `currentColor` and stays correct across light/dark — an `<img src="logo.svg">` cannot
 * do that, but unlike an <img> it also cannot stop the markup from running as a document:
 * a stray <script>, an `onload=` on any element, a <foreignObject>, or an external
 * `xlink:href` all execute the moment the markup lands in the page).
 *
 * This is an ALLOW-LIST, not a block-list: only the elements/attributes named below
 * survive, everything else — including anything invented after this file was written —
 * is dropped.
 */
class StorefrontSvg
{
    /**
     * Elements a logo can be built from. Deliberately absent: <script>, <foreignObject>
     * (embeds arbitrary HTML), <image> (fetches a remote document), <a> (javascript: link
     * vector), <style> (can reach the surrounding page + @import), <pattern>/<filter>
     * (reference other documents), and every SMIL animation element (a classic
     * `onload`-equivalent smuggling route).
     */
    private const ELEMENTS = array(
        'svg', 'g', 'defs', 'title', 'desc', 'symbol', 'use',
        'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'text', 'tspan',
        'linearGradient', 'radialGradient', 'stop',
        'clipPath', 'mask',
    );

    /**
     * Attributes a logo can carry. Notably absent: every `on*` event handler (excluded by
     * simply never being listed) and `style` (can carry `url()`).
     */
    private const ATTRIBUTES = array(
        // structure + identity
        'id', 'class', 'viewBox', 'xmlns', 'version', 'preserveAspectRatio',
        'width', 'height', 'x', 'y', 'transform',
        // geometry
        'd', 'points', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
        'fx', 'fy', 'fr', 'offset', 'pathLength',
        // paint
        'fill', 'fill-opacity', 'fill-rule', 'opacity',
        'stroke', 'stroke-width', 'stroke-opacity', 'stroke-linecap', 'stroke-linejoin',
        'stroke-dasharray', 'stroke-dashoffset', 'stroke-miterlimit',
        'stop-color', 'stop-opacity', 'color',
        'gradientUnits', 'gradientTransform', 'spreadMethod',
        'clip-path', 'clip-rule', 'mask', 'maskUnits', 'clipPathUnits',
        // type
        'font-family', 'font-size', 'font-weight', 'font-style',
        'text-anchor', 'dominant-baseline', 'letter-spacing',
        // accessibility
        'role', 'aria-hidden', 'aria-label', 'aria-labelledby', 'focusable',
        // the only reference allowed, and only ever to a fragment of this same document
        'href', 'xlink:href',
    );

    /**
     * Attributes that name somewhere else. A reference may only ever point INSIDE this
     * document — `#a` is a local gradient/clip reference; anything else is a fetch.
     */
    private const REFERENCE_ATTRIBUTES = array('href', 'xlink:href', 'clip-path', 'mask', 'fill', 'stroke');

    /** A logo is not a document; 64KB of markup is already generous. */
    public const MAX_BYTES = 65536;

    /**
     * @param string $svg raw markup straight off an upload
     *
     * @return string sanitized markup, or '' if it is not a usable SVG
     */
    public static function clean($svg)
    {
        $svg = trim((string) $svg);

        if ($svg === '' || strlen($svg) > self::MAX_BYTES) {
            return '';
        }

        // A DOCTYPE/ENTITY is refused outright rather than stripped — that is how XXE and
        // billion-laughs expansion get in, and no logo needs one. Reject before handing
        // anything to the parser.
        if (preg_match('~<!DOCTYPE~i', $svg) || preg_match('~<!ENTITY~i', $svg)) {
            return '';
        }

        $previous = libxml_use_internal_errors(true);

        $doc                     = new DOMDocument();
        $doc->preserveWhiteSpace = false;

        // LIBXML_NONET: no network at parse time, ever. NOENT is deliberately NOT set —
        // entity substitution is the thing being refused.
        $loaded = $doc->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !$doc->documentElement) {
            return '';
        }

        // The root must actually be an <svg> — a logo that is secretly an <html> is not one.
        if (strtolower($doc->documentElement->nodeName) !== 'svg') {
            return '';
        }

        self::scrub($doc->documentElement);

        // Comments and processing instructions carry nothing a logo needs and have been
        // used to smuggle markup past naive parsers.
        $xpath = new DOMXPath($doc);
        foreach (iterator_to_array($xpath->query('//comment() | //processing-instruction()')) as $node) {
            $node->parentNode->removeChild($node);
        }

        $out = $doc->saveXML($doc->documentElement);

        return is_string($out) ? trim($out) : '';
    }

    /**
     * Depth-first, walking a COPY of the child list — removing a node while iterating a
     * live DOMNodeList silently skips its sibling, which is how half-sanitized markup
     * ships.
     *
     * @param DOMElement $element
     */
    private static function scrub(DOMElement $element)
    {
        foreach (iterator_to_array($element->childNodes) as $child) {
            /** @var DOMNode $child */
            if ($child->nodeType === XML_ELEMENT_NODE) {
                /** @var DOMElement $child */
                if (!in_array($child->nodeName, self::ELEMENTS, true)) {
                    $element->removeChild($child);
                    continue;
                }

                self::scrub($child);
                continue;
            }

            // Text inside <title>/<desc> is fine; anything else that is neither an
            // element nor text (CDATA is a classic script smuggler) is not.
            if ($child->nodeType !== XML_TEXT_NODE) {
                $element->removeChild($child);
            }
        }

        self::scrubAttributes($element);
    }

    /**
     * @param DOMElement $element
     */
    private static function scrubAttributes(DOMElement $element)
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            /** @var DOMAttr $attribute */
            $name  = $attribute->nodeName;
            $value = $attribute->nodeValue;

            // Not on the allow-list — note that list contains no `on*` handler; it is not
            // blocked, it is simply never allowed.
            if (!in_array($name, self::ATTRIBUTES, true) && strpos($name, 'xmlns:') !== 0) {
                $element->removeAttribute($name);
                continue;
            }

            if (self::isDangerousValue($name, $value)) {
                $element->removeAttribute($name);
            }
        }
    }

    /**
     * True when a value names somewhere else it shouldn't. `fill`/`stroke` are on the
     * reference list too — they legitimately take `url(#gradient)`, and `url(https://…)`
     * is the same syntax pointing at somebody else's server.
     *
     * @param string $name
     * @param string $value
     *
     * @return bool
     */
    private static function isDangerousValue($name, $value)
    {
        // Strip whitespace/control chars before matching: `java\0script:` and
        // `java\nscript:` are both live in some parsers.
        $flat = strtolower(preg_replace('~[\s\x00-\x1f\x7f]+~', '', (string) $value));

        if (strpos($flat, 'javascript:') !== false
            || strpos($flat, 'data:') !== false
            || strpos($flat, 'vbscript:') !== false
            || strpos($flat, '&#') !== false) {
            return true;
        }

        if (in_array($name, self::REFERENCE_ATTRIBUTES, true)) {
            // A bare fragment (`#id`) or a url() pointing at one is fine; anything else
            // names a document, and a logo has no business fetching one.
            if (strpos($flat, 'url(') !== false && strpos($flat, 'url(#') !== 0) {
                return true;
            }

            if (($name === 'href' || $name === 'xlink:href') && strpos($flat, '#') !== 0) {
                return true;
            }
        }

        return false;
    }
}
