<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pertanyaan calon tamu dari website.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pertanyaan_tamu', function (Blueprint $table): void {
            $table->id();
            $table->string('sumber', 30);
            $table->string('status', 20)->default('baru');
            $table->string('nama')->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('journey')->nullable();
            $table->string('lokasi_pickup')->nullable();
            $table->date('tanggal_perjalanan')->nullable();
            $table->string('durasi', 60)->nullable();
            $table->string('jenis_perjalanan', 60)->nullable();
            $table->text('pesan');
            $table->text('catatan_admin')->nullable();
            $table->string('sidik_pengirim', 64)->nullable();
            $table->foreignId('permintaan_pesanan_id')->nullable()->constrained('permintaan_pesanan')->nullOnDelete();
            $table->timestamps();
            $table->index(['sidik_pengirim', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pertanyaan_tamu');
    }
};
