<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\AktivitasPersonel;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Support\DataGuide;
use Tests\TestCase;

class PersonelPanelTest extends TestCase
{
    private function personelDenganAkun(string $peran, array $akun = []): array
    {
        $user = User::factory()->create(['jabatan' => $peran, ...$akun]);
        $personel = $user->personel()->create([
            'nama' => ucfirst($peran).' Uji',
            'peran' => $peran,
            'email' => $user->email,
            'status_operasional' => 'siap',
            'aktif' => true,
        ]);

        return [$user, $personel];
    }

    public function test_halaman_masuk_panel_guide_tetap_terbuka_saat_browser_memegang_sesi_akun_lain(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('personel.masuk'))->assertOk();
    }

    public function test_guide_yang_sudah_masuk_dari_tautan_email_langsung_ke_dashboard(): void
    {
        [$guide] = $this->personelDenganAkun('guide');

        $this->actingAs($guide)->get(route('personel.masuk'))->assertRedirect(route('personel.dashboard'));
    }

    public function test_guide_melihat_log_aktivitasnya_sendiri_saja(): void
    {
        [$guide, $personel] = $this->personelDenganAkun('guide');
        [, $personelLain] = $this->personelDenganAkun('guide');
        AktivitasPersonel::catat($personel, 'ditugaskan', 'Ditugaskan ke MST-AKTIVITAS-SAYA.');
        AktivitasPersonel::catat($personelLain, 'ditugaskan', 'Ditugaskan ke MST-AKTIVITAS-LAIN.');

        $this->actingAs($guide)->get(route('personel.aktivitas'))
            ->assertOk()
            ->assertSee('MST-AKTIVITAS-SAYA')
            ->assertDontSee('MST-AKTIVITAS-LAIN');
    }

    public function test_aktivitas_tercatat_saat_guide_ditugaskan_menanggapi_dan_menyelesaikan_trip(): void
    {
        [$guide, $personel] = $this->personelDenganAkun('guide');
        $pesanan = Pesanan::factory()->create([
            'guide_personel_id' => $personel->id,
            'status_konfirmasi_guide' => 'menunggu',
            'status' => BookingStatus::Payment,
            'tanggal_trip' => now()->subDays(3),
            'tanggal_selesai' => now()->subDays(2),
        ]);

        $this->actingAs($guide)->post(route('personel.pesanan.tanggapi', $pesanan), ['jawaban' => 'diterima'])->assertSessionHasNoErrors();
        $this->actingAs($guide)->post(route('personel.pesanan.trip-selesai', $pesanan))->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(['diterima', 'trip_selesai'], AktivitasPersonel::query()->where('personel_id', $personel->id)->pluck('jenis')->all());
    }

    public function test_guide_dapat_mengganti_kata_sandi_dengan_sandi_lama_yang_benar(): void
    {
        [$guide, $personel] = $this->personelDenganAkun('guide', ['password' => 'sandi-lama-123']);

        $this->actingAs($guide)->put(route('personel.profil.sandi'), ['sandi_lama' => 'salah-total', 'sandi_baru' => 'sandi-baru-456', 'sandi_baru_confirmation' => 'sandi-baru-456'])
            ->assertSessionHasErrors('sandi_lama');
        $this->assertTrue(Hash::check('sandi-lama-123', $guide->fresh()->password));

        $this->actingAs($guide)->put(route('personel.profil.sandi'), ['sandi_lama' => 'sandi-lama-123', 'sandi_baru' => 'sandi-baru-456', 'sandi_baru_confirmation' => 'sandi-baru-456'])
            ->assertRedirect(route('personel.profil'));
        $this->assertTrue(Hash::check('sandi-baru-456', $guide->fresh()->password));
        $this->assertDatabaseHas('aktivitas_personel', ['personel_id' => $personel->id, 'jenis' => 'sandi']);
    }

    public function test_profil_guide_hanya_menampilkan_data_dan_tidak_bisa_mengubah_kontak(): void
    {
        [$guide, $personel] = $this->personelDenganAkun('guide');
        $personel->update(['whatsapp' => '628111222333']);

        $this->actingAs($guide)->get(route('personel.profil'))->assertOk()->assertSee('628111222333')->assertSee('hanya bisa diubah oleh admin');
        $this->actingAs($guide)->put(route('personel.profil'), ['email' => 'baru@example.com', 'whatsapp' => '0'])->assertStatus(405);
        $this->assertSame('628111222333', $personel->fresh()->whatsapp);
    }

    public function test_guide_dapat_melihat_pesanan_yang_ditugaskan(): void
    {
        [$guide, $personel] = $this->personelDenganAkun('guide');
        $pesanan = Pesanan::factory()->create([
            'guide_personel_id' => $personel->id,
            'status_konfirmasi_guide' => 'menunggu',
        ]);

        $this->actingAs($guide)
            ->get(route('personel.dashboard'))
            ->assertOk()
            ->assertSee($pesanan->kode)
            ->assertSee('Terima tugas')
            ->assertSee('Tolak tugas');
    }

    public function test_akun_dengan_jabatan_lama_driver_tidak_bisa_mengakses_panel(): void
    {
        [$guide] = $this->personelDenganAkun('guide');
        $guide->update(['jabatan' => 'driver']);

        $this->actingAs($guide)
            ->get(route('personel.dashboard'))
            ->assertRedirect(route('personel.masuk'));

        $this->assertGuest();
    }

    public function test_guide_dapat_menerima_tugas_dan_admin_menerima_notifikasi(): void
    {
        [$guide, $personel] = $this->personelDenganAkun('guide');
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $pesanan = Pesanan::factory()->create([
            'guide_personel_id' => $personel->id,
            'status_konfirmasi_guide' => 'menunggu',
        ]);

        $this->actingAs($guide)
            ->from(route('personel.dashboard'))
            ->post(route('personel.pesanan.tanggapi', $pesanan), ['jawaban' => 'diterima'])
            ->assertRedirect(route('personel.dashboard'))
            ->assertSessionHas('sukses');

        $pesanan->refresh();
        $this->assertSame('diterima', $pesanan->status_konfirmasi_guide);
        $this->assertNotNull($pesanan->guide_dikonfirmasi_at);
        $this->assertSame(1, $admin->notifications()->where('type', 'App\\Notifications\\TanggapanPenugasanNotification')->count());

        $this->actingAs($guide)
            ->post(route('personel.pesanan.tanggapi', $pesanan), ['jawaban' => 'ditolak'])
            ->assertStatus(409);
        $this->assertSame(1, $admin->notifications()->where('type', 'App\\Notifications\\TanggapanPenugasanNotification')->count());
    }

    public function test_tugas_yang_sudah_dibatalkan_tidak_bisa_dikonfirmasi(): void
    {
        [$guide, $personel] = $this->personelDenganAkun('guide');
        $pesanan = Pesanan::factory()->dibatalkan()->create([
            'guide_personel_id' => $personel->id,
            'status_konfirmasi_guide' => 'menunggu',
        ]);

        $this->actingAs($guide)
            ->post(route('personel.pesanan.tanggapi', $pesanan), ['jawaban' => 'diterima'])
            ->assertStatus(409);

        $this->assertSame('menunggu', $pesanan->fresh()->status_konfirmasi_guide);
    }

    public function test_personel_lain_tidak_bisa_menanggapi_tugas_guide(): void
    {
        [$driver] = $this->personelDenganAkun('guide');
        $personelGuide = PersonelOperasional::query()->create([...DataGuide::sim(),
            'nama' => 'Guide Uji',
            'peran' => 'guide',
            'status_operasional' => 'siap',
            'aktif' => true,
        ]);
        $pesanan = Pesanan::factory()->create([
            'guide_personel_id' => $personelGuide->id,
            'status_konfirmasi_guide' => 'menunggu',
        ]);

        $this->actingAs($driver)
            ->post(route('personel.pesanan.tanggapi', $pesanan), ['jawaban' => 'diterima'])
            ->assertForbidden();
    }

    public function test_trip_tidak_bisa_ditandai_selesai_sebelum_tanggal_akhirnya(): void
    {
        [$driver, $personel] = $this->personelDenganAkun('guide');
        $pesanan = Pesanan::factory()->create([
            'tanggal_trip' => today()->addDay(),
            'tanggal_selesai' => today()->addDays(2),
            'guide_personel_id' => $personel->id,
            'status_konfirmasi_guide' => 'diterima',
        ]);

        $this->actingAs($driver)
            ->post(route('personel.pesanan.trip-selesai', $pesanan))
            ->assertRedirect(route('personel.dashboard'))
            ->assertSessionHasErrors('trip');

        $this->assertNull($pesanan->fresh()->guide_trip_selesai_at);
    }

    public function test_trip_selesai_dapat_dicatat_setelah_trip_berakhir(): void
    {
        [$driver, $personel] = $this->personelDenganAkun('guide');
        $admin = User::factory()->create(['jabatan' => 'admin operasional']);
        $pesanan = Pesanan::factory()->create([
            'tanggal_trip' => today()->subDays(2),
            'tanggal_selesai' => today()->subDay(),
            'guide_personel_id' => $personel->id,
            'status_konfirmasi_guide' => 'diterima',
        ]);

        $this->actingAs($driver)
            ->post(route('personel.pesanan.trip-selesai', $pesanan))
            ->assertRedirect(route('personel.dashboard'))
            ->assertSessionHas('sukses');

        $this->assertNotNull($pesanan->fresh()->guide_trip_selesai_at);
        $this->assertSame(1, $admin->notifications()->where('type', 'App\\Notifications\\TripSelesaiNotification')->count());
    }

    public function test_trip_yang_belum_diterima_tidak_bisa_ditandai_selesai(): void
    {
        [$guide, $personel] = $this->personelDenganAkun('guide');
        $pesanan = Pesanan::factory()->create([
            'tanggal_trip' => today()->subDay(),
            'guide_personel_id' => $personel->id,
            'status_konfirmasi_guide' => 'menunggu',
        ]);

        $this->actingAs($guide)
            ->post(route('personel.pesanan.trip-selesai', $pesanan))
            ->assertRedirect(route('personel.dashboard'))
            ->assertSessionHasErrors('trip');

        $this->assertNull($pesanan->fresh()->guide_trip_selesai_at);
    }

    public function test_penugasan_aktif_mencegah_personel_dihapus(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        [, $personel] = $this->personelDenganAkun('guide');
        $pesanan = Pesanan::factory()->create([
            'guide_personel_id' => $personel->id,
            'status' => BookingStatus::Payment,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.guide.indeks'))
            ->delete(route('admin.guide.hapus', $personel))
            ->assertRedirect(route('admin.guide.indeks'))
            ->assertSessionHas('gagal');

        $this->assertDatabaseHas('personel_operasional', ['id' => $personel->id]);
        $this->assertSame($personel->id, $pesanan->fresh()->guide_personel_id);
    }

    public function test_peran_personel_selalu_guide_dan_peran_driver_ditolak(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        [$guide, $personel] = $this->personelDenganAkun('guide');

        $this->actingAs($admin)
            ->from(route('admin.guide.indeks'))
            ->put(route('admin.guide.perbarui', $personel), [
                ...DataGuide::form(),
                'nama' => $personel->nama,
                'peran' => 'driver',
                'email' => $personel->email,
                'status_operasional' => 'siap',
                'aktif' => '1',
            ])
            ->assertRedirect(route('admin.guide.indeks'))
            ->assertSessionHasErrors('peran');

        $this->assertSame('guide', $personel->fresh()->peran);
        $this->assertSame('guide', $guide->fresh()->jabatan);
    }

    public function test_admin_dapat_membuat_akun_panel_personel_yang_langsung_masuk_dengan_kata_sandi_bawaan(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['jabatan' => 'admin']);

        $this->actingAs($admin)->post(route('admin.guide.simpan'), [...DataGuide::form(),
            'nama' => 'Guide Baru',
            'peran' => 'guide',
            'email' => 'guide.baru@example.com',
            'status_operasional' => 'siap',
            'aktif' => '1',
            'buat_akun_panel' => '1',
        ])->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sukses', 'Personel ditambahkan dan akun panel dibuat. Masuk Panel Guide dengan email guide.baru@example.com dan kata sandi "password".');

        $akun = User::query()->where('email', 'guide.baru@example.com')->firstOrFail();
        $personel = PersonelOperasional::query()->where('nama', 'Guide Baru')->firstOrFail();
        $this->assertSame('guide', $akun->jabatan);
        $this->assertSame($akun->id, $personel->user_id);
        Notification::assertNotSentTo($akun, ResetPassword::class);

        auth()->logout();
        $this->post(route('personel.proses-masuk'), [
            'email' => $akun->email,
            'password' => 'password',
        ])->assertRedirect(route('personel.dashboard'));
    }

    public function test_formulir_guide_menyediakan_opsi_buat_akun_panel(): void
    {
        $this->tanpaGuideCadangan();
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $akun = User::factory()->create(['email' => 'guide.berakun@example.com', 'jabatan' => 'guide']);
        PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Berakun', 'peran' => 'guide', 'email' => $akun->email, 'status_operasional' => 'siap', 'aktif' => true, 'user_id' => $akun->id]);
        PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Tanpa Akun', 'peran' => 'guide', 'email' => 'guide.tanpa@example.com', 'status_operasional' => 'siap', 'aktif' => true]);

        $html = $this->actingAs($admin)->get(route('admin.guide.indeks'))->assertOk()
            ->assertSee('Buat akun Panel Guide')
            ->assertSee('Akun Panel Guide aktif:')
            ->assertSee('guide.berakun@example.com')
            ->getContent();

        $this->assertSame(2, substr_count($html, 'name="buat_akun_panel"'));
    }

    public function test_akun_yang_sudah_ada_hanya_bisa_ditautkan_jika_perannya_cocok(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $akun = User::factory()->create(['email' => 'driver.lama@example.com', 'jabatan' => 'guide']);

        $this->actingAs($admin)->post(route('admin.guide.simpan'), [...DataGuide::form(),
            'nama' => 'Driver Baru',
            'peran' => 'guide',
            'email' => $akun->email,
            'status_operasional' => 'siap',
            'buat_akun_panel' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $personel = PersonelOperasional::query()->where('nama', 'Driver Baru')->firstOrFail();
        $this->assertSame($akun->id, $personel->user_id);
        Notification::assertNothingSent();

        $this->actingAs($admin)
            ->post(route('admin.guide.simpan'), [...DataGuide::form(),
                'nama' => 'Driver Kedua',
                'peran' => 'guide',
                'email' => $akun->email,
                'status_operasional' => 'siap',
                'buat_akun_panel' => '1',
            ])->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('personel_operasional', ['nama' => 'Driver Kedua']);
    }

    public function test_akun_tamu_tidak_dipromosikan_diam_diam_menjadi_akun_personel(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        User::factory()->create(['email' => 'tamu@example.com', 'jabatan' => 'tamu']);

        $this->actingAs($admin)
            ->from(route('admin.guide.indeks'))
            ->post(route('admin.guide.simpan'), [...DataGuide::form(),
                'nama' => 'Guide Tamu',
                'peran' => 'guide',
                'email' => 'tamu@example.com',
                'status_operasional' => 'siap',
                'buat_akun_panel' => '1',
            ])
            ->assertRedirect(route('admin.guide.indeks'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('personel_operasional', ['nama' => 'Guide Tamu']);
        $this->assertDatabaseHas('users', ['email' => 'tamu@example.com', 'jabatan' => 'tamu']);
    }

    public function test_akun_panel_mewajibkan_email(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);

        $this->actingAs($admin)->post(route('admin.guide.simpan'), [...DataGuide::form(),
            'nama' => 'Tanpa Email',
            'peran' => 'guide',
            'status_operasional' => 'siap',
            'buat_akun_panel' => '1',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('personel_operasional', ['nama' => 'Tanpa Email']);
    }

    public function test_admin_dapat_membuat_akun_panel_untuk_personel_yang_sudah_ada(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $personel = PersonelOperasional::query()->create([...DataGuide::sim(),
            'nama' => 'Driver Lama',
            'peran' => 'guide',
            'email' => 'driver.lama2@example.com',
            'status_operasional' => 'siap',
            'aktif' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.guide.perbarui', $personel), [...DataGuide::form(),
            'nama' => 'Driver Lama',
            'peran' => 'guide',
            'email' => 'driver.lama2@example.com',
            'status_operasional' => 'siap',
            'aktif' => '1',
            'buat_akun_panel' => '1',
        ])->assertSessionHasNoErrors();

        $akun = User::query()->where('email', 'driver.lama2@example.com')->firstOrFail();
        $this->assertSame('guide', $akun->jabatan);
        $this->assertSame($akun->id, $personel->fresh()->user_id);
        $this->assertTrue(Hash::check('password', $akun->password));
        Notification::assertNotSentTo($akun, ResetPassword::class);
    }

    public function test_nomor_whatsapp_pesanan_kustom_admin_menerima_format_dengan_tanda_baca(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);

        $this->actingAs($admin)->post(route('admin.pesanan-kustom.simpan'), [
            'nama' => 'Tamu Format',
            'whatsapp' => '+62 812-3456-7890',
            'destinasi_diinginkan' => 'Bromo',
            'tanggal_fleksibel' => '1',
            'durasi_hari' => 2,
            'lokasi_penjemputan' => 'Malang',
            'jumlah_orang' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pesanan_kustom', ['nama' => 'Tamu Format', 'whatsapp' => '6281234567890']);
    }
}
