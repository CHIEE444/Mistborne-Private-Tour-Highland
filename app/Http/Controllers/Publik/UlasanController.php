<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Http\Requests\Publik\SimpanUlasanRequest;
use App\Models\Pesanan;
use App\Models\Ulasan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class UlasanController extends Controller
{
    public function create(string $token): Response
    {
        $pesanan = Pesanan::where('token_rating', $token)->first();

        // Token yang tidak dikenal tetap menampilkan penjelasan ramah, tetapi berstatus 404 (bukan 200).
        return response()->view('publik.ulasan.formulir-rating', [
            'pesanan' => $pesanan,
            'tautanAktif' => $pesanan?->tautanRatingAktif() ?? false,
        ], $pesanan === null ? 404 : 200);
    }

    public function store(SimpanUlasanRequest $request, string $token): RedirectResponse
    {
        $pesanan = Pesanan::where('token_rating', $token)->firstOrFail();

        abort_unless($pesanan->tautanRatingAktif(), 404);

        Ulasan::create([
            'pesanan_id' => $pesanan->id,
            'rating' => $request->integer('rating'),
            'komentar' => $request->string('komentar')->value(),
            'izin_tampil' => $request->boolean('izin_tampil'),
        ]);

        return redirect()->route('rating.formulir', $token)->with('sukses', true);
    }
}
