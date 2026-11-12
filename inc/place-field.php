<?php
/*
 * Storefront — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/** Whether the core prints the Location field and owns its style setting. */
if (!function_exists('storefront_core_place_field')) {
    function storefront_core_place_field(): bool
    {
        return function_exists('osc_place_field') && function_exists('osc_item_place_style');
    }
}

/**
 * Once, on a core that owns the style: a site that chose dropdowns here keeps them, when the
 * core setting was never saved. Autocomplete sites get the core default, one box.
 */
if (!function_exists('storefront_map_place_style')) {
    function storefront_map_place_style(): void
    {
        if (!storefront_core_place_field() || osc_get_preference('place_style_mapped', 'storefront') === '1') {
            return;
        }
        if ((string) osc_get_preference('item_place_style') === '' && osc_get_preference('location_input_type', 'storefront') === 'dropdown') {
            osc_set_preference('item_place_style', 'dropdown');
        }
        osc_set_preference('place_style_mapped', '1', 'storefront');
    }
}
