<?php

namespace App\Core\Support;

use Carbon\Carbon;

class LocaleFormatter
{
    /**
     * Format a monetary amount according to locale rules.
     * German (de): 1.234,56 €
     * English (en): €1,234.56
     */
    public static function currency(float $amount, ?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();

        if ($loc === 'de') {
            return number_format($amount, 2, ',', '.') . ' €';
        }

        return '€' . number_format($amount, 2, '.', ',');
    }

    /**
     * Format a number according to locale rules.
     * German (de): 1.234,50
     * English (en): 1,234.50
     */
    public static function number(float $number, int $decimals = 2, ?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();

        if ($loc === 'de') {
            return number_format($number, $decimals, ',', '.');
        }

        return number_format($number, $decimals, '.', ',');
    }

    /**
     * Format a date according to locale standards.
     * German (de): 21.08.2026
     * English (en): Aug 21, 2026
     */
    public static function date($date, ?string $format = null, ?string $locale = null): string
    {
        if (!$date) return '';

        $loc = $locale ?: app()->getLocale();
        $carbon = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $carbon->setLocale($loc);

        if ($format) {
            return $carbon->translatedFormat($format);
        }

        if ($loc === 'de') {
            return $carbon->format('d.m.Y');
        }

        return $carbon->format('M d, Y');
    }

    /**
     * Format a time according to locale standards.
     * German (de): 14:30
     * English (en): 2:30 PM
     */
    public static function time($time, ?string $locale = null): string
    {
        if (!$time) return '';

        $loc = $locale ?: app()->getLocale();
        $carbon = $time instanceof Carbon ? $time->copy() : Carbon::parse($time);
        $carbon->setLocale($loc);

        if ($loc === 'de') {
            return $carbon->format('H:i');
        }

        return $carbon->format('g:i A');
    }

    /**
     * Format datetime according to locale standards.
     * German (de): 21.08.2026 14:30
     * English (en): Aug 21, 2026, 2:30 PM
     */
    public static function datetime($datetime, ?string $locale = null): string
    {
        if (!$datetime) return '';

        $loc = $locale ?: app()->getLocale();
        $carbon = $datetime instanceof Carbon ? $datetime->copy() : Carbon::parse($datetime);
        $carbon->setLocale($loc);

        if ($loc === 'de') {
            return $carbon->format('d.m.Y H:i');
        }

        return $carbon->format('M d, Y, g:i A');
    }
}
