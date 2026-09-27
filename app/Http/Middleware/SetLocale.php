<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->get('lang') ?? $request->cookie('site_lang', 'id');

        if (! in_array($locale, ['id', 'en'], true)) {
            $locale = 'id';
        }

        if ($request->has('lang') && in_array($request->get('lang'), ['id', 'en'], true)) {
            \Illuminate\Support\Facades\Cookie::queue('site_lang', $locale, 60 * 24 * 365);
        }

        app()->setLocale($locale);

        return $next($request);
    }
}