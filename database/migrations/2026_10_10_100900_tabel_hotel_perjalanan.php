<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relasi hotel dengan journey (many-to-many).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_perjalanan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotel')->cascadeOnDelete();
            $table->foreignId('perjalanan_id')->constrained('perjalanan')->cascadeOnDelete();
            $table->unique(['hotel_id', 'perjalanan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_perjalanan');
    }
};
