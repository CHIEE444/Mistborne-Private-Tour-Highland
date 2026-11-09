<?php

namespace App\Policies;

use App\Models\Artikel;
use App\Models\User;

class ArtikelPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Artikel $artikel): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Artikel $artikel): bool
    {
        return true;
    }

    public function delete(User $user, Artikel $artikel): bool
    {
        return true;
    }

    public function restore(User $user, Artikel $artikel): bool
    {
        return true;
    }
}
