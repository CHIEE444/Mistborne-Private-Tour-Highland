<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan operasional global (satu baris).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('batas_bayar_hari')->default(3);
            $table->unsignedTinyInteger('persen_charge_ganti_jadwal')->default(5);
            $table->unsignedTinyInteger('kuota_default')->default(5);
            $table->unsignedTinyInteger('batas_ulasan_hari')->default(7);
            $table->unsignedSmallInteger('batas_video_mb')->default(100);
            $table->string('nomor_whatsapp_admin')->default('6281808606086');
            $table->timestamps();
            $table->text('rekomendasi_penginapan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
    }
};
