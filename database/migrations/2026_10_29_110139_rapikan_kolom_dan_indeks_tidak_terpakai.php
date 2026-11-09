<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyamakan basis data yang sudah berjalan dengan migrasi pembuatan tabel: membuang kolom yang tidak pernah diisi
 * atau ditampilkan sistem, serta indeks ganda peninggalan migrasi lama. Database baru sudah terbentuk tanpa semuanya,
 * jadi setiap langkah memeriksa keberadaannya lebih dulu.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private const KOLOM = [
        'pesanan_kustom' => [
            'email', 'jumlah_anak', 'rentang_usia_anak', 'batasan_waktu', 'lokasi_pengantaran', 'kebutuhan_aksesibilitas',
            'sumber_informasi', 'preferensi_kontak', 'waktu_kontak', 'kebutuhan_diet', 'acara_khusus',
        ],
        'perjalanan' => ['accent_icon', 'accent_label', 'tambahan_all_in'],
        'users' => ['google_id', 'avatar'],
    ];

    public function up(): void
    {
        // Duplikat dari indeks unik (perjalanan_id, tanggal) yang sudah ada.
        DB::statement('DROP INDEX IF EXISTS jadwal_keberangkatan_perjalanan_id_tanggal_index');
        DB::statement('DROP INDEX IF EXISTS users_google_id_index');

        foreach (self::KOLOM as $tabel => $kolom) {
            $ada = array_values(array_filter($kolom, fn (string $nama): bool => Schema::hasColumn($tabel, $nama)));
            if ($ada !== []) {
                Schema::table($tabel, fn (Blueprint $table) => $table->dropColumn($ada));
            }
        }
    }

    public function down(): void
    {
        Schema::table('pesanan_kustom', function (Blueprint $table): void {
            $table->string('email')->nullable();
            $table->unsignedSmallInteger('jumlah_anak')->nullable();
            $table->string('rentang_usia_anak')->nullable();
            $table->text('batasan_waktu')->nullable();
            $table->string('lokasi_pengantaran')->nullable();
            $table->text('kebutuhan_aksesibilitas')->nullable();
            $table->string('sumber_informasi')->nullable();
            $table->string('preferensi_kontak')->nullable();
            $table->string('waktu_kontak')->nullable();
            $table->text('kebutuhan_diet')->nullable();
            $table->string('acara_khusus')->nullable();
        });
        Schema::table('perjalanan', function (Blueprint $table): void {
            $table->string('accent_icon')->nullable();
            $table->string('accent_label')->nullable();
            $table->unsignedBigInteger('tambahan_all_in')->nullable();
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->string('google_id')->nullable()->index();
            $table->string('avatar')->nullable();
        });
    }
};
