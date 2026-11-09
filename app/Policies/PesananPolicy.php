<?php

namespace App\Policies;

use App\Models\Pesanan;
use App\Models\User;

/**
 * Satu-satunya akun admin boleh melihat/membuat/mengubah/menghapus pesanan.
 */
class PesananPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Pesanan $pesanan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Pesanan $pesanan): bool
    {
        return ! $pesanan->terkunci();
    }

    public function delete(User $user, Pesanan $pesanan): bool
    {
        return true;
    }
}
