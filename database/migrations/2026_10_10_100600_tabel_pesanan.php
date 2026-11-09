<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pesanan resmi (inti transaksi), termasuk penugasan guide dan reservasi hotel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('perjalanan_id')->constrained('perjalanan')->restrictOnDelete();
            $table->string('nama_perjalanan');
            $table->date('tanggal_trip');
            $table->date('tanggal_selesai')->nullable();
            $table->string('nama_lengkap');
            $table->string('whatsapp');
            $table->string('paket_opsional')->nullable();
            $table->string('nama_hotel')->nullable();
            $table->string('lokasi_pickup');
            $table->unsignedTinyInteger('jumlah_wisatawan');
            $table->text('permintaan_khusus')->nullable();
            $table->unsignedBigInteger('harga')->nullable();
            $table->string('status')->default('dihubungi');
            $table->string('sumber');
            $table->timestamp('dikonfirmasi_at')->nullable();
            $table->string('status_sebelum_hold')->nullable();
            $table->string('status_sebelum_batal')->nullable();
            $table->date('tanggal_lama')->nullable();
            $table->unsignedBigInteger('charge_ganti_jadwal')->default(0);
            $table->string('pihak_pembatal')->nullable();
            $table->text('alasan_batal')->nullable();
            $table->unsignedBigInteger('nominal_refund')->default(0);
            $table->string('nama_guide')->nullable();
            $table->string('bukti_pembayaran')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->string('token_rating')->unique();
            $table->boolean('pengingat_h7_terkirim')->default(false);
            $table->boolean('pengingat_h2_terkirim')->default(false);
            $table->timestamps();
            $table->unsignedBigInteger('user_web_id')->nullable()->index();
            $table->time('waktu_pickup')->nullable();
            $table->unsignedBigInteger('nominal_dibayar')->nullable();
            $table->string('metode_pembayaran', 30)->nullable();
            $table->foreignId('guide_personel_id')->nullable()->constrained('personel_operasional')->nullOnDelete();
            $table->string('status_konfirmasi_guide', 20)->nullable();
            $table->timestamp('guide_dikonfirmasi_at')->nullable();
            $table->timestamp('guide_trip_selesai_at')->nullable();
            $table->timestamp('pembayaran_diterima_at')->nullable();
            $table->boolean('pengingat_h3_terkirim')->default(false);
            $table->uuid('token_booking')->nullable()->unique();
            $table->unsignedSmallInteger('durasi_hari')->nullable();
            $table->foreignId('kendaraan_id')->nullable()->index()->constrained('kendaraan')->nullOnDelete();
            $table->string('status_penginapan', 10)->nullable();
            $table->foreignId('hotel_id')->nullable()->constrained('hotel')->nullOnDelete();
            $table->date('hotel_check_in')->nullable();
            $table->date('hotel_check_out')->nullable();
            $table->unsignedTinyInteger('jumlah_kamar')->nullable();
            $table->string('tipe_kamar', 100)->nullable();
            $table->string('kode_booking_hotel', 100)->nullable();
            $table->string('status_reservasi_hotel', 15)->nullable();
            $table->text('catatan_reservasi_hotel')->nullable();
            $table->string('alamat_hotel', 500)->nullable();
            $table->string('kendaraan_pilihan', 150)->nullable();
            $table->timestamp('permintaan_hotel_dikirim_at')->nullable();
            $table->unsignedBigInteger('bayar_hotel_nominal')->nullable();
            $table->date('bayar_hotel_tanggal')->nullable();
            $table->string('bayar_hotel_bukti')->nullable();
            $table->timestamp('bukti_bayar_hotel_dikirim_at')->nullable();
            $table->timestamp('bayar_hotel_dikonfirmasi_at')->nullable();
            $table->timestamp('tamu_dikabari_hotel_at')->nullable();
            $table->timestamp('konfirmasi_ulang_hotel_at')->nullable();
            $table->index(['perjalanan_id', 'tanggal_trip', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
