<?php

use App\Http\Controllers\Admin\ArtikelController;
use App\Http\Controllers\Admin\AutentikasiController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HotelController;
use App\Http\Controllers\Admin\JadwalKeberangkatanController;
use App\Http\Controllers\Admin\KalenderTripController;
use App\Http\Controllers\Admin\KendaraanController;
use App\Http\Controllers\Admin\NotifikasiController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PerjalananController;
use App\Http\Controllers\Admin\PermintaanPesananController;
use App\Http\Controllers\Admin\PersonelOperasionalController;
use App\Http\Controllers\Admin\PertanyaanTamuController;
use App\Http\Controllers\Admin\PesananController;
use App\Http\Controllers\Admin\PesananKustomController;
use App\Http\Controllers\Admin\ProfilController;
use App\Http\Controllers\Admin\SelesaikanHotelController;
use App\Http\Controllers\Admin\ServisKendaraanController;
use App\Http\Controllers\Admin\UlasanController;
use Illuminate\Support\Facades\Route;

// Rute admin (prefix "admin.", didaftarkan di bootstrap/app.php): autentikasi terbuka, sisanya wajib login.

Route::middleware('guest')->group(function (): void {
    Route::get('masuk', [AutentikasiController::class, 'create'])->name('autentikasi.masuk');
    Route::post('masuk', [AutentikasiController::class, 'store'])->name('autentikasi.proses-masuk');
    Route::get('lupa-sandi', [AutentikasiController::class, 'lupaSandi'])->name('autentikasi.lupa-sandi');
    Route::post('lupa-sandi', [AutentikasiController::class, 'kirimTautanReset'])->name('autentikasi.kirim-tautan-reset');
    Route::get('atur-ulang-sandi/{token}', [AutentikasiController::class, 'aturUlangSandi'])->name('autentikasi.atur-ulang-sandi');
    Route::post('atur-ulang-sandi', [AutentikasiController::class, 'simpanSandiBaru'])->name('autentikasi.simpan-sandi-baru');
});

