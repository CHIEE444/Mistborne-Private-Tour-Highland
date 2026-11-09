<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master armada kendaraan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kendaraan', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('jenis', 20);
            $table->string('plat_nomor', 15);
            $table->unsignedSmallInteger('kapasitas');
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->string('warna', 30)->nullable();
            $table->string('status', 20)->default('siap')->index();
            $table->string('foto')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('bahan_bakar', 20)->nullable();
            $table->unsignedInteger('odometer')->nullable();
            $table->date('stnk_berlaku_sampai')->nullable();
            $table->date('pajak_berlaku_sampai')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kendaraan');
    }
};
