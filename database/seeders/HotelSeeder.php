<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Perjalanan;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    /**
     * Hotel mitra bintang 4+ per destinasi; hotel yang sumbernya bertentangan disimpan nonaktif sampai dicek admin.
     * Aman dijalankan ulang: hanya membuat hotel baru, tidak menimpa hasil edit admin atau menghidupkan hotel yang dihapus.
     */
    public function run(): void
    {
        $this->singkirkanHotelLama();

        $perjalananKeHotel = [];

        foreach ($this->daftarHotel() as $namaJourney => $daftar) {
            foreach ($daftar as $isi) {
                // Hanya membuat hotel yang belum ada (termasuk yang sudah dihapus admin): seed ulang tidak boleh
                // menimpa nomor WhatsApp, tarif, status aktif, atau tipe kamar yang sudah diubah admin.
                if (Hotel::query()->withTrashed()->where('nama', $isi['nama'])->exists()) {
                    continue;
                }

                $hotel = Hotel::query()->create([
                    'nama' => $isi['nama'],
                    'bintang' => $isi['bintang'] ?? Hotel::BINTANG_STANDAR,
                    'area' => $isi['area'],
                    'alamat' => $isi['alamat'],
                    'deskripsi' => $isi['deskripsi'],
                    'jarak_destinasi' => $isi['jarak'],
                    'tautan_peta' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($isi['nama'].' '.$isi['alamat']),
                    'whatsapp' => config('mistborne.kontak_uji.whatsapp'),
                    'tarif_mulai' => $isi['tarif_mulai'] ?? null,
                    'tarif_sampai' => $isi['tarif_sampai'] ?? null,
                    'kapasitas_kamar' => 2,
                    'tersedia_extra_bed' => false,
                    'tipe_kamar' => $isi['tipe_kamar'],
                    'fasilitas' => $isi['fasilitas'],
                    'jam_check_in' => '14:00',
                    'jam_check_out' => '12:00',
                    'catatan' => $isi['catatan'],
                    'aktif' => $isi['aktif'],
                    'sorotan' => $isi['sorotan'] ?? false,
                ]);

                $perjalananKeHotel[$namaJourney][] = $hotel->id;
                $this->lepasHotelLainDariJourney($namaJourney, $hotel);
            }
        }

        foreach ($perjalananKeHotel as $namaJourney => $idHotel) {
            Perjalanan::query()->where('nama', $namaJourney)->first()?->hotel()->syncWithoutDetaching($idHotel);
        }

        if ($perjalananKeHotel !== []) {
            $this->tautkanPaketWisata();
        }

        $this->pasangFotoAwal();

        Hotel::query()->each(fn (Hotel $hotel) => $hotel->sinkronkanKapasitasDenganJourney());
    }

    /**
     * @return array<string, list<array<string, mixed>>> nama journey => hotel
     */
    private function daftarHotel(): array
    {
        $lengkap = ['sarapan', 'ac', 'wifi', 'parkir', 'restoran'];

        return [
            'DIENG HIGHLAND ESCAPE' => [
                [
                    'nama' => 'Artotel Wanasaba', 'tarif_mulai' => 688_000, 'area' => 'Kota Wonosobo', 'aktif' => true, 'sorotan' => true,
                    'alamat' => 'Jl. A. Yani No. 112, Wonosobo, Jawa Tengah',
                    'jarak' => '1,5 jam berkendara ke dataran tinggi Dieng',
                    'deskripsi' => 'Hotel butik bintang 4 pertama di Wonosobo dengan desain modern dan karya seni di setiap sudut, kolam renang air hangat di rooftop, dan restoran serta lounge di lantai atas.',
                    'tipe_kamar' => [['nama' => 'Deluxe', 'kapasitas' => 2], ['nama' => 'Deluxe Balcony', 'kapasitas' => 2], ['nama' => 'Grand Suite', 'kapasitas' => 4]],
                    'fasilitas' => [...$lengkap, 'air_panas', 'kolam_renang', 'kafe_bar', 'ruang_pertemuan'],
                    'catatan' => 'Bintang 4: diumumkan Artotel Group (Kontan, Fortune IDN) dan disebut hotel bintang 4 pertama di Wonosobo. Mulai beroperasi 14 Oktober 2026. Tipe kamar sesuai pengumuman (Deluxe 26 m², Deluxe Balcony 28 m², Grand Suite 46 m²); kapasitas per tipe perkiraan.',
                ],
            ],
            'IJEN VOLCANIC ESCAPE' => [
                [
                    'nama' => 'Aston Banyuwangi Hotel & Conference Center', 'tarif_mulai' => 638_400, 'area' => 'Kota Banyuwangi', 'aktif' => true, 'sorotan' => true,
                    'alamat' => 'Jl. Brawijaya, Lingkungan Cuking, Mojopanggung, Giri, Banyuwangi, Jawa Timur 68425',
                    'jarak' => '1,5 jam berkendara ke Paltuding (pos pendakian Ijen)',
                    'deskripsi' => 'Hotel bintang 4 di Banyuwangi dengan 163 kamar, kolam renang, spa, dan ruang pertemuan; basis nyaman sebelum dan sesudah pendakian Ijen.',
                    'tipe_kamar' => [['nama' => 'Deluxe', 'kapasitas' => 2], ['nama' => 'Premier Room', 'kapasitas' => 2], ['nama' => 'Junior Suite', 'kapasitas' => 3], ['nama' => 'Family Room', 'kapasitas' => 4]],
                    'fasilitas' => [...$lengkap, 'kolam_renang', 'spa', 'ruang_pertemuan'],
                    'catatan' => 'Bintang 4: dicantumkan sebagai hotel bintang 4 di tiket.com, Trip.com, dan ulasan pemesanan; alamat dari daftar Orami. Tipe kamar dan kapasitas per tipe perkiraan.',
                ],
            ],
            'CIWIDEY & RANCABALI ESCAPE' => [
                [
                    'nama' => 'Forest Hills Ciwidey', 'tarif_mulai' => 650_000, 'area' => 'Ciwidey, Bandung Selatan', 'aktif' => true, 'sorotan' => true,
                    'alamat' => 'Jl. Raya Soreang–Ciwidey Km 23, Soreang, Kabupaten Bandung, Jawa Barat 40911',
                    'jarak' => '45 menit berkendara ke Kawah Putih; 1 jam ke Situ Patenggang dan Rancabali',
                    'deskripsi' => 'Hotel bintang 4 di kawasan Ciwidey–Soreang dengan suasana pegunungan dan skor tamu tinggi di Trip.com, cocok sebagai basis jelajah Ciwidey dan Rancabali.',
                    'tipe_kamar' => [['nama' => 'Deluxe Twin', 'kapasitas' => 2], ['nama' => 'Deluxe Queen', 'kapasitas' => 2], ['nama' => 'Deluxe Executive Mountain View', 'kapasitas' => 3], ['nama' => 'Executive Balcony Pool View', 'kapasitas' => 3], ['nama' => 'Family Room', 'kapasitas' => 4]],
                    'fasilitas' => $lengkap,
                    'catatan' => 'Bintang 4: Destinia dan Trip.com (skor tamu 9,6). Alamat berbeda tipis antar sumber (Destinia: Km 23 Soreang–Ciwidey; Trip.com: Jl. Propinsi, Sukajadi); cek di Google Maps. Tipe kamar perkiraan.',
                ],
            ],
            'GREEN CANYON JOURNEY' => [
                [
                    'nama' => 'Menara Laut Hotel', 'area' => 'Pangandaran', 'aktif' => true, 'sorotan' => true, 'tarif_mulai' => 730000,
                    'alamat' => 'Jl. Pamugaran Bulak Laut, Pananjung, Pangandaran, Jawa Barat 46396',
                    'jarak' => '45 menit berkendara ke Green Canyon; beberapa langkah dari Pantai Pangandaran',
                    'deskripsi' => 'Hotel bintang 4 tepi pantai Pangandaran dengan kolam renang outdoor, restoran, kafe, dan resepsionis 24 jam.',
                    'tipe_kamar' => [['nama' => 'Deluxe', 'kapasitas' => 2], ['nama' => 'Family Room', 'kapasitas' => 4]],
                    'fasilitas' => [...$lengkap, 'kolam_renang', 'kafe_bar', 'room_service', 'resepsionis_24'],
                    'catatan' => 'Bintang 4: JawaPos dan Trip.com. Tarif mulai dari JawaPos (belum tentu terbaru). Tipe kamar perkiraan.',
                ],
            ],
            'PAPANDAYAN CRATER JOURNEY' => [
                [
                    'nama' => 'Hotel Santika Premiere Garut', 'tarif_mulai' => 650_000, 'tarif_sampai' => 730_000, 'area' => 'Cipanas, Garut', 'aktif' => true, 'sorotan' => true,
                    'alamat' => 'Jl. Raya Cipanas Baru, Tarogong Kaler, Garut, Jawa Barat 44151',
                    'jarak' => '1 jam berkendara ke kaki Gunung Papandayan',
                    'deskripsi' => 'Hotel bintang 4 dengan 102 kamar, pemandangan gunung dan sawah, kolam renang, spa, pusat kebugaran, dan room service 24 jam.',
                    'tipe_kamar' => [['nama' => 'Deluxe', 'kapasitas' => 2], ['nama' => 'Pool View', 'kapasitas' => 2], ['nama' => 'Premiere Room', 'kapasitas' => 3], ['nama' => 'Suite', 'kapasitas' => 3], ['nama' => 'Family Room', 'kapasitas' => 4]],
                    'fasilitas' => [...$lengkap, 'kolam_renang', 'spa', 'pusat_kebugaran', 'room_service', 'resepsionis_24', 'ruang_pertemuan', 'lift'],
                    'catatan' => 'Bintang 4: Expedia (4,0), Tripadvisor, dan JawaPos. Kapasitas per tipe perkiraan.',
                ],
            ],
            'LEMBANG & TANGKUBAN PARAHU JOURNEY' => [
                [
                    'nama' => 'The Lodge Maribaya', 'tarif_mulai' => 800_000, 'tarif_sampai' => 3_000_000, 'area' => 'Lembang, Bandung Barat', 'aktif' => true, 'sorotan' => true,
                    'alamat' => 'Jl. Maribaya No. 149, Cibodas, Lembang, Kabupaten Bandung Barat, Jawa Barat 40135',
                    'jarak' => '40 menit berkendara ke Tangkuban Parahu dan kebun bunga Lembang',
                    'deskripsi' => 'Hotel bintang 4 bernuansa alam di Lembang dengan udara sejuk dan pemandangan perbukitan, cocok untuk beristirahat setelah menjelajah kawah dan kebun bunga.',
                    'tipe_kamar' => [['nama' => 'Deluxe Room', 'kapasitas' => 2], ['nama' => 'Tree House', 'kapasitas' => 2], ['nama' => 'Family Room', 'kapasitas' => 4]],
                    'fasilitas' => $lengkap,
                    'catatan' => 'Bintang 4: Trip.com dan daftar 4 bintang Tripadvisor untuk Lembang. Alamat dari listing Trip.com. Tipe kamar dan kapasitas perkiraan.',
                ],
            ],
            'BROMO SUNRISE JOURNEY' => [
                [
                    'nama' => 'The Singhasari Resort', 'bintang' => 5, 'area' => 'Kota Batu, Malang', 'aktif' => true, 'sorotan' => true,
                    'alamat' => 'Jl. Ir. Soekarno No. 120, Beji, Batu, Jawa Timur 65236',
                    'jarak' => '15 menit berkendara ke Museum Angkut dan Alun-Alun Kota Batu',
                    'deskripsi' => 'Resor bintang 5 di Kota Batu dengan desain yang terinspirasi budaya kerajaan Nusantara, kolam renang luar ruang dengan perosotan air, spa, dan restoran rooftop berpemandangan kota. Basis nyaman untuk malam pertama sebelum menuju kawasan Bromo.',
                    'tipe_kamar' => [['nama' => 'Deluxe Room', 'kapasitas' => 2], ['nama' => 'Premier Room', 'kapasitas' => 2], ['nama' => 'Pool Access Room', 'kapasitas' => 2], ['nama' => 'Suite Room', 'kapasitas' => 2], ['nama' => 'Thematic Family Room', 'kapasitas' => 4]],
                    'fasilitas' => [...$lengkap, 'kolam_renang', 'spa', 'kafe_bar', 'ruang_pertemuan'],
                    'catatan' => 'Bintang 5: listing Travelocity/Hotels.com dan TIMES Indonesia (resor bintang 5 pertama di Jawa Timur). Alamat dan nama tipe kamar dari situs resmi thesinghasari.com; kapasitas per tipe, tarif, dan estimasi jarak belum terverifikasi: cek ke hotel lalu lengkapi di menu Hotel. Foto dari situs resmi hotel.',
                ],
            ],
            'Telaga Sarangan & Cemoro Sewu' => [
                [
                    'nama' => 'Aston Madiun Hotel & Conference Center', 'area' => 'Kota Madiun', 'aktif' => true, 'sorotan' => true,
                    'alamat' => 'Jl. Mayjen Sungkono No. 41, Nambangan Kidul, Manguharjo, Kota Madiun, Jawa Timur 63128',
                    'jarak' => '1 jam berkendara ke Telaga Sarangan',
                    'deskripsi' => 'Hotel bintang 4 di Kota Madiun dengan kolam renang, pusat kebugaran, restoran, dan ballroom. Basis yang nyaman dan ramah keluarga untuk menjelajah Telaga Sarangan dan Cemoro Sewu di kaki Gunung Lawu.',
                    'tipe_kamar' => [['nama' => 'Superior Room', 'kapasitas' => 2], ['nama' => 'Deluxe Room', 'kapasitas' => 2], ['nama' => 'Suite', 'kapasitas' => 3]],
                    'fasilitas' => [...$lengkap, 'kolam_renang', 'pusat_kebugaran', 'ruang_pertemuan'],
                    'catatan' => 'Bintang 4: listing database properti dan jaringan Aston. Dipilih karena hotel bintang 4 terdekat yang ramah keluarga (Allura Azana Tawangmangu khusus dewasa). Jarak ke Sarangan perkiraan (Madiun–Magetan–Sarangan). Tipe kamar, kapasitas, dan tarif belum terverifikasi; foto belum ada: unggah foto resmi di menu Hotel.',
                ],
            ],
            'BOROBUDUR & MERAPI SUNRISE JOURNEY' => [
                [
                    'nama' => 'Atria Hotel Magelang', 'tarif_mulai' => 580_000, 'tarif_sampai' => 704_000, 'area' => 'Kota Magelang', 'aktif' => true, 'sorotan' => true,
                    'alamat' => 'Jl. Jenderal Sudirman No. 42, Magelang, Jawa Tengah',
                    'jarak' => '40 menit berkendara ke Candi Borobudur',
                    'deskripsi' => 'Hotel bintang 4 di pusat kota Magelang dengan fasilitas konferensi, nyaman sebagai basis sebelum sunrise Borobudur.',
                    'tipe_kamar' => [['nama' => 'Superior', 'kapasitas' => 2], ['nama' => 'Deluxe', 'kapasitas' => 2], ['nama' => 'Executive Suite', 'kapasitas' => 2], ['nama' => 'Family Room', 'kapasitas' => 4]],
                    'fasilitas' => [...$lengkap, 'ruang_pertemuan'],
                    'catatan' => 'Bintang 4: Expedia, Parador Hotels, dan daftar hotel Magelang UNTIDAR. Kode pos berbeda antar sumber (56125/56126); cek di Google Maps. Tipe kamar perkiraan.',
                ],
            ],
        ];
    }

    /**
     * Hotel pada daftar awal yang dulu otomatis diberi bintang 4 tanpa dicek. Yang sudah terpakai pesanan hanya dinonaktifkan
     * agar riwayat tetap utuh; yang belum terpakai dihapus.
     */
    private function singkirkanHotelLama(): void
    {
        $lama = [
            'Zaada Villa Dieng by Travelink', 'Eagle Hotel Wonosobo', 'Front One Harvest Hotel Wonosobo', 'Bromo Permai 1', 'Hotel Sawah Ijen',
            'Hotel Pesona Ciwidey', 'Abang Hotel Ciwidey', 'Horison Palma Pangandaran', 'The Arnawa Hotel', 'Laut Biru Resort',
            'Bukit Alamanda Resort & Resto', 'Danau Dariza Hotel & Resort', 'Aleyra Hotel and Villa Garut',
            'Kresna Hotel Wonosobo', 'Lereng Bromo Hotel', 'Hotel Batu Wonderland Malang',
            'Mercure Garut City Center', 'Grand Sunshine Resort & Convention', 'Puteri Gunung Hotel',
        ];

        Hotel::query()->whereIn('nama', $lama)->each(function (Hotel $hotel): void {
            if ($hotel->pesanan()->exists()) {
                $hotel->update(['aktif' => false, 'catatan' => 'NONAKTIF — bintang 4 belum terverifikasi (data awal). Dipertahankan karena sudah dipakai pesanan.']);

                return;
            }

            $hotel->perjalanan()->detach();
            $hotel->forceDelete();
        });
    }

    /**
     * Mengisi foto HD bawaan (public/images/hotel) hanya bila foto masih kosong atau foto bawaan lama; unggahan admin tidak ditimpa.
     */
    private function pasangFotoAwal(): void
    {
        $fotoAwal = [
            'Atria Hotel Magelang' => '/images/Atria Hotel Magelang.jpg',
            'Artotel Wanasaba' => '/images/hotel/artotel-wanasaba-utama.webp',
            'Aston Banyuwangi Hotel & Conference Center' => '/images/hotel/aston-banyuwangi-utama.webp',
            'Aston Madiun Hotel & Conference Center' => '/images/hotel/aston-madiun-utama.webp',
            'Forest Hills Ciwidey' => '/images/hotel/forest-hills-ciwidey-utama.webp',
            'Hotel Santika Premiere Garut' => '/images/hotel/santika-garut-utama.webp',
            'Menara Laut Hotel' => '/images/hotel/menara-laut-utama.webp',
            'The Lodge Maribaya' => '/images/hotel/the-lodge-maribaya-utama.webp',
            'Plataran Bromo' => '/images/hotel/plataran-bromo-utama.webp',
            'The Singhasari Resort' => '/images/hotel/singhasari-resort-utama.webp',
        ];
        $fotoLama = ['/images/singhasari-resort-kolam.jpg'];
        $galeriAwal = [
            'Artotel Wanasaba' => [
                ['foto' => '/images/hotel/artotel-wanasaba-kamar-1.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/artotel-wanasaba-kamar-2.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/artotel-wanasaba-restoran-1.webp', 'label' => 'restoran'],
                ['foto' => '/images/hotel/artotel-wanasaba-pemandangan-1.webp', 'label' => 'pemandangan'],
                ['foto' => '/images/hotel/artotel-wanasaba-fasilitas-1.webp', 'label' => 'fasilitas'],
            ],
            'Aston Banyuwangi Hotel & Conference Center' => [
                ['foto' => '/images/hotel/aston-banyuwangi-kolam-renang-1.webp', 'label' => 'kolam_renang'],
                ['foto' => '/images/hotel/aston-banyuwangi-kamar-1.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/aston-banyuwangi-kamar-2.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/aston-banyuwangi-lobi-1.webp', 'label' => 'lobi'],
                ['foto' => '/images/hotel/aston-banyuwangi-restoran-1.webp', 'label' => 'restoran'],
                ['foto' => '/images/hotel/aston-banyuwangi-eksterior-1.webp', 'label' => 'eksterior'],
            ],
            'Aston Madiun Hotel & Conference Center' => [
                ['foto' => '/images/hotel/aston-madiun-eksterior-1.webp', 'label' => 'eksterior'],
                ['foto' => '/images/hotel/aston-madiun-kamar-1.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/aston-madiun-kamar-2.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/aston-madiun-kamar-3.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/aston-madiun-restoran-1.webp', 'label' => 'restoran'],
                ['foto' => '/images/hotel/aston-madiun-fasilitas-1.webp', 'label' => 'fasilitas'],
            ],
            'Atria Hotel Magelang' => [
                ['foto' => '/images/hotel/atria-magelang-eksterior-1.webp', 'label' => 'eksterior'],
                ['foto' => '/images/hotel/atria-magelang-kamar-1.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/atria-magelang-kamar-2.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/atria-magelang-kolam-renang-1.webp', 'label' => 'kolam_renang'],
                ['foto' => '/images/hotel/atria-magelang-restoran-1.webp', 'label' => 'restoran'],
            ],
            'Forest Hills Ciwidey' => [
                ['foto' => '/images/hotel/forest-hills-ciwidey-kolam-renang-1.webp', 'label' => 'kolam_renang'],
                ['foto' => '/images/hotel/forest-hills-ciwidey-kamar-1.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/forest-hills-ciwidey-kamar-2.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/forest-hills-ciwidey-kamar-3.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/forest-hills-ciwidey-restoran-1.webp', 'label' => 'restoran'],
            ],
            'Hotel Santika Premiere Garut' => [
                ['foto' => '/images/hotel/santika-garut-kolam-renang-1.webp', 'label' => 'kolam_renang'],
                ['foto' => '/images/hotel/santika-garut-kamar-1.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/santika-garut-kamar-2.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/santika-garut-kamar-3.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/santika-garut-restoran-1.webp', 'label' => 'restoran'],
                ['foto' => '/images/hotel/santika-garut-lobi-1.webp', 'label' => 'lobi'],
            ],
            'Menara Laut Hotel' => [
                ['foto' => '/images/hotel/menara-laut-kolam-renang-1.webp', 'label' => 'kolam_renang'],
                ['foto' => '/images/hotel/menara-laut-kamar-1.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/menara-laut-kamar-2.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/menara-laut-restoran-1.webp', 'label' => 'restoran'],
                ['foto' => '/images/hotel/menara-laut-lobi-1.webp', 'label' => 'lobi'],
                ['foto' => '/images/hotel/menara-laut-fasilitas-1.webp', 'label' => 'fasilitas'],
            ],
            'The Lodge Maribaya' => [
                ['foto' => '/images/hotel/the-lodge-maribaya-kamar-1.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/the-lodge-maribaya-kamar-2.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/the-lodge-maribaya-pemandangan-1.webp', 'label' => 'pemandangan'],
                ['foto' => '/images/hotel/the-lodge-maribaya-restoran-1.webp', 'label' => 'restoran'],
            ],
            'Plataran Bromo' => [
                ['foto' => '/images/hotel/plataran-bromo-kamar-1.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/plataran-bromo-kamar-2.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/plataran-bromo-kamar-3.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/plataran-bromo-restoran-1.webp', 'label' => 'restoran'],
                ['foto' => '/images/hotel/plataran-bromo-pemandangan-1.webp', 'label' => 'pemandangan'],
            ],
            'The Singhasari Resort' => [
                ['foto' => '/images/hotel/singhasari-resort-kolam-renang-1.webp', 'label' => 'kolam_renang'],
                ['foto' => '/images/hotel/singhasari-resort-kamar-1.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/singhasari-resort-kamar-2.webp', 'label' => 'kamar'],
                ['foto' => '/images/hotel/singhasari-resort-lobi-1.webp', 'label' => 'lobi'],
                ['foto' => '/images/hotel/singhasari-resort-lobi-2.webp', 'label' => 'lobi'],
                ['foto' => '/images/hotel/singhasari-resort-restoran-1.webp', 'label' => 'restoran'],
            ],
        ];

        foreach ($fotoAwal as $nama => $path) {
            Hotel::query()->where('nama', $nama)
                ->where(fn ($query) => $query->whereNull('foto')->orWhere('foto', '')->orWhereIn('foto', $fotoLama))
                ->update(['foto' => $path]);
        }

        foreach ($galeriAwal as $nama => $galeri) {
            Hotel::query()->where('nama', $nama)->get()->each(function (Hotel $hotel) use ($galeri): void {
                $lama = collect($hotel->galeri ?? []);
                $path = fn ($baris): string => (string) ($baris['foto'] ?? '');

                // Sudah pernah diisi galeri HD: hormati hasil edit admin setelahnya.
                if ($lama->contains(fn ($baris): bool => str_starts_with($path($baris), '/images/hotel/'))) {
                    return;
                }

                // Kosong atau hanya foto bawaan lama → ganti; berisi unggahan admin → tambahkan di belakangnya.
                $unggahanAdmin = $lama->reject(fn ($baris): bool => str_starts_with($path($baris), '/images/'))->values();
                $hotel->update(['galeri' => $unggahanAdmin->concat($galeri)->take(Hotel::MAKS_GALERI)->values()->all()]);
            });
        }
    }

    /**
     * Hotel baru untuk journey yang sudah punya hotel (mis. The Singhasari Resort menggantikan Plataran Bromo): hotel lama
     * dilepas dari journey itu karena satu journey hanya boleh punya satu hotel aktif. Data hotel lama tetap utuh.
     */
    private function lepasHotelLainDariJourney(string $namaJourney, Hotel $hotelBaru): void
    {
        $journey = Perjalanan::query()->where('nama', $namaJourney)->first();

        if ($journey) {
            $journey->hotel()->detach($journey->hotel()->whereKeyNot($hotelBaru->id)->pluck('hotel.id')->all());
        }
    }

    /** Paket Wisata memakai hotel di kawasan yang sama dengan journey sejenis, supaya tamu paket juga melihat hotelnya. */
    private function tautkanPaketWisata(): void
    {
        $paketKeJourney = [
            'Kawah Rengganis' => 'CIWIDEY & RANCABALI ESCAPE',
            'Bukit Sikunir' => 'DIENG HIGHLAND ESCAPE',
            'Hutan Pinus' => 'DIENG HIGHLAND ESCAPE',
        ];

        foreach ($paketKeJourney as $namaPaket => $namaJourney) {
            $paket = Perjalanan::query()->where('nama', $namaPaket)->first();
            $hotel = Perjalanan::query()->where('nama', $namaJourney)->first()?->hotel()->aktif()->orderByDesc('hotel.sorotan')->orderBy('hotel.id')->first();

            if ($paket && $hotel) {
                $hotel->perjalanan()->syncWithoutDetaching([$paket->id]);
            }
        }
    }
}
