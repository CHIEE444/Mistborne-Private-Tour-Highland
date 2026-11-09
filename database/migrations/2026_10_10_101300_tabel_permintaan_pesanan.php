<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan pesanan dari form booking website.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_pesanan', function (Blueprint $table): void {
            $table->id();
            $table->string('token', 80)->unique();
            $table->string('whatsapp', 30);
            $table->string('status', 30)->default('menunggu_tamu');
            $table->foreignId('perjalanan_id')->nullable()->constrained('perjalanan')->nullOnDelete();
            $table->string('nama_lengkap')->nullable();
            $table->date('tanggal_trip')->nullable();
            $table->unsignedTinyInteger('jumlah_wisatawan')->nullable();
            $table->string('nama_hotel')->nullable();
            $table->string('lokasi_pickup')->nullable();
            $table->time('waktu_pickup')->nullable();
            $table->string('paket_opsional')->nullable();
            $table->text('permintaan_khusus')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->timestamp('token_berlaku_sampai');
            $table->foreignId('pesanan_id')->nullable()->unique()->constrained('pesanan')->nullOnDelete();
            $table->timestamps();
            $table->string('sumber', 20)->default('admin');
            $table->uuid('token_booking')->nullable()->unique();
            $table->foreignId('user_web_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan_tidak_jadi')->nullable();
            $table->string('status_penginapan', 10)->nullable();
            $table->string('alamat_hotel', 500)->nullable();
            $table->string('kendaraan_pilihan', 150)->nullable();
            $table->index(['perjalanan_id', 'tanggal_trip']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_pesanan');
    }
};
