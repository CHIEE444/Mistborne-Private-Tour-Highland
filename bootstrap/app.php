<?php

use App\Http\Middleware\PastikanAdmin;
use App\Http\Middleware\PastikanPersonel;
use App\Http\Middleware\PertahankanBahasaPublik;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        then: function (): void {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(__DIR__.'/../routes/admin.php');
        },
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Aliases middleware kustom:
        // - "hak-admin" = pelindung tambahan rute admin (hanya jabatan admin, guide tidak boleh masuk);
        //   dipasangkan dengan middleware "auth" di routes/admin.php.
        $middleware->alias([
            'hak-admin' => PastikanAdmin::class,
            'hak-personel' => PastikanPersonel::class,
        ]);

        // Cek hak akses harus sebelum pengikatan model; kalau tidak, pengguna tanpa hak bisa membedakan
        // ID yang ada (dialihkan) dari ID yang tidak ada (404).
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: PastikanAdmin::class);
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: PastikanPersonel::class);

        $middleware->web(append: [PertahankanBahasaPublik::class]);

        // Pengguna yang sudah login dan membuka halaman khusus tamu (masuk, lupa sandi) dikembalikan ke beranda
        // perannya sendiri, bukan ke beranda publik.
        $middleware->redirectUsersTo(function (Request $request): string {
            $jabatan = mb_strtolower(trim((string) $request->user()?->jabatan));

            return match (true) {
                in_array($jabatan, ['admin', 'admin operasional'], true) => route('admin.dashboard.indeks'),
                $jabatan === 'guide' => route('personel.dashboard'),
                default => route('home'),
            };
        });

        // Seluruh area terautentikasi di aplikasi ini adalah admin panel (prefix /admin);
        // tidak ada route bernama "login" bawaan, jadi tamu diarahkan ke halaman masuk admin.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('personel*')
            ? route('personel.masuk')
            : route('admin.autentikasi.masuk'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson(),
        );
    })->create();
