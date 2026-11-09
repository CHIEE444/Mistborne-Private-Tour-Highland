<?php

namespace App\Providers;

use App\Kontrak\PengirimWhatsApp;
use App\Layanan\WhatsApp\PengirimWhatsAppTautan;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\DevCommands;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PengirimWhatsApp::class, PengirimWhatsAppTautan::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Pass upload limits directly to the built-in server process. `artisan serve`
            // starts a child PHP process that does not inherit CLI `-d` overrides.
            DevCommands::register('php -d upload_max_filesize=16M -d post_max_size=20M -S 127.0.0.1:8000 -t public server.php', 'server');
        }

        ResetPassword::createUrlUsing(fn (User $user, string $token): string => route('admin.autentikasi.atur-ulang-sandi', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]));
        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            $tautan = route('admin.autentikasi.atur-ulang-sandi', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
            $kedaluwarsaMenit = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');
            $dataEmail = [
                'nama' => $user->name,
                'tautan' => $tautan,
                'kedaluwarsaMenit' => $kedaluwarsaMenit,
            ];

            return (new MailMessage)
                ->subject('Atur ulang kata sandi akun MISTBORNE')
                ->view('mail.atur-ulang-sandi', $dataEmail)
                ->text('mail.atur-ulang-sandi-teks', $dataEmail);
        });

        // Komponen anonim kini tinggal di resources/views/komponen (struktur folder
        // bahasa Indonesia): x-tata-letak.navbar → komponen/tata-letak/navbar.blade.php, dst.
        Blade::anonymousComponentPath(resource_path('views/komponen'));

        // Komponen admin memakai folder default Laravel (resources/views/components),
        // jadi <x-tata-letak.admin> dan <x-admin.tombol> otomatis terdeteksi tanpa registrasi.

        // URL resource admin berbahasa Indonesia: /admin/pesanan/buat, /admin/pesanan/{pesanan}/ubah.
        Route::resourceVerbs([
            'create' => 'buat',
            'edit' => 'ubah',
        ]);
    }
}
