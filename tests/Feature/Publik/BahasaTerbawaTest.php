<?php

namespace Tests\Feature\Publik;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class BahasaTerbawaTest extends TestCase
{
    public function test_bahasa_pilihan_terbawa_ke_halaman_tanpa_awalan_url(): void
    {
        $this->get('/en/journey')->assertOk();

        $this->get('/rancang-trip-kustom')->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_kembali_ke_beranda_indonesia_mengganti_bahasa_tersimpan(): void
    {
        $this->get('/ja/journey')->assertOk();
        $this->get('/')->assertOk();
        $this->get('/rancang-trip-kustom')->assertOk();

        $this->assertSame('id', app()->getLocale());
    }

    public function test_tanpa_pilihan_bahasa_default_indonesia(): void
    {
        $this->get('/rancang-trip-kustom')->assertOk();

        $this->assertSame(config('app.locale'), app()->getLocale());
    }

    public function test_pesan_galat_booking_mengikuti_bahasa_pilihan(): void
    {
        $tamu = User::factory()->create(['jabatan' => 'tamu']);

        $this->get('/zh/journey')->assertOk();

        $this->actingAs($tamu)
            ->post(route('booking.simpan'), ['token_booking' => (string) Str::uuid(), 'destination' => 'x'])
            ->assertSessionHasErrors('nama_lengkap');

        $this->assertSame('请填写姓名。', session('errors')->first('nama_lengkap'));
    }
}