Route::middleware(['auth', 'hak-admin'])->group(function (): void {
    Route::post('keluar', [AutentikasiController::class, 'destroy'])->name('autentikasi.keluar');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.indeks');
    Route::get('selesaikan-hotel', SelesaikanHotelController::class)->name('selesaikan-hotel.indeks');
    Route::get('notifikasi/ringkasan', [NotifikasiController::class, 'ringkasan'])->name('notifikasi.ringkasan');
    Route::post('notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('notifikasi/{notification}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');

    Route::prefix('pertanyaan-tamu')->name('pertanyaan-tamu.')->group(function (): void {
        Route::get('/', [PertanyaanTamuController::class, 'index'])->name('indeks');
        Route::patch('{pertanyaan}', [PertanyaanTamuController::class, 'perbarui'])->name('perbarui');
    });

    Route::prefix('permintaan-pesanan')->name('permintaan-pesanan.')->group(function (): void {
        Route::get('/', [PermintaanPesananController::class, 'index'])->name('indeks');
        Route::post('kirim-form', [PermintaanPesananController::class, 'kirimForm'])->name('kirim-form');
        Route::post('{permintaan}/kirim-ulang-form', [PermintaanPesananController::class, 'kirimUlangForm'])->name('kirim-ulang-form');
        Route::post('{permintaan}/follow-up', [PermintaanPesananController::class, 'tindakLanjut'])->name('follow-up');
        Route::post('{permintaan}/buat-pesanan', [PermintaanPesananController::class, 'buatPesanan'])->name('buat-pesanan');
        Route::post('{permintaan}/tidak-jadi', [PermintaanPesananController::class, 'tidakJadi'])->name('tidak-jadi');
    });

    Route::prefix('kendaraan')->name('kendaraan.')->group(function (): void {
        Route::get('/', [KendaraanController::class, 'index'])->name('indeks');
        Route::get('buat', [KendaraanController::class, 'create'])->name('buat');
        Route::post('/', [KendaraanController::class, 'store'])->name('simpan');
        Route::get('{kendaraan}', [KendaraanController::class, 'show'])->name('detail');
        Route::get('{kendaraan}/ubah', [KendaraanController::class, 'edit'])->name('ubah');
        Route::put('{kendaraan}', [KendaraanController::class, 'update'])->name('perbarui');
        Route::delete('{kendaraan}', [KendaraanController::class, 'destroy'])->name('hapus');

        Route::prefix('{kendaraan}/servis')->name('servis.')->scopeBindings()->group(function (): void {
            Route::post('/', [ServisKendaraanController::class, 'store'])->name('simpan');
            Route::put('{servis}', [ServisKendaraanController::class, 'update'])->name('perbarui');
            Route::delete('{servis}', [ServisKendaraanController::class, 'destroy'])->name('hapus');
        });
    });

    Route::prefix('hotel')->name('hotel.')->group(function (): void {
        Route::get('/', [HotelController::class, 'index'])->name('indeks');
        Route::get('buat', [HotelController::class, 'create'])->name('buat');
        Route::get('urus', [HotelController::class, 'urus'])->name('urus');
        Route::post('/', [HotelController::class, 'store'])->name('simpan');
        Route::get('{hotel}', [HotelController::class, 'show'])->name('detail');
        Route::get('{hotel}/ubah', [HotelController::class, 'edit'])->name('ubah');
        Route::put('{hotel}', [HotelController::class, 'update'])->name('perbarui');
        Route::delete('{hotel}', [HotelController::class, 'destroy'])->name('hapus');
    });

    Route::get('guide', [PersonelOperasionalController::class, 'index'])->name('guide.indeks');
    Route::get('guide/{personel}/foto', [PersonelOperasionalController::class, 'foto'])->name('guide.foto');
    Route::post('guide', [PersonelOperasionalController::class, 'store'])->name('guide.simpan');
    Route::put('guide/{personel}', [PersonelOperasionalController::class, 'update'])->name('guide.perbarui');
    Route::delete('guide/{personel}', [PersonelOperasionalController::class, 'destroy'])->name('guide.hapus');

    Route::prefix('pesanan')->name('pesanan.')->group(function (): void {
        Route::get('/', [PesananController::class, 'index'])->name('indeks');
        Route::get('buat', [PesananController::class, 'create'])->name('buat');
        Route::post('/', [PesananController::class, 'store'])->name('simpan');
        Route::get('{pesanan}', [PesananController::class, 'show'])->name('detail');
        Route::get('{pesanan}/ubah', [PesananController::class, 'edit'])->name('ubah');
        Route::put('{pesanan}', [PesananController::class, 'update'])->name('perbarui');
        Route::delete('{pesanan}', [PesananController::class, 'destroy'])->name('hapus');

        Route::patch('{pesanan}/konfirmasi', [PesananController::class, 'konfirmasi'])->name('konfirmasi');
        Route::patch('{pesanan}/pembayaran-diterima', [PesananController::class, 'catatPembayaranDiterima'])->name('pembayaran-diterima');
        Route::get('{pesanan}/pembayaran-diterima', fn (int $pesanan) => redirect()->route('admin.pesanan.detail', ['pesanan' => $pesanan]));
        Route::patch('{pesanan}/payment', [PesananController::class, 'ubahKePayment'])->name('payment');
        Route::patch('{pesanan}/selesaikan', [PesananController::class, 'selesaikan'])->name('selesaikan');
        Route::patch('{pesanan}/hold', [PesananController::class, 'toggleHold'])->name('hold');
        Route::post('{pesanan}/ganti-jadwal', [PesananController::class, 'gantiJadwal'])->name('ganti-jadwal');
        Route::post('{pesanan}/batalkan', [PesananController::class, 'batalkan'])->name('batalkan');

        Route::put('{pesanan}/operasional', [PesananController::class, 'simpanOperasional'])->name('operasional');
        Route::get('{pesanan}/penugasan/status', [PesananController::class, 'statusPenugasan'])->name('penugasan.status');
        Route::post('{pesanan}/penugasan/kirim-ulang', [PesananController::class, 'kirimUlangPenugasan'])->name('penugasan.kirim-ulang');
        Route::put('{pesanan}/hotel', [PesananController::class, 'simpanHotel'])->name('hotel.simpan');
        Route::put('{pesanan}/hotel/konfirmasi', [PesananController::class, 'konfirmasiHotel'])->name('hotel.konfirmasi');
        Route::patch('{pesanan}/hotel/batal', [PesananController::class, 'batalkanHotel'])->name('hotel.batal');
        Route::patch('{pesanan}/hotel/langkah/{langkah}', [PesananController::class, 'tandaiLangkahHotel'])->name('hotel.langkah.tandai');
        Route::delete('{pesanan}/hotel/langkah/{langkah}', [PesananController::class, 'batalkanTandaLangkahHotel'])->name('hotel.langkah.batal');
        Route::put('{pesanan}/hotel/pembayaran', [PesananController::class, 'simpanPembayaranHotel'])->name('hotel.pembayaran.simpan');
        Route::get('{pesanan}/hotel/bukti', [PesananController::class, 'lihatBuktiHotel'])->name('hotel.bukti.lihat');
        Route::delete('{pesanan}/hotel', [PesananController::class, 'lepasHotel'])->name('hotel.lepas');
        Route::get('{pesanan}/bukti-pembayaran', [PesananController::class, 'lihatBukti'])->name('bukti.lihat');
        Route::delete('{pesanan}/bukti-pembayaran', [PesananController::class, 'hapusBukti'])->name('bukti.hapus');
        Route::patch('{pesanan}/pengingat/{jenis}', [PesananController::class, 'tandaiPengingat'])->name('pengingat');
    });

    Route::prefix('pesanan-kustom')->name('pesanan-kustom.')->group(function (): void {
        Route::get('/', [PesananKustomController::class, 'index'])->name('indeks');
        Route::get('buat', [PesananKustomController::class, 'create'])->name('buat');
        Route::post('/', [PesananKustomController::class, 'store'])->name('simpan');
        Route::get('{id}/detail', [PesananKustomController::class, 'show'])->name('detail');
        Route::put('{pesananKustom}', [PesananKustomController::class, 'update'])->name('perbarui');
        Route::put('{pesananKustom}/tindak-lanjut', [PesananKustomController::class, 'updateTindakLanjut'])->name('perbarui-tindak-lanjut');
        Route::patch('{pesananKustom}/hubungi', [PesananKustomController::class, 'hubungi'])->name('hubungi');
        Route::post('{pesananKustom}/penawaran-terkirim', [PesananKustomController::class, 'tandaiPenawaranTerkirim'])->name('penawaran-terkirim');
        Route::delete('{pesananKustom}', [PesananKustomController::class, 'arsipkan'])->name('arsipkan');
        Route::patch('{id}/pulihkan', [PesananKustomController::class, 'pulihkan'])->name('pulihkan');
        Route::delete('{id}/hapus', [PesananKustomController::class, 'hapus'])->name('hapus');
        Route::post('{pesananKustom}/jadikan-resmi', [PesananKustomController::class, 'jadikanResmi'])->name('jadikan-resmi');
    });
    Route::prefix('kalender-trip')->name('kalender-trip.')->group(function (): void {
        Route::get('/', [KalenderTripController::class, 'index'])->name('indeks');
        Route::get('rekap-pdf', [KalenderTripController::class, 'rekapPdf'])->name('rekap-pdf');
    });

    Route::prefix('perjalanan')->name('perjalanan.')->group(function (): void {
        Route::get('/', [PerjalananController::class, 'index'])->name('indeks');
        Route::get('buat', [PerjalananController::class, 'create'])->name('buat');
        Route::post('/', [PerjalananController::class, 'store'])->name('simpan');
        Route::get('{perjalanan}/ubah', [PerjalananController::class, 'edit'])->name('ubah');
        Route::put('{perjalanan}', [PerjalananController::class, 'update'])->name('perbarui');
        Route::delete('{perjalanan}', [PerjalananController::class, 'destroy'])->name('hapus');
        Route::patch('{perjalanan}/sembunyikan', [PerjalananController::class, 'sembunyikanDariBeranda'])->name('sembunyikan');

        Route::prefix('{perjalanan}/tanggal')->name('tanggal.')->group(function (): void {
            Route::get('/', [JadwalKeberangkatanController::class, 'index'])->name('indeks');
            Route::post('/', [JadwalKeberangkatanController::class, 'store'])->name('simpan');
            Route::put('{tanggal}', [JadwalKeberangkatanController::class, 'update'])->name('perbarui');
            Route::patch('{tanggal}/blokir', [JadwalKeberangkatanController::class, 'blokir'])->name('blokir');
            Route::delete('{tanggal}', [JadwalKeberangkatanController::class, 'destroy'])->name('hapus');
        });
    });

    Route::prefix('artikel')->name('artikel.')->group(function (): void {
        Route::get('/', [ArtikelController::class, 'index'])->name('indeks');
        Route::get('buat', [ArtikelController::class, 'create'])->name('buat');
        Route::post('/', [ArtikelController::class, 'store'])->name('simpan');
        Route::get('{artikel}/ubah', [ArtikelController::class, 'edit'])->name('ubah');
        Route::put('{artikel}', [ArtikelController::class, 'update'])->name('perbarui');
        Route::delete('{artikel}', [ArtikelController::class, 'destroy'])->name('hapus');
        Route::patch('{id}/pulihkan', [ArtikelController::class, 'pulihkan'])->name('pulihkan');
    });
    Route::prefix('ulasan')->name('ulasan.')->group(function (): void {
        Route::get('/', [UlasanController::class, 'index'])->name('indeks');
        Route::patch('{ulasan}/setujui', [UlasanController::class, 'setujui'])->name('setujui');
        Route::post('{ulasan}/video', [UlasanController::class, 'unggahVideo'])->name('video.unggah');
        Route::delete('{ulasan}/video', [UlasanController::class, 'hapusVideo'])->name('video.hapus');
        Route::delete('{ulasan}', [UlasanController::class, 'tolak'])->name('tolak');
        Route::patch('{id}/pulihkan', [UlasanController::class, 'pulihkan'])->name('pulihkan');
    });
    Route::prefix('pengaturan')->name('pengaturan.')->group(function (): void {
        Route::get('/', [PengaturanController::class, 'index'])->name('indeks');
        Route::put('/', [PengaturanController::class, 'update'])->name('perbarui');
    });

    Route::get('profil', [ProfilController::class, 'edit'])->name('profil.ubah');
    Route::put('profil', [ProfilController::class, 'update'])->name('profil.perbarui');
});
