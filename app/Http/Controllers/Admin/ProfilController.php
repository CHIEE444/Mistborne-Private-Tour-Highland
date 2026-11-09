<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Profil\PerbaruiProfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function edit(): View
    {
        return view('admin.profil.ubah', [
            'user' => auth()->user(),
        ]);
    }

    public function update(PerbaruiProfilRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->safe()->only(['name', 'jabatan']));

        if ($request->hasFile('foto')) {
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }

            $user->foto = $request->file('foto')->store('admin/profil', 'public');
        }

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();

        return back()->with('sukses', __('admin.profil.berhasil_diperbarui'));
    }
}
