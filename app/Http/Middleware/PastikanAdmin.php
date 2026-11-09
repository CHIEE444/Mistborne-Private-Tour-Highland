<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Larangan tambahan di atas middleware "auth" untuk rute admin: hanya jabatan admin yang boleh masuk.
 * Akun guide memakai guard "web" yang sama, jadi tanpa pemeriksaan ini guide yang sudah login
 * bisa membuka panel admin.
 */
class PastikanAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $jabatan = mb_strtolower(trim((string) ($user?->jabatan ?? '')));

        // Hanya jabatan admin yang masuk; akun tanpa jabatan (kosong) tidak otomatis menjadi admin.
        if (! $user || ! in_array($jabatan, ['admin', 'admin operasional'], true)) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.autentikasi.masuk');
        }

        return $next($request);
    }
}
