<?php

namespace App\Layanan;

use App\Enums\StatusPesananKustom;
use App\Models\PermintaanPesanan;
use App\Models\PertanyaanTamu;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

/** Menyiapkan notifikasi database admin untuk lonceng di header dashboard. */
class NotifikasiAdminLayanan
{
    /**
     * @return array{jumlah_belum_dibaca: int, jumlah_permintaan_baru: int, jumlah_pertanyaan_baru: int, jumlah_kustom_baru: int, daftar: list<array<string, mixed>>}
     */
    public function ringkasan(User $admin, int $batas = 8): array
    {
        return [
            'jumlah_belum_dibaca' => $admin->unreadNotifications()->count(),
            'jumlah_permintaan_baru' => $this->jumlahPermintaanBaru(),
            'jumlah_pertanyaan_baru' => $this->jumlahPertanyaanBaru(),
            'jumlah_kustom_baru' => $this->jumlahKustomBaru(),
            'daftar' => $admin->notifications()
                ->latest()
                ->take($batas)
                ->get()
                ->map(fn (DatabaseNotification $notifikasi): array => $this->format($notifikasi))
                ->values()
                ->all(),
        ];
    }

    /** Permintaan Form yang menunggu ditinjau admin (angka pada menu Permintaan Pesanan). */
    public function jumlahPermintaanBaru(): int
    {
        return PermintaanPesanan::query()->where('status', 'menunggu_tinjauan')->count();
    }

    /** Pertanyaan tamu yang belum ditangani admin (angka pada menu Pertanyaan Tamu). */
    public function jumlahPertanyaanBaru(): int
    {
        return PertanyaanTamu::query()->where('status', 'baru')->count();
    }

    /** Permintaan trip kustom yang belum ditangani admin (angka pada menu Pesanan Kustom). */
    public function jumlahKustomBaru(): int
    {
        return PesananKustom::query()->where('status', StatusPesananKustom::Baru)->count();
    }

    /** Halaman yang dibuka setelah notifikasi ditandai dibaca. */
    public function tautan(DatabaseNotification $notifikasi): string
    {
        if ($notifikasi->data['permintaan_pesanan_id'] ?? null) {
            return route('admin.permintaan-pesanan.indeks');
        }

        if ($notifikasi->data['pertanyaan_tamu_id'] ?? null) {
            return route('admin.pertanyaan-tamu.indeks');
        }

        if ($notifikasi->data['pesanan_kustom_id'] ?? null) {
            return route('admin.pesanan-kustom.detail', $notifikasi->data['pesanan_kustom_id']);
        }

        $pesanan = ($notifikasi->data['pesanan_id'] ?? null)
            ? Pesanan::query()->find($notifikasi->data['pesanan_id'])
            : null;

        return $pesanan
            ? route('admin.pesanan.detail', $pesanan)
            : route('admin.dashboard.indeks');
    }

    /** @return array<string, mixed> */
    public function format(DatabaseNotification $notifikasi): array
    {
        $data = $notifikasi->data;
        $jenis = $data['jenis'] ?? '';
        $waktu = Carbon::parse($data['waktu'] ?? $notifikasi->created_at)->timezone(config('app.timezone'));

        return [
            'id' => $notifikasi->id,
            'jenis' => $this->jenisTampilan($jenis, $data),
            'label' => match ($jenis) {
                'permintaan_pesanan_masuk' => 'Permintaan',
                'pertanyaan_tamu_masuk' => 'Pertanyaan',
                'pesanan_kustom_masuk' => 'Kustom',
                default => ucfirst($data['peran'] ?? 'Personel'),
            },
            'judul' => $data['judul'] ?? ($jenis === 'trip_selesai' ? 'Trip selesai' : 'Pembaruan penugasan'),
            'baris' => $jenis === 'trip_selesai' && isset($data['kode_pesanan'])
                ? $this->barisTripSelesai($data)
                : [$data['pesan'] ?? 'Ada pembaruan penugasan.'],
            'waktu' => $waktu->format('d M, H:i'),
            'dibaca' => $notifikasi->read_at !== null,
            'url_baca' => route('admin.notifikasi.baca', $notifikasi->id),
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function jenisTampilan(string $jenis, array $data): string
    {
        return match ($jenis) {
            'permintaan_pesanan_masuk' => 'permintaan',
            'pertanyaan_tamu_masuk' => 'pertanyaan',
            'pesanan_kustom_masuk' => 'kustom',
            default => $data['peran'] ?? 'personel',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function barisTripSelesai(array $data): array
    {
        $tanggal = isset($data['tanggal_trip']) ? Carbon::parse($data['tanggal_trip'])->format('d M Y') : '';

        return [
            ($data['nama_personel'] ?? 'Personel').' menyelesaikan '.$data['kode_pesanan'].' · '.($data['nama_perjalanan'] ?? 'Perjalanan'),
            ($data['nama_rombongan'] ?? 'Rombongan').(isset($data['jumlah_wisatawan']) ? ' · '.$data['jumlah_wisatawan'].' orang' : ''),
            $tanggal.(! empty($data['waktu_jemput']) ? ' · Jemput '.$data['waktu_jemput'] : '').(! empty($data['lokasi_jemput']) ? ' · '.$data['lokasi_jemput'] : ''),
        ];
    }
}
