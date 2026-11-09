<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ulasan tamu per pesanan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ulasan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pesanan_id')->unique()->constrained('pesanan')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('komentar');
            $table->string('video_path')->nullable();
            $table->boolean('izin_tampil')->default(false);
            $table->boolean('disetujui')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ulasan');
    }
};
