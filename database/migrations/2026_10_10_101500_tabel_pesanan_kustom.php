<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan Rancang Trip Kustom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan_kustom', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('whatsapp');
            $table->string('destinasi_diinginkan');
            $table->string('jadwal_diinginkan');
            $table->unsignedSmallInteger('jumlah_orang');
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('harga_final')->nullable();
            $table->string('nama_pendamping')->nullable();
            $table->string('status')->default('baru');
            $table->timestamps();
            $table->softDeletes();
            $table->date('tanggal_diinginkan')->nullable();
            $table->boolean('tanggal_fleksibel')->default(false);
            $table->unsignedSmallInteger('durasi_hari')->nullable();
            $table->string('lokasi_penjemputan')->nullable();
            $table->text('rencana_perjalanan')->nullable();
            $table->text('rincian_penawaran')->nullable();
            $table->date('batas_penawaran')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->foreignId('pesanan_id')->nullable()->unique()->constrained('pesanan')->nullOnDelete();
            $table->json('minat_perjalanan')->nullable();
            $table->string('tempo_perjalanan')->nullable();
            $table->timestamp('penawaran_dikirim_pada')->nullable();
            $table->text('alasan_tidak_jadi')->nullable();
            $table->string('status_penginapan', 10)->nullable();
            $table->string('nama_hotel')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan_kustom');
    }
};
