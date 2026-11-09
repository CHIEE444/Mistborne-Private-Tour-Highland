<?php

namespace App\Policies;

use App\Models\PesananKustom;
use App\Models\User;

class PesananKustomPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PesananKustom $pesananKustom): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PesananKustom $pesananKustom): bool
    {
        return true;
    }

    public function delete(User $user, PesananKustom $pesananKustom): bool
    {
        return true;
    }

    public function restore(User $user, PesananKustom $pesananKustom): bool
    {
        return true;
    }

    public function forceDelete(User $user, PesananKustom $pesananKustom): bool
    {
        return true;
    }
}
