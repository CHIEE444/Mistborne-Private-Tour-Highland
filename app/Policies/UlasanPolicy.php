<?php

namespace App\Policies;

use App\Models\Ulasan;
use App\Models\User;

class UlasanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ulasan $ulasan): bool
    {
        return true;
    }

    public function update(User $user, Ulasan $ulasan): bool
    {
        return true;
    }

    public function delete(User $user, Ulasan $ulasan): bool
    {
        return true;
    }

    public function restore(User $user, Ulasan $ulasan): bool
    {
        return true;
    }
}
