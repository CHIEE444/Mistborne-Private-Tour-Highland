<?php

use App\Http\Controllers\PersonelPanelController;
use App\Http\Controllers\Publik\ArtikelController;
use App\Http\Controllers\Publik\FormulirPesananController;
use App\Http\Controllers\Publik\HotelController;
use App\Http\Controllers\Publik\JourneyController;
use App\Http\Controllers\Publik\PesananKustomController;
use App\Http\Controllers\Publik\UlasanController;
use Illuminate\Support\Facades\Route;

// Multi-bahasa: '/' = Indonesia, '/en' '/ms' '/zh' '/ja' '/ko' '/es' '/fr' '/ar' = view yang sama dengan locale lain;
// '/id' dialihkan 301 ke '/'. Tanpa pengalihan otomatis berdasarkan Accept-Language (demi SEO).
Route::redirect('/id', '/', 301);
// Alamat berawalan /id/ (bahasa Indonesia adalah bawaan tanpa awalan) dialihkan ke alamat tanpa awalan.
Route::get('/id/{halaman}', fn (string $halaman) => redirect('/'.$halaman, 301))
    ->where('halaman', '(journey|galeri|artikel)(/[A-Za-z0-9\-]+)?');

Route::get('/', function () {
    app()->setLocale('id');

    return view('pages.beranda');
})->name('home');

Route::get('/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['id', 'en', 'ms', 'zh', 'ja', 'ko', 'es', 'fr', 'ar'], true), 404);

    app()->setLocale($locale);

    return view('pages.beranda');
})->where('locale', 'en|ms|zh|ja|ko|es|fr|ar')->name('home.localized');

// [BARU] Halaman Journey: daftar lengkap semua kartu booking (partial data bersama).
// '/' = Indonesia (default); '/{locale}/journey' = view sama, locale sesuai pilihan.
Route::get('/journey', fn () => view('pages.journey'))->name('journeys.index');
Route::get('/{locale}/journey', function (string $locale) {
    abort_unless(in_array($locale, ['id', 'en', 'ms', 'zh', 'ja', 'ko', 'es', 'fr', 'ar'], true), 404);

    app()->setLocale($locale);

    return view('pages.journey');
})->where('locale', 'en|ms|zh|ja|ko|es|fr|ar')->name('journeys.index.localized');

// Halaman detail satu journey: deskripsi, highlight, itinerary, info praktis, dan tanggal tersedia.
Route::get('/journey/{slug}', [JourneyController::class, 'show'])->name('journeys.show');

// Halaman detail satu hotel mitra (hanya hotel aktif): galeri, fasilitas, tipe kamar, FAQ, dan ulasan tamu journey terkait.
Route::get('/hotel/{hotel:slug}', [HotelController::class, 'show'])->name('hotel.show');
Route::get('/{locale}/journey/{slug}', [JourneyController::class, 'show'])
    ->where('locale', 'en|ms|zh|ja|ko|es|fr|ar')
    ->name('journeys.show.localized');

// [BARU] Halaman Galeri: arsip foto lengkap.
// '/' = Indonesia (default); '/{locale}/galeri' = view sama, locale sesuai pilihan.
Route::get('/galeri', fn () => view('pages.galeri'))->name('gallery.index');
Route::get('/{locale}/galeri', function (string $locale) {
    abort_unless(in_array($locale, ['id', 'en', 'ms', 'zh', 'ja', 'ko', 'es', 'fr', 'ar'], true), 404);

    app()->setLocale($locale);

    return view('pages.galeri');
})->where('locale', 'en|ms|zh|ja|ko|es|fr|ar')->name('gallery.index.localized');

// [BARU] Halaman Artikel: arsip lengkap Jurnal Perjalanan.
// '/' = Indonesia (default); '/{locale}/artikel' = view sama, locale sesuai pilihan.
Route::get('/artikel', fn () => view('pages.artikel'))->name('articles.index');
Route::get('/{locale}/artikel', function (string $locale) {
    abort_unless(in_array($locale, ['id', 'en', 'ms', 'zh', 'ja', 'ko', 'es', 'fr', 'ar'], true), 404);

    app()->setLocale($locale);

    return view('pages.artikel');
})->where('locale', 'en|ms|zh|ja|ko|es|fr|ar')->name('articles.index.localized');
Route::get('/artikel/{slug}', [ArtikelController::class, 'show'])->name('articles.show');

// Rancang Trip Kustom: satu-satunya pintu masuk pesanan kustom (hanya bahasa Indonesia).
Route::get('/rancang-trip-kustom', [PesananKustomController::class, 'create'])->name('rancang-trip-kustom');
Route::post('/rancang-trip-kustom', [PesananKustomController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('rancang-trip-kustom.simpan');

// [BOOKING WEB] Form data diri dari kartu destinasi. Tamu mengisi langsung tanpa login.
// destination = nama journey (contoh: BROMO SUNRISE JOURNEY).
use App\Http\Controllers\Publik\BookingController;
use App\Http\Controllers\Publik\PertanyaanTamuController;

// Pertanyaan calon tamu (form kontak / tombol WhatsApp) dicatat sebelum WhatsApp dibuka.
Route::post('/pertanyaan', [PertanyaanTamuController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('pertanyaan.simpan');

Route::get('/booking', [BookingController::class, 'create'])->name('booking.buat');
Route::post('/booking', [BookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('booking.simpan');

Route::get('/form-pesanan/{token}', [FormulirPesananController::class, 'show'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('form-pesanan.tampil');
Route::post('/form-pesanan/{token}', [FormulirPesananController::class, 'store'])
    ->where('token', '[A-Za-z0-9]+')
    ->middleware('throttle:5,1')
    ->name('form-pesanan.simpan');

// Halaman rating lewat token: satu ulasan per pesanan (hanya bahasa Indonesia).
Route::get('/rating/{token}', [UlasanController::class, 'create'])->name('rating.formulir');
Route::post('/rating/{token}', [UlasanController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('rating.simpan');

Route::prefix('personel')->name('personel.')->group(function (): void {
    // Tanpa middleware "guest": tautan email penugasan harus selalu membuka halaman masuk panel Guide,
    // bukan terlempar ke website publik ketika browser masih memegang sesi akun lain (mis. admin).
    Route::get('masuk', [PersonelPanelController::class, 'masuk'])->name('masuk');
    Route::post('masuk', [PersonelPanelController::class, 'prosesMasuk'])->name('proses-masuk');

    Route::middleware(['auth', 'hak-personel'])->group(function (): void {
        Route::get('/', [PersonelPanelController::class, 'dashboard'])->name('dashboard');
        Route::get('aktivitas', [PersonelPanelController::class, 'aktivitas'])->name('aktivitas');
        Route::get('profil', [PersonelPanelController::class, 'profil'])->name('profil');
        Route::put('profil/sandi', [PersonelPanelController::class, 'ubahSandi'])->name('profil.sandi');
        Route::post('pesanan/{pesanan}/tanggapi', [PersonelPanelController::class, 'tanggapi'])->name('pesanan.tanggapi');
        Route::post('pesanan/{pesanan}/trip-selesai', [PersonelPanelController::class, 'selesaikanTrip'])->name('pesanan.trip-selesai');
        Route::post('keluar', [PersonelPanelController::class, 'keluar'])->name('keluar');
    });
});
