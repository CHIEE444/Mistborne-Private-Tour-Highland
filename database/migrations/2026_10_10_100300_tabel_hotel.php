<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master hotel mitra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->unsignedTinyInteger('bintang')->default(4);
            $table->string('area', 100);
            $table->text('alamat')->nullable();
            $table->string('tautan_peta', 500)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('nama_pic', 100)->nullable();
            $table->unsignedInteger('tarif_mulai')->nullable();
            $table->unsignedInteger('tarif_sampai')->nullable();
            $table->unsignedTinyInteger('kapasitas_kamar')->default(2);
            $table->boolean('tersedia_extra_bed')->default(false);
            $table->json('fasilitas')->nullable();
            $table->time('jam_check_in')->default('14:00:00');
            $table->time('jam_check_out')->default('12:00:00');
            $table->text('kebijakan_pembatalan')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('aktif')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->string('foto', 500)->nullable();
            $table->boolean('sorotan')->default(false);
            $table->json('tipe_kamar')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('jarak_destinasi', 150)->nullable();
            $table->json('galeri')->nullable();
            $table->string('slug', 190)->nullable()->unique();
            $table->string('google_place_id')->nullable();
            $table->decimal('google_rating', 2, 1)->nullable();
            $table->unsignedInteger('google_jumlah_ulasan')->nullable();
            $table->timestamp('google_diperbarui')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel');
    }
};
