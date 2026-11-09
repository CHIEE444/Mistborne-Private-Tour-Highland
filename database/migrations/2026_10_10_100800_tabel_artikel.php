<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master artikel Jurnal Perjalanan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artikel', function (Blueprint $table): void {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->string('kategori');
            $table->string('penulis');
            $table->unsignedSmallInteger('waktu_baca_menit')->default(1);
            $table->text('ringkasan');
            $table->longText('isi');
            $table->string('img')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artikel');
    }
};
