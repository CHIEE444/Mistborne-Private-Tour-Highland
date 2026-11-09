<?php

namespace App\Http\Controllers\Publik;

use App\Bantuan\AturanNama;
use App\Bantuan\JamPenjemputan;
use App\Bantuan\NomorWhatsApp;
use App\Enums\StatusPenginapan;
use App\Http\Controllers\Controller;
use App\Kontrak\PengirimWhatsApp;
use App\Layanan\KapasitasTripLayanan;
use App\Layanan\PesanWhatsAppLayanan;
use App\Models\Pengaturan;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\PertanyaanTamu;
use App\Models\User;
use App\Notifications\PermintaanPesananMasukNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FormulirPesananController extends Controller
{
    public function show(string $token, PengirimWhatsApp $pengirimWhatsApp, PesanWhatsAppLayanan $pesan, KapasitasTripLayanan $kapasitas): View
    {
        $permintaan = $this->cariPermintaan($token);

        $daftarJourney = Perjalanan::query()->where('template_pesanan_kustom', false)->orderBy('urutan')->get(['id', 'nama', 'slug', 'tone', 'durasi_hari', 'maks_orang', 'harga', 'template_pesanan_kustom', 'kendaraan_id', 'alasan_kendaraan']);

        return view('pages.form-pesanan', [
            'permintaan' => $permintaan,
            // Setelah tamu mengirim pembaruan, ringkasannya bisa langsung dikonfirmasi ke admin lewat WhatsApp.
            'tautanKonfirmasiWa' => session('terkirim') && filled($permintaan->nama_lengkap)
                ? $pengirimWhatsApp->buatTautan((string) Pengaturan::current()->nomor_whatsapp_admin, $pesan->pembaruanDariTamu($permintaan->loadMissing('perjalanan')))
                : null,
            'journey' => $daftarJourney,
            'hotelPerJourney' => $daftarJourney->mapWithKeys(fn (Perjalanan $j): array => [$j->id => $j->hotelPublik()])->all(),
            'batasPerJourney' => $daftarJourney->mapWithKeys(fn (Perjalanan $j): array => [$j->id => (int) ($j->maks_orang ?? 15)])->all(),
            // Pilihan tanggal per journey = Tanggal Tersedia beserta sisa slotnya (sama dengan formulir booking di website).
            'tanggalPerJourney' => $daftarJourney->mapWithKeys(fn (Perjalanan $j): array => [$j->id => $kapasitas->daftarTanggal($j)
                ->map(fn (array $opsi): array => [...$opsi, 'teks' => Carbon::parse($opsi['tanggal'])->translatedFormat('l, d F Y').' · '.$opsi['label']])
                ->all()])->all(),
        ]);
    }

    public function store(Request $request, string $token, KapasitasTripLayanan $kapasitasTrip): RedirectResponse
    {
        $permintaan = $this->cariPermintaan($token);

        abort_unless($permintaan->dapatDiubahTamu(), 410, 'Tautan formulir ini sudah tidak aktif. Hubungi admin MISTBORNE untuk meminta tautan baru.');

        $request->merge([
            'whatsapp' => NomorWhatsApp::lokal($request->input('whatsapp')),
            'nama_lengkap' => AturanNama::rapikan($request->input('nama_lengkap')),
            'waktu_pickup' => JamPenjemputan::normalisasi($request->input('waktu_pickup')),
        ]);

        $data = $request->validate([
            'nama_lengkap' => AturanNama::aturan(),
            'whatsapp' => ['required', 'string', 'regex:/^08[0-9]{7,12}$/'],
            'perjalanan_id' => ['required', 'integer', Rule::exists('perjalanan', 'id')->where('template_pesanan_kustom', false)->whereNull('deleted_at')],
            'tanggal_trip' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now()->addDays(7)->toDateString(), 'before_or_equal:'.now()->addDays(7)->addYear()->toDateString()],
            'jumlah_wisatawan' => ['required', 'integer', 'min:1', 'max:50'],
            'lokasi_pickup' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'waktu_pickup' => ['nullable', 'date_format:H:i'],
            'paket_opsional' => ['nullable', 'string', 'max:255'],
            'permintaan_khusus' => ['nullable', 'string', 'max:1000'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max' => 'Nama lengkap maksimal 255 karakter.',
            'nama_lengkap.not_regex' => 'Nama tidak boleh memuat karakter < atau >.',
            'nama_lengkap.min' => 'Nama lengkap minimal 3 karakter.',
            'nama_lengkap.regex' => 'Nama lengkap harus memuat huruf dan tidak boleh berisi simbol.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Masukkan nomor WhatsApp dengan angka saja, diawali 08 atau +62, contoh 081234567890.',
            'perjalanan_id.required' => 'Journey wajib dipilih.',
            'perjalanan_id.exists' => 'Journey yang dipilih tidak tersedia.',
            'tanggal_trip.required' => 'Tanggal perjalanan wajib dipilih.',
            'tanggal_trip.date_format' => 'Tanggal perjalanan tidak valid.',
            'tanggal_trip.after_or_equal' => 'Tanggal perjalanan minimal tujuh hari dari hari ini (H-7).',
            'tanggal_trip.before_or_equal' => 'Tanggal perjalanan hanya dapat dipilih sampai satu tahun ke depan.',
            'jumlah_wisatawan.required' => 'Jumlah peserta wajib diisi.',
            'jumlah_wisatawan.integer' => 'Jumlah peserta harus berupa angka bulat.',
            'jumlah_wisatawan.min' => 'Jumlah peserta minimal satu orang.',
            'jumlah_wisatawan.max' => 'Jumlah peserta maksimal 50 orang.',
            'lokasi_pickup.required' => 'Lokasi penjemputan wajib diisi.',
            'lokasi_pickup.max' => 'Lokasi penjemputan maksimal 255 karakter.',
            'lokasi_pickup.not_regex' => 'Lokasi jemput tidak boleh memuat karakter < atau >.',
            'waktu_pickup.date_format' => 'Jam penjemputan tidak valid.',
            'paket_opsional.max' => 'Catatan tambahan maksimal 255 karakter.',
            'permintaan_khusus.max' => 'Permintaan khusus maksimal 1.000 karakter.',
        ]);

        // Sistem all-inclusive: penginapan dan tiket masuk diurus MISTBORNE, jadi tamu tidak mengisi data hotel.
        $data = [...$data, 'status_penginapan' => StatusPenginapan::Diurus->value, 'nama_hotel' => null, 'alamat_hotel' => null];

        $perjalanan = Perjalanan::query()
            ->whereKey($data['perjalanan_id'])
            ->where('template_pesanan_kustom', false)
            ->first();
        if (! $perjalanan) {
            throw ValidationException::withMessages(['perjalanan_id' => 'Journey yang dipilih sudah tidak tersedia.']);
        }

        // Mobil ditentukan sistem saat pesanan dibuat, jadi batas peserta hanya mengikuti kapasitas journey.
        $kapasitas = (int) ($perjalanan->maks_orang ?? 15);
        if ((int) $data['jumlah_wisatawan'] > $kapasitas) {
            throw ValidationException::withMessages(['jumlah_wisatawan' => "Jumlah peserta melebihi kapasitas maksimal journey ini ({$kapasitas} tamu). Admin akan memeriksa ketersediaan sebelum mengonfirmasi."]);
        }

        // Sama seperti form booking: tanggal harus salah satu Tanggal Tersedia journey ini dan masih punya slot.
        $slot = $kapasitasTrip->cek($perjalanan, $data['tanggal_trip']);
        if (! $slot['tersedia']) {
            throw ValidationException::withMessages(['tanggal_trip' => $slot['alasan']]);
        }

        DB::transaction(function () use ($permintaan, $data): void {
            $terkunci = PermintaanPesanan::query()->whereKey($permintaan->id)->lockForUpdate()->firstOrFail();
            abort_unless($terkunci->dapatDiubahTamu(), 410, 'Tautan formulir ini sudah tidak aktif.');

            $perluNotifikasi = $terkunci->status !== 'menunggu_tinjauan';

            $terkunci->update([
                ...$data,
                'status' => 'menunggu_tinjauan',
                'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru(),
            ]);

            PertanyaanTamu::query()
                ->where('permintaan_pesanan_id', $terkunci->id)
                ->whereIn('status', ['baru', 'dibalas'])
                ->update(['status' => 'jadi_booking']);

            if ($perluNotifikasi) {
                $terkini = $terkunci->fresh(['perjalanan']);
                User::query()
                    ->whereRaw('LOWER(jabatan) IN (?, ?)', ['admin', 'admin operasional'])
                    ->each(fn (User $admin) => $admin->notify(new PermintaanPesananMasukNotification($terkini)));
            }
        });

        return redirect()->route('form-pesanan.tampil', $token)->with('terkirim', true);
    }

    private function cariPermintaan(string $token): PermintaanPesanan
    {
        $permintaan = PermintaanPesanan::query()->where('token', $token)->firstOrFail();

        abort_if($permintaan->token_berlaku_sampai->isPast() && ! in_array($permintaan->status, ['disetujui', 'tidak_jadi'], true), 410, 'Tautan formulir ini sudah kedaluwarsa. Hubungi admin MISTBORNE untuk meminta tautan baru.');

        return $permintaan;
    }
}
