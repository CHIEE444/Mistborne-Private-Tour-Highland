<?php

namespace Database\Seeders;

use App\Models\Perjalanan;
use Illuminate\Database\Seeder;

class PaketWisataSeeder extends Seeder
{
    /**
     * Paket Relaksasi Alam. Paket adalah Perjalanan berjenis "paket": punya halaman detail, itinerary, jadwal tanggal,
     * dan dipesan lewat alur booking yang sama dengan journey. Harga mengikuti biaya driver, guide, armada, dan BBM;
     * kapasitas dihitung untuk tamu saja (driver dan guide memakai 2 kursi). Aman dijalankan ulang (updateOrCreate per nama).
     */
    public function run(): void
    {
        $jemput = static fn (string $kota, string $tujuan): string => "Jemput di hotel atau titik jemput ({$kota}), lalu berangkat menuju {$tujuan}";
        $siang = 'Makan siang di warung lokal (dibayar sendiri)';
        $inap = 'Check-in penginapan dan istirahat';
        $malam = 'Makan malam dan bebas bersantai';
        $sarapan = 'Sarapan dan check-out dari penginapan';
        $antar = 'Antar kembali ke hotel atau titik lanjutan Anda';

        $paket = [
            [
                'nama' => 'Kawah Rengganis', 'region' => 'jawa-barat', 'tempat' => 'Ciwidey, Jawa Barat', 'chip' => 'Kawah & Telaga', 'harga' => 4_450_000,
                'tagline' => 'Kabut tebal menyelimuti kawah, hamparan pasir abu-abu, dan udara dingin pegunungan.',
                'label_unggulan' => 'PILIHAN UTAMA • EKSPEDISI PRIVAT 2H1M', 'img' => '/images/kawah-rengganis.webp',
                'deskripsi' => 'Jelajahi kawah vulkanik berkabut dengan hamparan pasir abu-abu dan aliran air yang berkelok. Didampingi pengemudi terlatih, nikmati udara sejuk dataran tinggi dan sarapan hangat sebelum turun.',
                'highlights' => ['Kawah vulkanik berkabut dengan pasir abu-abu', 'Aliran air berkelok di tengah kawah', 'Situ Patenggang dan kebun teh Rancabali', 'Sarapan hangat sebelum turun gunung'],
                'itinerary' => [
                    ['t' => 'Hari 1', 'x' => $jemput('Bandung', 'Ciwidey')],
                    ['t' => 'Hari 1', 'x' => 'Menjelajah Kawah Rengganis dan hamparan pasir abu-abu'],
                    ['t' => 'Hari 1', 'x' => 'Situ Patenggang dan kebun teh Rancabali'],
                    ['t' => 'Hari 2', 'x' => $antar],
                ],
                'praktis' => ['pickup' => 'Hotel di Bandung', 'start' => 'Fleksibel, sesuai permintaan tamu', 'tips' => 'Cuaca dataran tinggi berubah cepat: bawa jaket ringan dan payung.'],
            ],
            [
                'nama' => 'Bukit Sikunir', 'region' => 'jawa-tengah', 'tempat' => 'Dieng, Jawa Tengah', 'chip' => 'Sunrise', 'harga' => 5_050_000,
                'tagline' => 'Fajar menyingsing di atas siluet gunung dan kerlip lampu kota dari ketinggian.',
                'label_unggulan' => 'FAVORIT KELUARGA • SUNRISE DIENG', 'img' => '/images/bukitsikunir.jpg',
                'deskripsi' => 'Mulai perjalanan dini hari menuju puncak bukit, lalu saksikan langit berubah warna di atas siluet gunung dan lampu kota yang perlahan padam. Pengemudi lokal kami hafal jalurnya.',
                'highlights' => ['Golden sunrise di Bukit Sikunir', 'Kawah Sikidang dan Telaga Warna', 'Candi Arjuna berusia ribuan tahun', 'Kebun teh Tambi berhawa sejuk'],
                'itinerary' => [
                    ['t' => 'Hari 1', 'x' => $jemput('Wonosobo, Semarang, atau Yogyakarta', 'dataran tinggi Dieng')],
                    ['t' => 'Hari 1', 'x' => 'Kawah Sikidang, Telaga Warna, dan Telaga Pengilon'],
                    ['t' => 'Hari 1', 'x' => 'Candi Arjuna dan kompleks candi Dieng'],
                    ['t' => 'Hari 2', 'x' => 'Menikmati sunrise Sikunir, lalu sarapan hangat di penginapan'],
                    ['t' => 'Hari 2', 'x' => 'Kebun teh Tambi dan jalur kabut Kaligua'],
                    ['t' => 'Hari 2', 'x' => $antar],
                ],
                'praktis' => ['pickup' => 'Hotel di Wonosobo, Semarang, atau Yogyakarta', 'start' => 'Fleksibel, sesuai permintaan tamu', 'tips' => 'Bawa jaket tebal — suhu pagi di puncak bisa mencapai 4°C.'],
            ],
            [
                // Foto: "Telaga Sarangan dan Gunung Lawu - Maret 2026" oleh Ivoryenze, Wikimedia Commons, CC BY-SA 4.0.
                'nama' => 'Telaga Sarangan & Cemoro Sewu', 'region' => 'jawa-timur', 'tempat' => 'Magetan, Jawa Timur', 'chip' => 'Telaga & Pegunungan', 'harga' => 4_500_000,
                'tagline' => 'Telaga biru di kaki Gunung Lawu dan hutan cemara yang sejuk di jalur Cemoro Sewu.',
                'label_unggulan' => 'TELAGA KAKI LAWU • 2H1M ALL-IN', 'img' => '/images/telaga-sarangan-lawu.jpg',
                'deskripsi' => 'Nikmati Telaga Sarangan di ketinggian sekitar 1.200 mdpl dengan Gunung Lawu sebagai latar. Berkeliling telaga dengan perahu, singgah di Air Terjun Tirtosari, lalu keesokan paginya berjalan santai di hutan cemara Cemoro Sewu, pintu pendakian Gunung Lawu. Perjalanan santai tanpa pendakian berat.',
                'highlights' => ['Telaga Sarangan berlatar Gunung Lawu', 'Berkeliling telaga dengan perahu motor', 'Air Terjun Tirtosari di dekat telaga', 'Hutan cemara Cemoro Sewu yang sejuk'],
                'itinerary' => [
                    ['t' => 'Hari 1', 'x' => $jemput('Madiun, Magetan, atau Solo', 'Telaga Sarangan')],
                    ['t' => 'Hari 1', 'x' => 'Berkeliling Telaga Sarangan dengan perahu motor, berlatar Gunung Lawu'],
                    ['t' => 'Hari 1', 'x' => 'Air Terjun Tirtosari, beberapa menit dari tepi telaga'],
                    ['t' => 'Hari 1', 'x' => $inap],
                    ['t' => 'Hari 2', 'x' => 'Sarapan, lalu menuju Cemoro Sewu, pintu pendakian Gunung Lawu'],
                    ['t' => 'Hari 2', 'x' => 'Berjalan santai di hutan cemara Cemoro Sewu yang sejuk'],
                    ['t' => 'Hari 2', 'x' => $antar],
                ],
                'praktis' => ['pickup' => 'Hotel di Madiun, Magetan, atau Solo', 'start' => 'Fleksibel, sesuai permintaan tamu', 'tips' => 'Bawa jaket: pagi di Sarangan dan Cemoro Sewu terasa dingin. Naik perahu menyesuaikan cuaca dan antrean di dermaga.'],
            ],
            [
                'nama' => 'Hutan Pinus', 'region' => 'jawa-tengah', 'tempat' => 'Dataran Tinggi Jawa Tengah', 'chip' => 'Sehari Penuh', 'harga' => 4_550_000,
                'tagline' => 'Barisan pohon pinus menjulang, udara sejuk, dan jalan setapak yang tenang.',
                'label_unggulan' => 'PENJELAJAH • 2H1M ALL-IN', 'img' => '/images/hutan-pinus.webp',
                'deskripsi' => 'Habiskan hari berjalan santai di antara pohon pinus yang menjulang. Nikmati udara sejuk, spot foto, dan piknik di tengah hutan, lengkap dengan pengemudi yang siap mengantar ke titik terbaik.',
                'highlights' => ['Berjalan santai di antara pohon pinus', 'Spot foto dan piknik di tengah hutan', 'Udara sejuk jauh dari keramaian', 'Pengemudi siap mengantar ke titik terbaik'],
                'itinerary' => [
                    ['t' => 'Hari 1', 'x' => 'Jemput di hotel atau titik jemput, lalu berangkat menuju hutan pinus'],
                    ['t' => 'Hari 1', 'x' => 'Berjalan santai di antara pohon pinus dan spot foto'],
                    ['t' => 'Hari 1', 'x' => 'Piknik makan siang di tengah hutan (dibayar sendiri)'],
                    ['t' => 'Hari 2', 'x' => 'Menikmati udara pagi di hutan pinus'],
                    ['t' => 'Hari 2', 'x' => $antar],
                ],
                'praktis' => ['pickup' => 'Hotel atau titik jemput di area wisata', 'start' => 'Fleksibel, sesuai permintaan tamu', 'tips' => 'Pakai sepatu nyaman dan bawa jaket ringan; tanah hutan bisa licin setelah hujan.'],
            ],
        ];

        foreach ($paket as $urutan => $data) {
            $praktis = [...$data['praktis'], 'capacity' => 'Maks. 4 tamu per booking, di luar pemandu yang merangkap sopir'];
            unset($data['praktis']);

            $perjalanan = Perjalanan::query()->withTrashed()->where('jenis', Perjalanan::JENIS_PAKET)->where('nama', $data['nama'])->first() ?? new Perjalanan;

            if (! $perjalanan->exists) {
                $perjalanan->fill([
                    ...$data,
                    'jenis' => Perjalanan::JENIS_PAKET,
                    'durasi_hari' => 2,
                    'maks_orang' => 4,
                    'rating_label' => '4.95',
                    'praktis' => $praktis,
                    'tone' => 'paket',
                    'urutan' => $urutan + 1,
                    'tampil_beranda' => true,
                ])->save();
            }

            $this->perbaruiNilaiSeedLama($perjalanan, $data);
            $this->buatJadwal($perjalanan);
        }

        $this->pensiunkanPaket(['Kawah Ijen']);
    }

