<?php

namespace App\Policies;

use App\Models\Perjalanan;
use App\Models\User;

/**
 * Admin boleh melihat/membuat/mengubah apa saja; penghapusan ditolak bila journey masih punya pesanan aktif.
 */
class PerjalananPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Perjalanan $perjalanan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Perjalanan $perjalanan): bool
    {
        return true;
    }

    public function delete(User $user, Perjalanan $perjalanan): bool
    {
        return ! $perjalanan->punyaPesananAktif();
    }

    public function restore(User $user, Perjalanan $perjalanan): bool
    {
        return true;
    }
}
