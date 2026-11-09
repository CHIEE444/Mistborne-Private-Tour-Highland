<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pengaturan\PerbaruiPengaturanRequest;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function index(): View
    {
        return view('admin.pengaturan.indeks', [
            'pengaturan' => Pengaturan::current(),
        ]);
    }

    public function update(PerbaruiPengaturanRequest $request): RedirectResponse
    {
        Pengaturan::current()->update($request->validated());

        return back()->with('sukses', 'Pengaturan berhasil diperbarui dan diterapkan ke seluruh sistem operasional.');
    }
}
