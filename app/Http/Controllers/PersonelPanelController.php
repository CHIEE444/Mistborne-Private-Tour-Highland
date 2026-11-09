<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Http\Requests\Admin\Autentikasi\MasukRequest;
use App\Models\AktivitasPersonel;
use App\Models\Pesanan;
use App\Models\User;
use App\Notifications\TanggapanPenugasanNotification;
use App\Notifications\TripSelesaiNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PersonelPanelController extends Controller
{
    public function masuk(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user && mb_strtolower(trim((string) $user->jabatan)) === 'guide' && $user->personel) {
            return redirect()->route('personel.dashboard');
        }

        return view('personel.masuk');
    }

    public function prosesMasuk(MasukRequest $request): RedirectResponse
    {
        $request->autentikasi();
        $user = $request->user();
        $personel = $user?->personel;
        $jabatan = mb_strtolower(trim((string) ($user?->jabatan ?? '')));

        if (
            $jabatan !== 'guide'
            || ! $personel
            || mb_strtolower(trim((string) $personel->peran)) !== $jabatan
        ) {
            Auth::logout();

            return back()->withErrors(['email' => 'Akun ini tidak terdaftar sebagai guide.']);
        }

        // Sesi yang sama kadang menyimpan intended URL dari halaman publik.
        // Setelah login personel, selalu masuk ke panel Guide.
        $request->session()->forget('url.intended');
        $request->session()->regenerate();

        AktivitasPersonel::catat($personel, 'masuk', 'Masuk ke panel Guide.');

        return redirect()->route('personel.dashboard');
    }

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $personel = $user->personel;
        $kolom = 'guide_personel_id';
        $statusKolom = 'status_konfirmasi_guide';

        $pesanan = Pesanan::query()
            ->where($kolom, $personel->id)
            ->whereIn('status', [
                BookingStatus::Dihubungi->value,
                BookingStatus::Payment->value,
                BookingStatus::HoldSchedule->value,
                BookingStatus::Completed->value,
            ])
            ->orderByDesc('tanggal_trip')
            ->get();

        return view('personel.dashboard', [
            'personel' => $personel,
            'pesanan' => $pesanan,
            'statusKolom' => $statusKolom,
            'ringkasan' => [
                'menunggu' => $pesanan->where('status_konfirmasi_guide', 'menunggu')->count(),
                'mendatang' => $pesanan->where('status_konfirmasi_guide', 'diterima')->whereNull('guide_trip_selesai_at')->count(),
                'selesai' => $pesanan->whereNotNull('guide_trip_selesai_at')->count(),
            ],
        ]);
    }

    public function aktivitas(Request $request): View
    {
        $personel = $request->user()->personel;

        return view('personel.aktivitas', [
            'personel' => $personel,
            'aktivitas' => AktivitasPersonel::query()
                ->with('pesanan:id,kode')
                ->where('personel_id', $personel->id)
                ->latest('created_at')
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function profil(Request $request): View
    {
        return view('personel.profil', ['personel' => $request->user()->personel]);
    }

    /** Guide hanya boleh mengganti kata sandi sendiri; data identitas, kontak, dan SIM hanya bisa diubah admin. */
    public function ubahSandi(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'sandi_lama' => ['required', 'string'],
            'sandi_baru' => ['required', 'string', 'confirmed', Password::min(8)],
        ], [], ['sandi_lama' => 'kata sandi lama', 'sandi_baru' => 'kata sandi baru']);

        if (! Hash::check($data['sandi_lama'], $user->password)) {
            throw ValidationException::withMessages(['sandi_lama' => 'Kata sandi lama tidak sesuai.']);
        }

        $user->update(['password' => $data['sandi_baru']]);
        AktivitasPersonel::catat($user->personel, 'sandi', 'Kata sandi akun diganti.');

        return redirect()->route('personel.profil')->with('sukses', 'Kata sandi berhasil diganti.');
    }

    public function tanggapi(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $request->validate(['jawaban' => ['required', 'in:diterima,ditolak']]);

        $user = $request->user();
        $personel = $user->personel;
        $kolomId = 'guide_personel_id';
        $kolomStatus = 'status_konfirmasi_guide';
        $kolomWaktu = 'guide_dikonfirmasi_at';
        $jawaban = $request->string('jawaban')->toString();

        $waktu = now();
        $pesanan = DB::transaction(function () use ($pesanan, $personel, $kolomId, $kolomStatus, $kolomWaktu, $jawaban, $waktu): Pesanan {
            $pesanan = Pesanan::query()->whereKey($pesanan->id)->lockForUpdate()->firstOrFail();

            abort_unless((int) $pesanan->{$kolomId} === (int) $personel->id, 403);
            abort_if(in_array($pesanan->status, [BookingStatus::Dibatalkan, BookingStatus::Completed], true), 409, 'Pesanan ini sudah ditutup.');
            abort_unless($pesanan->{$kolomStatus} === 'menunggu', 409, 'Penugasan ini sudah ditanggapi.');

            $pesanan->update([
                $kolomStatus => $jawaban,
                $kolomWaktu => $waktu,
            ]);

            AktivitasPersonel::catat($personel, $jawaban, ($jawaban === 'diterima' ? 'Menerima' : 'Menolak')." tugas {$pesanan->kode} ({$pesanan->nama_perjalanan}).", $pesanan);

            return $pesanan;
        });

        User::query()
            ->whereRaw('LOWER(jabatan) IN (?, ?)', ['admin', 'admin operasional'])
            ->each(fn (User $admin) => $admin->notify(new TanggapanPenugasanNotification(
                pesanan: $pesanan,
                personel: $personel,
                jawaban: $jawaban,
                waktu: $waktu,
            )));

        return back()->with('sukses', $jawaban === 'diterima'
            ? 'Penugasan berhasil dikonfirmasi.'
            : 'Penugasan ditolak. Admin akan menghubungi Anda untuk informasi selanjutnya.');
    }

    public function selesaikanTrip(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $personel = $request->user()->personel;
        $kolomId = 'guide_personel_id';
        $kolomKonfirmasi = 'status_konfirmasi_guide';
        $kolomSelesai = 'guide_trip_selesai_at';

        abort_unless((int) $pesanan->{$kolomId} === (int) $personel->id, 403);

        $waktuSelesai = now();
        try {
            $pesanan = DB::transaction(function () use ($pesanan, $personel, $kolomId, $kolomKonfirmasi, $kolomSelesai, $waktuSelesai): Pesanan {
                $pesanan = Pesanan::query()->whereKey($pesanan->id)->lockForUpdate()->firstOrFail();

                if ((int) $pesanan->{$kolomId} !== (int) $personel->id) {
                    abort(403);
                }

                if ($pesanan->{$kolomKonfirmasi} !== 'diterima') {
                    throw ValidationException::withMessages(['trip' => 'Terima tugas terlebih dahulu sebelum menandai trip selesai.']);
                }

                $tanggalAkhirTrip = $pesanan->tanggal_selesai ?? $pesanan->tanggal_trip;
                if ($tanggalAkhirTrip->isFuture()) {
                    throw ValidationException::withMessages(['trip' => 'Trip hanya dapat ditandai selesai setelah tanggal perjalanan berakhir.']);
                }

                if ($pesanan->{$kolomSelesai}) {
                    throw ValidationException::withMessages(['trip' => 'Trip ini sudah ditandai selesai.']);
                }

                if ($pesanan->status === BookingStatus::Dibatalkan) {
                    throw ValidationException::withMessages(['trip' => 'Pesanan yang dibatalkan tidak dapat ditandai selesai.']);
                }

                $pesanan->update([$kolomSelesai => $waktuSelesai]);
                AktivitasPersonel::catat($personel, 'trip_selesai', "Menandai trip {$pesanan->kode} ({$pesanan->nama_perjalanan}) selesai.", $pesanan);

                return $pesanan->fresh();
            });
        } catch (ValidationException $exception) {
            return redirect()->route('personel.dashboard')->withErrors($exception->errors());
        }

        User::query()
            ->whereRaw('LOWER(jabatan) IN (?, ?)', ['admin', 'admin operasional'])
            ->each(fn (User $admin) => $admin->notify(new TripSelesaiNotification(
                pesanan: $pesanan,
                personel: $personel,
                waktu: $waktuSelesai,
            )));

        return redirect()->route('personel.dashboard')->with('sukses', 'Trip ditandai selesai. Notifikasi sudah dikirim ke dashboard admin.');
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('personel.masuk');
    }
}
