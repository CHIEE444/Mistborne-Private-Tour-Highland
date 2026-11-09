<?php

namespace App\Http\Requests\Admin\Autentikasi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MasukRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Coba masuk dengan kredensial pada request. Melempar ValidationException bila gagal
     * atau bila IP+email ini sudah melewati batas 5 percobaan (terkunci 15 menit).
     */
    public function autentikasi(): void
    {
        $this->pastikanTidakDibatasi();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('ingat'))) {
            RateLimiter::hit($this->kunciPembatas(), 900);

            throw ValidationException::withMessages([
                'email' => $this->pesanGagal(),
            ]);
        }

        RateLimiter::clear($this->kunciPembatas());
    }

    protected function pastikanTidakDibatasi(): void
    {
        if (! RateLimiter::tooManyAttempts($this->kunciPembatas(), 5)) {
            return;
        }

        $menit = (int) ceil(RateLimiter::availableIn($this->kunciPembatas()) / 60);

        throw ValidationException::withMessages([
            'email' => __('admin.autentikasi.terkunci', ['menit' => $menit]),
        ]);
    }

    protected function pesanGagal(): string
    {
        $sisa = max(0, 5 - RateLimiter::attempts($this->kunciPembatas()));

        return $sisa > 0
            ? __('admin.autentikasi.gagal_dengan_sisa', ['sisa' => $sisa])
            : __('admin.autentikasi.gagal');
    }

    public function kunciPembatas(): string
    {
        return Str::lower((string) $this->input('email')).'|'.$this->ip();
    }
}
