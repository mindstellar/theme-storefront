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
        return array('hero_tagline', 'footer_message');
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
 * A per-language text: the visitor's language, then the site's default language, then $default.
 */
if (!function_exists('storefront_text_setting')) {
    function storefront_text_setting($key, $default = '')
    {
        foreach (array(osc_current_user_locale(), osc_language()) as $code) {
            $value = (string) osc_get_preference($key . $code, 'storefront');
            if ($value !== '') {
                return $value;
            }
        }
        $old = (string) osc_get_preference($key, 'storefront');

        return $old !== '' ? $old : $default;
    }
}

if (!function_exists('storefront_save_text')) {
    function storefront_save_text($key)
    {
        foreach (array_keys(storefront_text_locales()) as $code) {
            osc_set_preference($key . $code, (string) Params::getParam($key . $code), 'storefront');
        }
    }
}
