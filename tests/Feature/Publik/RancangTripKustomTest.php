<?php

namespace Tests\Feature\Publik;

use App\Models\Pengaturan;
use App\Models\PesananKustom;
use Tests\TestCase;

class RancangTripKustomTest extends TestCase
{
    public function test_halaman_bisa_diakses(): void
    {
        $this->get(route('rancang-trip-kustom'))->assertOk();
    }

    public function test_submit_valid_menyimpan_dan_kembali_ke_halaman_dengan_tautan_wa(): void
    {
        $response = $this->post(route('rancang-trip-kustom.simpan'), [
            'nama' => 'Budi Santoso',
            'whatsapp' => '081234567890',
            'destinasi_diinginkan' => 'Dieng & Bromo',
            'tanggal_diinginkan' => now()->addDays(10)->toDateString(),
            'durasi_hari' => 3,
            'lokasi_penjemputan' => 'Wonosobo',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
            'jumlah_orang' => 4,
            'catatan' => 'Mohon pemandu bahasa Inggris',
        ]);

        // Tidak redirect()->away() langsung — kembali ke halaman yang sama (tetap jadi "home
        // base" tamu), dengan tautan WA dibawa lewat session flash untuk dibuka di tab baru.
        $response->assertRedirect(route('rancang-trip-kustom'));
        $response->assertSessionHas('sukses', true);
        // Pesan WA selalu ditujukan ke nomor ADMIN (bukan nomor pengisi form).
        $this->assertStringContainsString('wa.me/'.Pengaturan::current()->nomor_whatsapp_admin, session('tautanWa'));
        $this->assertStringContainsString('Budi%20Santoso', session('tautanWa'));
        // Isi draft ikut dibawa agar tamu bisa membacanya sebelum mengirim lewat WhatsApp.
        $this->assertStringContainsString('Budi Santoso', session('pesanWa'));

        $kustom = PesananKustom::where('nama', 'Budi Santoso')->first();
        $this->assertNotNull($kustom);
        $this->assertSame('Dieng & Bromo', $kustom->destinasi_diinginkan);
        $this->assertSame(4, $kustom->jumlah_orang);
        $this->assertSame('baru', $kustom->status->value);
    }

    public function test_halaman_sukses_menampilkan_draft_chat_dulu_sebelum_menyatakan_data_terkirim(): void
    {
        $response = $this->withSession([
            'sukses' => true,
            'tautanWa' => 'https://wa.me/6281808606086?text=halo',
            'pesanWa' => 'Halo Admin MISTBORNE, saya ingin merancang trip kustom.',
        ])->get(route('rancang-trip-kustom'));

        $response->assertOk()
            ->assertSeeInOrder([
                'Satu langkah lagi: kirim draft ke admin',
                'belum sampai ke admin',
                'Halo Admin MISTBORNE, saya ingin merancang trip kustom.',
                'Kirim lewat WhatsApp',
                'Sudah menekan kirim di WhatsApp?',
                'Data pesanan Anda sudah dikirim',
            ])
            ->assertSee('Permintaan Anda tersimpan. Langkah terakhir: kirim draft chat ke admin.')
            ->assertSee('https://wa.me/6281808606086?text=halo', false)
            ->assertDontSee('window.open(', false);
    }

    public function test_whatsapp_dinormalisasi_ke_format_62(): void
    {
        $this->post(route('rancang-trip-kustom.simpan'), [
            'nama' => 'Siti',
            'whatsapp' => '081234567890',
            'destinasi_diinginkan' => 'Ijen',
            'tanggal_fleksibel' => 1,
            'durasi_hari' => 2,
            'lokasi_penjemputan' => 'Banyuwangi',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
            'jumlah_orang' => 2,
        ]);

        $this->assertSame('6281234567890', PesananKustom::where('nama', 'Siti')->first()->whatsapp);
    }

    public function test_validasi_field_wajib(): void
    {
        $response = $this->post(route('rancang-trip-kustom.simpan'), []);

        $response->assertSessionHasErrors(['nama', 'whatsapp', 'destinasi_diinginkan', 'tanggal_diinginkan', 'durasi_hari', 'lokasi_penjemputan', 'jumlah_orang']);
        $this->assertSame(0, PesananKustom::count());
    }

    public function test_honeypot_terisi_ditolak_diam_diam(): void
    {
        $response = $this->post(route('rancang-trip-kustom.simpan'), [
            'nama' => 'Bot',
            'whatsapp' => '081234567890',
            'destinasi_diinginkan' => 'Dieng',
            'tanggal_fleksibel' => 1,
            'durasi_hari' => 1,
            'lokasi_penjemputan' => 'Wonosobo',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
            'jumlah_orang' => 1,
            'website' => 'http://spam.example',
        ]);

        $response->assertRedirect(route('rancang-trip-kustom'));
        $this->assertSame(0, PesananKustom::count());
    }

    public function test_kartu_custom_highland_journey_menuju_form_kustom(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString(route('rancang-trip-kustom'), $html);
    }

    public function test_nomor_wa_admin_yang_diubah_di_pengaturan_dipakai_di_tautan_wa(): void
    {
        Pengaturan::current()->update(['nomor_whatsapp_admin' => '6289988877766']);

        $this->post(route('rancang-trip-kustom.simpan'), [
            'nama' => 'Dewi',
            'whatsapp' => '081234567890',
            'destinasi_diinginkan' => 'Kaligua',
            'tanggal_fleksibel' => 1,
            'durasi_hari' => 1,
            'lokasi_penjemputan' => 'Brebes',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
            'jumlah_orang' => 2,
        ]);

        $this->assertStringContainsString('wa.me/6289988877766', session('tautanWa'));
    }

