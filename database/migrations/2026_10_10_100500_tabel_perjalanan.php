<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master journey dan paket wisata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perjalanan', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->string('slug')->unique();
            $table->string('region')->nullable();
            $table->string('chip')->nullable();
            $table->string('tempat')->nullable();
            $table->string('tagline')->nullable();
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('harga')->nullable();
            $table->unsignedSmallInteger('durasi_hari')->nullable();
            $table->unsignedSmallInteger('maks_orang')->default(6);
            $table->string('img')->nullable();
            $table->json('highlights')->nullable();
            $table->json('itinerary')->nullable();
            $table->json('praktis')->nullable();
            $table->string('tone')->nullable();
            $table->string('rating_label')->nullable();
            $table->unsignedInteger('reviews_count')->nullable();
            $table->boolean('tampil_beranda')->default(false);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->boolean('template_pesanan_kustom')->default(false);
            $table->text('rekomendasi_penginapan')->nullable();
            $table->string('jenis', 10)->default('journey')->index();
            $table->string('label_unggulan', 120)->nullable();
            $table->foreignId('kendaraan_id')->nullable()->constrained('kendaraan')->nullOnDelete();
            $table->text('alasan_kendaraan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perjalanan');
    }
};
