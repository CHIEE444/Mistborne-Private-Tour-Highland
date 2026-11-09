<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PastikanPersonel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $jabatan = mb_strtolower(trim((string) ($user?->jabatan ?? '')));
        $personel = $user?->personel;

        if (
            ! $user
            || $jabatan !== 'guide'
            || ! $personel
            || mb_strtolower(trim((string) $personel->peran)) !== $jabatan
        ) {
            // Admin sering membuka panel personel di browser yang sama. Arahkan ke
            // form login personel agar bisa berpindah akun tanpa halaman 403 polos.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('personel.masuk')
                ->withErrors(['email' => 'Masuk menggunakan akun Guide yang sudah ditautkan ke data personel.']);
        }

        return $next($request);
    }
}
