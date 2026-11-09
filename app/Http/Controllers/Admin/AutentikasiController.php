<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Autentikasi\MasukRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AutentikasiController extends Controller
{
    public function create(): View
    {
        return view('admin.autentikasi.masuk');
    }

    public function store(MasukRequest $request): RedirectResponse
    {
        $request->autentikasi();

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard.indeks'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.autentikasi.masuk');
    }

    public function lupaSandi(): View
    {
        return view('admin.autentikasi.lupa-sandi');
    }

    public function kirimTautanReset(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::broker('users')->sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function aturUlangSandi(Request $request, string $token): View
    {
        return view('admin.autentikasi.atur-ulang-sandi', [
            'token' => $token,
            'email' => $request->string('email'),
        ]);
    }

    public function simpanSandiBaru(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill(['password' => $password])->save();
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('admin.autentikasi.masuk')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
