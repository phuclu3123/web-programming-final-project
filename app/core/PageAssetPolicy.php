<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Conservative route-aware asset policy for the two public layouts.
 *
 * Each entry is the exact Controller::view() identifier (not a URL heuristic).
 * Only known, exercised views are specialized. Unknown views retain the existing
 * asset set, so adding a new route never silently removes styles or behavior.
 *
 * Assets not listed in the conditional set remain shared and are always loaded:
 * Bootstrap, local Font Awesome, design-system, product/release/integration,
 * member/public header, auth support and public coupon/event actions.
 */
final class PageAssetPolicy
{
    /** @var array<string, list<string>> */
    private const PROFILES = [
        // Standalone public home includes editorial/campaign feature components.
        'home' => ['discovery.css', 'editorial.css', 'category-navigation.css',
            'discovery.js', 'editorial.js', 'hero-carousel.js', 'campaign-clock.js', 'category-picker.js'],
        // Community Coupons uses discovery + editorial coupon styles but not
        // the home-only discovery autocomplete, carousel or campaign clock.
        'coupons/index' => ['discovery.css', 'editorial.css', 'category-navigation.css', 'category-picker.js'],
        // Category directory requires editorial hover/focus image preview.
        'categories/index' => ['discovery.css', 'editorial.css', 'category-navigation.css',
            'editorial.js', 'category-picker.js'],
        // The standalone workspaces do not render the public discovery features.
        'shop' => [],
        'admin' => [],
        'error' => [],
        // Simple member-layout content without discovery, campaign or category UI.
        'info/page' => [],
        'auth/reset-password' => [],
        'notifications/index' => [],
    ];

    /** @var list<string> */
    private const CONDITIONAL = [
        'discovery.css', 'editorial.css', 'category-navigation.css',
        'discovery.js', 'editorial.js', 'hero-carousel.js',
        'campaign-clock.js', 'category-picker.js',
    ];

    public static function enabled(string $view, string $asset): bool
    {
        // Fail open for unrecognized assets or views: preserve old behavior.
        if (!in_array($asset, self::CONDITIONAL, true) || !array_key_exists($view, self::PROFILES)) {
            return true;
        }

        return in_array($asset, self::PROFILES[$view], true);
    }
}
