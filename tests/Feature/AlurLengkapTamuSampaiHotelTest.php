<?php

namespace Tests\Feature;

use App\Layanan\AlurReservasiHotel;
use App\Mail\PenugasanPersonel;
use App\Models\Hotel;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\DataGuide;
use Tests\TestCase;

/**
 * Satu jalur utuh seperti pemakaian nyata: tamu mengisi formulir, admin membuat pesanan (mobil otomatis), pembayaran masuk,
 * guide ditugaskan (email ke alamat uji), lalu hotel diurus langkah demi langkah sampai antrean dan detail pesanan sama-sama "Sudah diurus".
 */
class AlurLengkapTamuSampaiHotelTest extends TestCase
{
    public function test_dari_formulir_tamu_sampai_hotel_sudah_diurus(): void
    {
        Mail::fake();
        Storage::fake('local');
        $admin = User::factory()->create();
        $journey = Perjalanan::query()->where('nama', 'BROMO SUNRISE JOURNEY')->firstOrFail();
        $this->bukaTanggal($journey, Carbon::today()->addDays(25));
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Uji Lengkap', 'whatsapp' => '081808606086', 'tarif_mulai' => 700_000, 'tipe_kamar' => [['nama' => 'Family Room', 'kapasitas' => 4]]]);
        $journey->hotel()->attach($hotel);
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Lengkap', 'peran' => 'guide', 'email' => 'razifarba4@gmail.com', 'whatsapp' => '6281808606086', 'status_operasional' => 'siap', 'aktif' => true]);

        // 1. Tamu mengisi formulir (tanpa info mobil), nomor tamu tersimpan dan terbawa ke pesan WhatsApp.
        $permintaan = PermintaanPesanan::query()->create(['token' => Str::random(64), 'whatsapp' => '089912345678', 'status' => 'menunggu_tamu', 'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru()]);
        $this->get(route('form-pesanan.tampil', $permintaan->token))->assertOk()->assertDontSee('Land Cruiser');
        $this->post(route('form-pesanan.simpan', $permintaan->token), [
            'nama_lengkap' => 'Tamu Lengkap', 'whatsapp' => '089912345678', 'perjalanan_id' => $journey->id,
            'tanggal_trip' => Carbon::today()->addDays(25)->toDateString(), 'jumlah_wisatawan' => 4, 'lokasi_pickup' => 'Stasiun Malang',
        ])->assertSessionHasNoErrors();
        $tautan = $this->get(route('form-pesanan.tampil', $permintaan->token))->viewData('tautanKonfirmasiWa');
        $this->assertStringContainsString('Nomor WhatsApp saya: 089912345678', rawurldecode($tautan));

        // 2. Admin membuat pesanan: mobil utama Bromo ditugaskan otomatis.
        $this->actingAs($admin)->post(route('admin.permintaan-pesanan.buat-pesanan', $permintaan))->assertSessionHasNoErrors();
        $pesanan = Pesanan::query()->latest('id')->firstOrFail();
        $this->assertSame($journey->kendaraan_id, $pesanan->kendaraan_id);
        $this->assertSame(4 * 1, $pesanan->jumlah_wisatawan);

        // 3. Pembayaran lunas masuk, pesanan berstatus Pembayaran dan masuk tabel Selesaikan Hotel di dashboard.
        $pesanan->update(['status' => 'payment', 'harga' => 10_300_000, 'nominal_dibayar' => 10_300_000]);
        $this->actingAs($admin)->get(route('admin.dashboard.indeks'))->assertOk()->assertViewHas('selesaikanHotel', fn ($daftar): bool => $daftar->contains('id', $pesanan->id));

        // 4. Guide ditugaskan: email penugasan dikirim ke alamat uji.
        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $guide->id, 'kendaraan_id' => $pesanan->kendaraan_id])->assertSessionHasNoErrors();
        Mail::assertSent(PenugasanPersonel::class, fn (PenugasanPersonel $mail): bool => $mail->hasTo('razifarba4@gmail.com'));

        // 5. Hotel diurus: simpan reservasi, lalu tiap langkah.
        $trip = $pesanan->fresh()->tanggal_trip;
        $akhirTrip = $pesanan->fresh()->tanggal_selesai;
        $this->actingAs($admin)->put(route('admin.pesanan.hotel.simpan', $pesanan), [
            'hotel_id' => $hotel->id, 'hotel_check_in' => $trip->toDateString(), 'hotel_check_out' => $akhirTrip->toDateString(),
            'jumlah_kamar' => 1, 'tipe_kamar' => 'Family Room', 'status_reservasi_hotel' => 'diminta',
        ])->assertSessionHasNoErrors();
        $alur = app(AlurReservasiHotel::class);
        $this->assertSame('pesan', $alur->langkahBerikutnya($pesanan->fresh())['kunci']);

        $this->actingAs($admin)->patch(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'permintaan']))->assertSessionHas('sukses');
        $this->actingAs($admin)->put(route('admin.pesanan.hotel.konfirmasi', $pesanan), ['kode_booking_hotel' => 'BOOK-1'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->put(route('admin.pesanan.hotel.pembayaran.simpan', $pesanan), [
            'bayar_hotel_nominal' => 700_000, 'bayar_hotel_tanggal' => today()->toDateString(), 'bayar_hotel_bukti' => UploadedFile::fake()->image('bukti.png'),
        ])->assertSessionHasNoErrors();
        foreach (['bukti', 'konfirmasi_bayar', 'kabari_tamu'] as $langkah) {
            $this->assertNotNull($alur->langkahBerikutnya($pesanan->fresh()), "Alur selesai terlalu cepat sebelum langkah {$langkah}");
            $this->actingAs($admin)->patch(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, $langkah]))->assertSessionHas('sukses');
        }

        // 6. Dashboard dan detail pesanan sama-sama menunjukkan hotel sudah diurus.
        $this->assertTrue($alur->sudahDiurus($pesanan->fresh()));
        $this->actingAs($admin)->get(route('admin.dashboard.indeks'))->assertOk()->assertViewHas('selesaikanHotel', fn ($daftar): bool => ! $daftar->contains('id', $pesanan->id));
        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))->assertOk()->assertSee('Sudah diurus')->assertDontSee('tahap lagi');
    }
}
