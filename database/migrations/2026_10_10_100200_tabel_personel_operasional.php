<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master personel operasional (guide) beserta akun panelnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personel_operasional', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('peran', 20);
            $table->string('whatsapp', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('wilayah_operasi')->nullable();
            $table->unsignedTinyInteger('pengalaman_tahun')->nullable();
            $table->text('keahlian')->nullable();
            $table->string('nomor_sim', 40)->nullable();
            $table->string('status_operasional', 20)->default('siap');
            $table->string('foto_profil')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('jenis_sim', 5)->nullable();
            $table->date('masa_berlaku_sim')->nullable();
            $table->unsignedSmallInteger('pengalaman_mengemudi_tahun')->nullable();
            $table->string('kendaraan_dikuasai')->nullable();
            $table->index(['peran', 'aktif', 'status_operasional']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personel_operasional');
    }
};
