<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\Artikel;
use Illuminate\View\View;

class ArtikelController extends Controller
{
    public function show(string $slug): View
    {
        // terbit(): hanya artikel yang published_at-nya sudah tercapai (real time) yang bisa dibuka.
        $artikel = Artikel::query()->terbit()->where('slug', $slug)->firstOrFail();

        $lainnya = Artikel::query()
            ->terbit()
            ->whereKeyNot($artikel->id)
            ->latest('published_at')
            ->take(3)
            ->get()
            ->map->kePublik();

        return view('pages.artikel-detail', [
            'artikel' => $artikel,
            'data' => $artikel->kePublik(),
            'lainnya' => $lainnya,
        ]);
    }
}