    public function test_nomor_wa_admin_yang_diubah_di_pengaturan_tampil_di_beranda(): void
    {
        Pengaturan::current()->update(['nomor_whatsapp_admin' => '6289988877766']);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('6289988877766', $html);
    }

    public function test_tanggal_dikosongkan_saat_fleksibel_dicentang(): void
    {
        $this->post(route('rancang-trip-kustom.simpan'), [
            'nama' => 'Budi Santoso',
            'whatsapp' => '081234567890',
            'destinasi_diinginkan' => 'Bromo',
            'tanggal_fleksibel' => 1,
            'tanggal_diinginkan' => now()->addDays(10)->toDateString(),
            'durasi_hari' => 2,
            'lokasi_penjemputan' => 'Malang',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
            'jumlah_orang' => 2,
        ])->assertSessionHasNoErrors();

        $kustom = PesananKustom::where('nama', 'Budi Santoso')->firstOrFail();
        $this->assertNull($kustom->tanggal_diinginkan);
        $this->assertSame('Tanggal fleksibel', $kustom->jadwal_diinginkan);
    }

    public function test_tanggal_trip_kustom_minimal_h7_sama_seperti_pesanan_resmi(): void
    {
        $data = [
            'nama' => 'Budi Santoso',
            'whatsapp' => '081234567890',
            'destinasi_diinginkan' => 'Bromo',
            'durasi_hari' => 2,
            'lokasi_penjemputan' => 'Malang',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
            'jumlah_orang' => 2,
        ];

        $this->post(route('rancang-trip-kustom.simpan'), [...$data, 'tanggal_diinginkan' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors(['tanggal_diinginkan' => 'Tanggal perjalanan minimal 7 hari dari hari ini (H-7), sama seperti pesanan resmi, agar guide, mobil, dan pembayaran sempat disiapkan.']);
        $this->post(route('rancang-trip-kustom.simpan'), [...$data, 'tanggal_diinginkan' => now()->addDays(7)->toDateString()])
            ->assertSessionHasNoErrors();
    }

    public function test_format_tiap_field_divalidasi(): void
    {
        $this->post(route('rancang-trip-kustom.simpan'), [
            'nama' => 'A<b>',
            'whatsapp' => '12345',
            'destinasi_diinginkan' => 'X',
            'tanggal_diinginkan' => now()->addYears(2)->toDateString(),
            'durasi_hari' => 31,
            'lokasi_penjemputan' => '<>',
            'jumlah_orang' => 0,
        ])->assertSessionHasErrors(['nama', 'whatsapp', 'destinasi_diinginkan', 'tanggal_diinginkan', 'durasi_hari', 'lokasi_penjemputan', 'jumlah_orang']);
    }

    public function test_formulir_punya_pemeriksaan_sisi_klien_dan_mengosongkan_tanggal_saat_fleksibel(): void
    {
        $this->get(route('rancang-trip-kustom'))
            ->assertOk()
            ->assertSee('novalidate', false)
            ->assertSee('$refs.tanggal.value', false);
    }

    public function test_pesan_whatsapp_sopan_lengkap_dan_memakai_label_yang_mudah_dibaca(): void
    {
        $this->post(route('rancang-trip-kustom.simpan'), [
            'nama' => 'Dewi Lestari',
            'whatsapp' => '081234567890',
            'destinasi_diinginkan' => 'Rinjani',
            'tanggal_diinginkan' => '2026-12-24',
            'durasi_hari' => 7,
            'lokasi_penjemputan' => 'Hotel ASTON Imperial Bekasi',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
            'jumlah_orang' => 4,
            'minat_perjalanan' => ['kuliner', 'sejarah'],
            'tempo_perjalanan' => 'santai',
            'catatan' => 'Nuansanya santai saja',
        ]);

        $pesan = urldecode((string) session('tautanWa'));

        foreach (['Halo Tim MISTBORNE', 'saya *Dewi Lestari*', '• Destinasi: Rinjani', 'Kamis, 24 Desember 2026', '• Durasi: 7 hari', '• Jumlah peserta: 4 orang', 'Kuliner, Sejarah', 'Santai, banyak waktu menikmati tempat', 'Nuansanya santai saja', 'Terima kasih banyak'] as $teks) {
            $this->assertStringContainsString($teks, $pesan);
        }
    }

    public function test_modal_konfirmasi_tampil_setelah_form_terkirim(): void
    {
        $this->withSession(['sukses' => true, 'tautanWa' => 'https://wa.me/628111?text=Halo'])
            ->get(route('rancang-trip-kustom'))
            ->assertOk()
            ->assertSee('modal-sukses-judul', false)
            ->assertSee('Satu langkah lagi: kirim draft ke admin')
            ->assertSee('Admin MISTBORNE sudah menerima draft chat Anda dan akan membalas lewat WhatsApp');
    }

    public function test_modal_konfirmasi_tidak_tampil_sebelum_submit(): void
    {
        $this->get(route('rancang-trip-kustom'))->assertOk()->assertDontSee('modal-sukses-judul', false);
    }
}
