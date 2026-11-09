<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat penawaran untuk pesanan kustom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_penawaran_pesanan_kustom', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pesanan_kustom_id')->constrained('pesanan_kustom')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('versi');
            $table->json('snapshot');
            $table->timestamp('terkirim_pada');
            $table->timestamps();
            $table->unique(['pesanan_kustom_id', 'versi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_penawaran_pesanan_kustom');
    }
};
