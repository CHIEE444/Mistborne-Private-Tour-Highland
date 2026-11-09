<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat aktivitas personel pada pesanan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aktivitas_personel', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('personel_id')->constrained('personel_operasional')->cascadeOnDelete();
            $table->foreignId('pesanan_id')->nullable()->constrained('pesanan')->nullOnDelete();
            $table->string('jenis', 30);
            $table->string('keterangan');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['personel_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas_personel');
    }
};
