<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Owner-typed texts that hold one value per language, stored as the key plus the locale code.
 */
if (!function_exists('storefront_text_keys')) {
    function storefront_text_keys()
    {
        return array('hero_headline', 'hero_tagline', 'keyword_placeholder', 'footer_message', 'header_logo_text');
    }
}

/**
 * The enabled site languages as code => name.
 */
if (!function_exists('storefront_text_locales')) {
    function storefront_text_locales()
    {
        return osc_settings_locales();
    }
}

/**
 * One-time move of the old single value to every enabled language, so nothing changes on screen.
 * Safe to run again: a language that already has a value is left alone.
 */
if (!function_exists('storefront_migrate_text_settings')) {
    function storefront_migrate_text_settings()
    {
        foreach (storefront_text_keys() as $key) {
            $old = (string) osc_get_preference($key, 'storefront');
            if ($old === '') {
                continue;
            }
            foreach (array_keys(storefront_text_locales()) as $code) {
                if ((string) osc_get_preference($key . $code, 'storefront') === '') {
                    osc_set_preference($key . $code, $old, 'storefront');
                }
            }
            osc_delete_preference($key, 'storefront');
        }
    }
}

/**
 * What a visitor in $code sees: that language's text, then the site's default language, then
 * the old single value, or ''. The fallback is core's osc_settings_text_value(); the body is
 * its shim for core 7.0.
 */
if (!function_exists('storefront_text_value')) {
    function storefront_text_value($key, $code)
    {
        if (function_exists('osc_settings_text_value')) {
            return osc_settings_text_value($key, 'storefront', $code);
        }
        foreach (array((string) $code, osc_language()) as $locale) {
            $value = $locale === '' ? '' : (string) osc_get_preference($key . $locale, 'storefront');
            if (trim($value) !== '') {
                return $value;
            }
        }

        return (string) osc_get_preference($key, 'storefront');
    }
}

/**
 * A per-language text for the visitor's language, or $default when none is set.
 */
if (!function_exists('storefront_text_setting')) {
    function storefront_text_setting($key, $default = '')
    {
        $value = storefront_text_value($key, osc_current_user_locale());

        return $value !== '' ? $value : $default;
    }
}

/**
 * Store each language's posted text. The form showed the old single value in every empty
 * language, so it is dropped once the languages hold it.
 */
if (!function_exists('storefront_save_text')) {
    function storefront_save_text($key)
    {
        foreach (array_keys(storefront_text_locales()) as $code) {
            osc_set_preference($key . $code, (string) Params::getParam($key . $code), 'storefront');
        }
        osc_delete_preference($key, 'storefront');
    }
}
