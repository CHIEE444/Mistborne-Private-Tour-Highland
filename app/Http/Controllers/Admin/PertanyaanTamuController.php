<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PertanyaanTamu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PertanyaanTamuController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(PertanyaanTamu::STATUS))],
        ]);
        $status = $filter['status'] ?? null;

        return view('admin.pertanyaan-tamu.indeks', [
            'pertanyaan' => PertanyaanTamu::query()
                ->with('permintaan')
                ->when($status, fn ($query, string $nilai) => $query->where('status', $nilai))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'jumlahPerStatus' => PertanyaanTamu::query()->selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status'),
            'statusAktif' => $status,
        ]);
    }

    public function perbarui(Request $request, PertanyaanTamu $pertanyaan): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['baru', 'dibalas', 'tidak_lanjut'])],
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
        ], [
            'catatan_admin.max' => 'Catatan admin maksimal 1.000 karakter.',
        ]);

        // Pertanyaan yang sudah menjadi permintaan booking tidak diubah statusnya; hanya catatannya.
        if ($pertanyaan->status === 'jadi_booking') {
            unset($data['status']);
        }

        $pertanyaan->update($data);

        return back()->with('sukses', 'Pertanyaan tamu diperbarui.');
    }
}
