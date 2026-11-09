<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat dan jadwal servis kendaraan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servis_kendaraan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kendaraan_id')->constrained('kendaraan')->cascadeOnDelete();
            $table->string('jenis', 80);
            $table->string('status', 12)->default('terjadwal');
            $table->date('jatuh_tempo_tanggal')->nullable();
            $table->unsignedInteger('jatuh_tempo_odometer')->nullable();
            $table->date('tanggal_servis')->nullable();
            $table->unsignedInteger('odometer_servis')->nullable();
            $table->unsignedInteger('biaya')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index(['kendaraan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servis_kendaraan');
    }
};
