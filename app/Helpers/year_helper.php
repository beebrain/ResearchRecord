<?php

/**
 * Year Helper Functions
 * Provides functions for formatting years in Thai Buddhist Era (พ.ศ.) and Christian Era (ค.ศ.)
 */

if (!function_exists('format_year_thai')) {
    /**
     * Format year as "พ.ศ. 2567 (ค.ศ. 2024)"
     *
     * @param int|string $year Christian year (ค.ศ.)
     * @param bool $shortFormat If true, returns "2567(2024)", if false returns "พ.ศ. 2567 (ค.ศ. 2024)"
     * @return string Formatted year string
     */
    function format_year_thai($year, $shortFormat = false)
    {
        if (empty($year)) {
            return '-';
        }

        $christianYear = (int)$year;
        $buddhistYear = $christianYear + 543;

        if ($shortFormat) {
            return "{$buddhistYear}({$christianYear})";
        }

        return "พ.ศ. {$buddhistYear} (ค.ศ. {$christianYear})";
    }
}

if (!function_exists('format_year_be')) {
    /**
     * Convert Christian year to Buddhist year
     *
     * @param int|string $year Christian year
     * @return int Buddhist year
     */
    function format_year_be($year)
    {
        return (int)$year + 543;
    }
}

if (!function_exists('format_year_ce')) {
    /**
     * Convert Buddhist year to Christian year
     *
     * @param int|string $year Buddhist year
     * @return int Christian year
     */
    function format_year_ce($year)
    {
        return (int)$year - 543;
    }
}

if (!function_exists('normalize_year_to_ce')) {
    /**
     * Normalize year to Christian Era (ค.ศ.) for database storage
     * Automatically detects if input is BE (พ.ศ.) or CE (ค.ศ.) and converts to CE
     *
     * @param int|string|null $year Year value (can be BE or CE)
     * @return int|null Christian year (ค.ศ.) or null if empty
     */
    function normalize_year_to_ce($year)
    {
        if (empty($year) || $year === null || $year === '') {
            return null;
        }

        $year = (int)$year;

        // If year is >= 2443 (BE 2443 = CE 1900), it's likely Buddhist Era
        // If year is < 1900, it's likely already CE but very old
        // If year is between 1900-2100, it's likely CE
        // If year is > 2100, it's likely BE

        if ($year >= 2443) {
            // Buddhist Era - convert to Christian Era
            return $year - 543;
        } elseif ($year >= 1900 && $year <= 2100) {
            // Likely Christian Era - return as is
            return $year;
        } elseif ($year < 1900) {
            // Very old year - assume it's already CE
            return $year;
        } else {
            // Year > 2100 but < 2443 - unlikely, but assume it's BE
            return $year - 543;
        }
    }
}

if (!function_exists('get_current_year_be')) {
    /**
     * Get current year in Buddhist Era
     *
     * @return int Current Buddhist year
     */
    function get_current_year_be()
    {
        return (int)date('Y') + 543;
    }
}

if (!function_exists('format_date_thai')) {
    /**
     * Format date with Thai Buddhist year
     *
     * @param string $date Date string
     * @param string $format PHP date format
     * @return string Formatted date with Buddhist year
     */
    function format_date_thai($date, $format = 'd/m/Y')
    {
        if (empty($date)) {
            return '-';
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        $formattedDate = date($format, $timestamp);

        // Replace year with Buddhist year
        $year = date('Y', $timestamp);
        $buddhistYear = (int)$year + 543;
        $formattedDate = str_replace($year, $buddhistYear, $formattedDate);

        return $formattedDate;
    }
}

if (!function_exists('format_month_thai')) {
    /**
     * Get Thai month name
     *
     * @param int $month Month number (1-12)
     * @param bool $short If true, returns short name
     * @return string Thai month name
     */
    function format_month_thai($month, $short = false)
    {
        $months = [
            1 => ['full' => 'มกราคม', 'short' => 'ม.ค.'],
            2 => ['full' => 'กุมภาพันธ์', 'short' => 'ก.พ.'],
            3 => ['full' => 'มีนาคม', 'short' => 'มี.ค.'],
            4 => ['full' => 'เมษายน', 'short' => 'เม.ย.'],
            5 => ['full' => 'พฤษภาคม', 'short' => 'พ.ค.'],
            6 => ['full' => 'มิถุนายน', 'short' => 'มิ.ย.'],
            7 => ['full' => 'กรกฎาคม', 'short' => 'ก.ค.'],
            8 => ['full' => 'สิงหาคม', 'short' => 'ส.ค.'],
            9 => ['full' => 'กันยายน', 'short' => 'ก.ย.'],
            10 => ['full' => 'ตุลาคม', 'short' => 'ต.ค.'],
            11 => ['full' => 'พฤศจิกายน', 'short' => 'พ.ย.'],
            12 => ['full' => 'ธันวาคม', 'short' => 'ธ.ค.']
        ];

        $month = (int)$month;

        if (!isset($months[$month])) {
            return '';
        }

        return $short ? $months[$month]['short'] : $months[$month]['full'];
    }
}

if (!function_exists('format_publication_date')) {
    /**
     * Format publication date with month and year in Thai
     *
     * @param int $year Publication year (Christian)
     * @param int|null $month Publication month
     * @return string Formatted date string
     */
    function format_publication_date($year, $month = null)
    {
        if (empty($year)) {
            return '-';
        }

        $buddhistYear = (int)$year + 543;

        if (!empty($month)) {
            $monthName = format_month_thai($month, false);
            return "{$monthName} พ.ศ. {$buddhistYear}";
        }

        return "พ.ศ. {$buddhistYear}";
    }
}
