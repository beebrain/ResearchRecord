<?php

/**
 * LINE Safe Link Helper
 * Helps create links that work properly in LINE app
 */

if (!function_exists('line_safe_url')) {
    /**
     * Generate a LINE-safe URL that redirects through the landing page
     *
     * @param string $url The target URL (can be relative or absolute)
     * @return string The LINE-safe URL
     */
    function line_safe_url($url = '') {
        // If no URL provided, use current base URL
        if (empty($url)) {
            $url = base_url();
        }

        // If relative URL, convert to absolute
        if (strpos($url, 'http') !== 0) {
            $url = base_url($url);
        }

        // Return URL to landing page with target URL as parameter
        return base_url('line-redirect.html?url=' . urlencode($url));
    }
}

if (!function_exists('is_line_browser')) {
    /**
     * Check if current request is from LINE in-app browser
     *
     * @return bool
     */
    function is_line_browser() {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
        return strpos($userAgent, 'line') !== false;
    }
}

if (!function_exists('is_in_app_browser')) {
    /**
     * Check if current request is from any in-app browser (LINE, Facebook, Instagram)
     *
     * @return bool
     */
    function is_in_app_browser() {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

        // Check for common in-app browsers
        $inAppBrowsers = ['line', 'fbav', 'fban', 'instagram'];

        foreach ($inAppBrowsers as $browser) {
            if (strpos($userAgent, $browser) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('get_shareable_link')) {
    /**
     * Get a shareable link optimized for social media/messaging apps
     *
     * @param string $url The target URL
     * @param bool $forceLandingPage Force use of landing page even if not in in-app browser
     * @return string
     */
    function get_shareable_link($url = '', $forceLandingPage = true) {
        // If force landing page or user is in in-app browser
        if ($forceLandingPage || is_in_app_browser()) {
            return line_safe_url($url);
        }

        // Otherwise return direct URL
        return empty($url) ? base_url() : (strpos($url, 'http') === 0 ? $url : base_url($url));
    }
}
