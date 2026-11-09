<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyimpan bahasa pilihan tamu di sesi agar halaman tanpa awalan bahasa (booking, form pesanan, rating) ikut bahasanya.
 */
class PertahankanBahasaPublik
{
    private const BAHASA = ['id', 'en', 'ms', 'zh', 'ja', 'ko', 'es', 'fr', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*', 'personel', 'personel/*')) {
            return $next($request);
        }

        $dariUrl = $request->routeIs('home') ? 'id' : $request->route('locale');

        if (is_string($dariUrl) && in_array($dariUrl, self::BAHASA, true)) {
            $request->session()->put('bahasa', $dariUrl);
            $bahasa = $dariUrl;
        } else {
            $bahasa = $request->session()->get('bahasa');
        }

        if (is_string($bahasa) && in_array($bahasa, self::BAHASA, true)) {
            app()->setLocale($bahasa);
        }

        return $next($request);
    }
}
