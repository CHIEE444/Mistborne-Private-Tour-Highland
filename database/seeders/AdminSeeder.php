<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Buat satu akun admin dari variabel ADMIN_SEED_* di .env (registrasi publik dimatikan,
     * satu-satunya cara masuk sistem adalah lewat akun yang dibuat seeder ini).
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => config('mistborne.admin.email')],
            [
                'name' => config('mistborne.admin.name'),
                'jabatan' => 'Admin Operasional',
                'password' => config('mistborne.admin.password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