    /**
     * Harga dan foto paket diperbarui hanya bila masih sama dengan nilai seed lama, supaya hasil edit admin tidak tertimpa.
     * Harga baru dihitung dari lembar biaya (biaya pokok + keuntungan ±10%, maksimal Rp500.000 di atas biaya pokok).
     *
     * @param  array<string, mixed>  $data
     */
    private function perbaruiNilaiSeedLama(Perjalanan $paket, array $data): void
    {
        $hargaLama = ['Kawah Rengganis' => 4_400_000, 'Bukit Sikunir' => 4_650_000, 'Telaga Sarangan & Cemoro Sewu' => 4_550_000, 'Hutan Pinus' => 4_300_000];
        $fotoLama = ['/images/kawahrengganis.jpg', '/images/hutanpinus.jpg'];
        $perubahan = [];

        if (isset($hargaLama[$paket->nama]) && (int) $paket->harga === $hargaLama[$paket->nama]) {
            $perubahan['harga'] = $data['harga'];
        }

        if (in_array($paket->img, $fotoLama, true)) {
            $perubahan['img'] = $data['img'];
        }

        if ($paket->exists && $perubahan !== []) {
            $paket->update($perubahan);
        }
    }

    /**
     * Paket yang sudah diganti (Kawah Ijen → Telaga Sarangan & Cemoro Sewu, karena tumpang tindih dengan Ijen Volcanic
     * Escape). Paket yang sudah punya pesanan hanya disembunyikan agar riwayat utuh; yang belum dipakai dihapus (soft delete,
     * masih bisa dipulihkan admin).
     *
     * @param  list<string>  $nama
     */
    private function pensiunkanPaket(array $nama): void
    {
        Perjalanan::query()->where('jenis', Perjalanan::JENIS_PAKET)->whereIn('nama', $nama)->each(function (Perjalanan $paket): void {
            $paket->pesanan()->exists() ? $paket->update(['tampil_beranda' => false]) : $paket->delete();
        });
    }

    /** Tanggal contoh ±60 hari ke depan agar paket langsung bisa dipesan; admin mengatur ulang di menu Tanggal Tersedia. */
    private function buatJadwal(Perjalanan $perjalanan): void
    {
        if ($perjalanan->trashed() || $perjalanan->jadwal()->exists()) {
            return;
        }

        foreach ([8, 15, 22, 29, 36, 43, 50, 57] as $tambahHari) {
            $perjalanan->jadwal()->updateOrCreate(['tanggal' => now()->addDays($tambahHari)->toDateString()], ['kuota' => null, 'diblok' => false]);
        }
    }
}
