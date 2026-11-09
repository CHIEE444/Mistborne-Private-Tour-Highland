<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil pengiriman email penugasan terakhir ke guide, supaya admin melihat terus (bukan hanya sekali di notifikasi)
 * apakah email sudah diterima layanan email, gagal, atau guide belum punya alamat email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table): void {
            $table->string('email_penugasan_status', 20)->nullable()->after('guide_trip_selesai_at');
            $table->string('email_penugasan_alamat')->nullable()->after('email_penugasan_status');
            $table->timestamp('email_penugasan_at')->nullable()->after('email_penugasan_alamat');
            $table->string('email_penugasan_galat', 500)->nullable()->after('email_penugasan_at');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table): void {
            $table->dropColumn(['email_penugasan_status', 'email_penugasan_alamat', 'email_penugasan_at', 'email_penugasan_galat']);
        });
    }
};
