<?php

if (!function_exists('t')) {
    function t(string $key): string
    {
        return __($key);
    }
}

if (!function_exists('formatRupiah')) {
    function formatRupiah($amount): string
    {
        return 'Rp' . number_format((float) $amount, 0, ',', '.');
    }
}

if (!function_exists('formatRupiahOwner')) {
    // Format khusus Owner Panel sesuai spec: "Rp 48.000" (pakai spasi). Selalu bungkus
    // hasilnya dengan class "whitespace-nowrap" di Blade biar gak kepotong pas line-wrap.
    function formatRupiahOwner($amount): string
    {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }
}

if (!function_exists('yk_is_logged_in')) {
    function yk_is_logged_in(): bool
    {
        return auth()->check();
    }
}

if (!function_exists('yk_user_email')) {
    function yk_user_email(): string
    {
        return auth()->check() ? auth()->user()->email : '';
    }
}

if (!function_exists('yk_format_blog_date')) {
    function yk_format_blog_date($dateStr, $lang = null): string
    {
        $lang = $lang ?: app()->getLocale();
        $bulan_id = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $bulan_en = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $ts = is_numeric($dateStr) ? (int)$dateStr : strtotime($dateStr);
        if (!$ts) return '';
        $bulan = ($lang === 'en') ? $bulan_en : $bulan_id;
        return date('j', $ts) . ' ' . $bulan[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
    }
}

if (!function_exists('lang_switch_url')) {
    function lang_switch_url(string $locale): string
    {
        return route('lang.switch', $locale);
    }
}
