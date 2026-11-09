<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal keberangkatan, kuota, dan pemblokiran tanggal per journey.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_keberangkatan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('perjalanan_id')->constrained('perjalanan')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedSmallInteger('kuota')->nullable();
            $table->boolean('diblok')->default(false);
            $table->timestamps();
            $table->unique(['perjalanan_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_keberangkatan');
    }
};
