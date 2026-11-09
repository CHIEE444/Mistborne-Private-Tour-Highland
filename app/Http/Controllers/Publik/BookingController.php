<?php

namespace App\Http\Controllers\Publik;

use App\Bantuan\AturanNama;
use App\Bantuan\JamPenjemputan;
use App\Bantuan\NomorWhatsApp;
use App\Enums\StatusPenginapan;
use App\Http\Controllers\Controller;
use App\Kontrak\PengirimWhatsApp;
use App\Layanan\KapasitasTripLayanan;
use App\Models\Pengaturan;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\User;
use App\Notifications\PermintaanPesananMasukNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Booking dari kartu destinasi: GET menampilkan formulir tanpa login, POST menyimpan permintaan lalu membuka WhatsApp admin.
 */
class BookingController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $nama = (string) $request->query('destination', '');

        abort_if($nama === '', 404);

        $journey = $this->journeyMana($nama);

        abort_if($journey === null, 404);

        // Journey kustom tidak punya tanggal tetap: tamu menentukan jadwalnya di form Rancang Trip Kustom.
        if ($journey['jadwal_fleksibel']) {
            return redirect()->route('rancang-trip-kustom');
        }

        $penggunaMasuk = $request->user();
        $penggunaAdmin = $this->adalahAdmin($penggunaMasuk);

        // Admin yang sedang menguji situs publik boleh membuka form, tetapi identitas admin tidak dipakai sebagai tamu.
        $penggunaTamu = $penggunaAdmin ? null : $penggunaMasuk;

        // Jumlah awal mengikuti pax yang tertera di journey (dibatasi kapasitas mobil), bukan 1 orang.
        $form = [
            'pax' => max(1, (int) ($journey['maks_orang'] ?? 1)),
        ];

        $perjalanan = Perjalanan::query()->where('nama', $journey['name'])->first();

        return view('pages.booking', [
            'hotel' => $perjalanan?->hotelPublik() ?? [],
            // Kolom tanggal hanya berisi Tanggal Tersedia journey ini beserta sisa slotnya (kuota journey dan guide bebas).
            'daftarTanggal' => $perjalanan ? app(KapasitasTripLayanan::class)->daftarTanggal($perjalanan) : collect(),
            'journey' => $journey,
            'user' => $penggunaTamu,
            'form' => $form,
            'tokenBooking' => (string) Str::uuid(),
            'waUrl' => '#', // form yang membuka WA, bukan panel kiri
        ]);
    }

    public function store(Request $request, PengirimWhatsApp $pengirimWhatsApp, KapasitasTripLayanan $kapasitasTrip): RedirectResponse
    {
        $penggunaMasuk = $request->user();
        $penggunaAdmin = $this->adalahAdmin($penggunaMasuk);

        $tokenBooking = (string) $request->input('token_booking', '');
        if (Str::isUuid($tokenBooking)) {
            $permintaanSebelumnya = PermintaanPesanan::query()->where('token_booking', $tokenBooking)->first();

            if ($permintaanSebelumnya) {
                abort_unless($penggunaAdmin || $permintaanSebelumnya->user_web_id === $penggunaMasuk?->id, 404);

                return redirect()->away($this->tautanWhatsApp($permintaanSebelumnya, $pengirimWhatsApp));
            }
        }

        $request->merge([
            'whatsapp' => NomorWhatsApp::lokal($request->input('whatsapp')),
            'nama_lengkap' => AturanNama::rapikan($request->input('nama_lengkap')),
            'waktu_pickup' => JamPenjemputan::normalisasi($request->input('waktu_pickup')),
        ]);

        $data = $request->validate([
            'token_booking' => ['required', 'uuid'],
            'destination' => ['required', 'string'],
            'pax' => ['required', 'integer', 'min:1', 'max:50'],
            'nama_lengkap' => AturanNama::aturan(),
            'whatsapp' => ['required', 'string', 'regex:/^08[0-9]{7,12}$/'],
            'tanggal_trip' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now()->addDays(7)->toDateString(), 'before_or_equal:'.now()->addDays(7)->addYear()->toDateString()],
            'lokasi_pickup' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'waktu_pickup' => ['nullable', 'date_format:H:i'],
            'paket_opsional' => ['nullable', 'string', 'max:255'],
            'permintaan_khusus' => ['nullable', 'string', 'max:1000'],
        ], [
            'destination.required' => __('Perjalanan wajib dipilih.'),
            'token_booking.required' => __('Formulir kedaluwarsa. Muat ulang halaman lalu kirim kembali.'),
            'token_booking.uuid' => __('Formulir tidak valid. Muat ulang halaman lalu coba kembali.'),
            'destination.string' => __('Perjalanan yang dipilih tidak valid.'),
            'pax.required' => __('Jumlah wisatawan wajib diisi.'),
            'pax.integer' => __('Jumlah wisatawan harus berupa angka bulat.'),
            'pax.min' => __('Jumlah wisatawan minimal 1 orang.'),
            'pax.max' => __('Jumlah wisatawan melebihi kapasitas perjalanan.'),
            'nama_lengkap.required' => __('Nama lengkap wajib diisi.'),
            'nama_lengkap.max' => __('Nama lengkap maksimal 255 karakter.'),
            'nama_lengkap.not_regex' => __('Nama tidak boleh memuat karakter < atau >.'),
            'nama_lengkap.min' => __('Nama lengkap minimal 3 karakter.'),
            'nama_lengkap.regex' => __('Nama lengkap harus memuat huruf dan tidak boleh berisi simbol.'),
            'whatsapp.required' => __('Nomor WhatsApp wajib diisi.'),
            'whatsapp.regex' => __('Masukkan nomor WhatsApp dengan angka saja, diawali 08 atau +62, contoh 081234567890.'),
            'tanggal_trip.required' => __('Tanggal perjalanan wajib dipilih.'),
            'tanggal_trip.date_format' => __('Tanggal perjalanan tidak valid.'),
            'tanggal_trip.after_or_equal' => __('Tanggal trip harus minimal tujuh hari dari hari ini (H-7).'),
            'tanggal_trip.before_or_equal' => __('Tanggal trip hanya dapat dipilih sampai satu tahun ke depan.'),
            'lokasi_pickup.required' => __('Alamat atau titik jemput wajib diisi.'),
            'lokasi_pickup.max' => __('Alamat atau titik jemput maksimal 255 karakter.'),
            'lokasi_pickup.not_regex' => __('Lokasi jemput tidak boleh memuat karakter < atau >.'),
            'waktu_pickup.date_format' => __('Format jam jemput tidak valid.'),
            'paket_opsional.max' => __('Catatan tambahan maksimal 255 karakter.'),
            'permintaan_khusus.max' => __('Permintaan khusus maksimal 1.000 karakter.'),
        ]);

        // Sistem all-inclusive: penginapan dan tiket masuk diurus MISTBORNE, jadi tamu tidak mengisi data hotel.
        $data = [...$data, 'status_penginapan' => StatusPenginapan::Diurus->value, 'nama_hotel' => null, 'alamat_hotel' => null];

        $journey = $this->journeyMana($data['destination']);

        abort_if($journey === null, 404);

        $perjalanan = Perjalanan::query()->where('nama', $journey['name'])->first();
        abort_if($perjalanan === null, 404);

        // Mobil ditentukan sistem saat pesanan dibuat, jadi batas tamu hanya mengikuti kapasitas journey.
        $kapasitas = (int) ($journey['maks_orang'] ?? 15);
        if ((int) $data['pax'] > $kapasitas) {
            throw ValidationException::withMessages([
                'pax' => __('Jumlah wisatawan melebihi kapasitas perjalanan (:kapasitas orang).', ['kapasitas' => $kapasitas]),
            ]);
        }

        // Tanggal harus salah satu Tanggal Tersedia journey ini dan masih punya slot. Slot baru dikunci saat admin membuat pesanan.
        $slot = $kapasitasTrip->cek($perjalanan, $data['tanggal_trip']);
        if (! $slot['tersedia']) {
            throw ValidationException::withMessages(['tanggal_trip' => __($slot['alasan'])]);
        }

        $penggunaWeb = $penggunaAdmin ? null : $penggunaMasuk;

        [$permintaan, $baru] = DB::transaction(function () use ($perjalanan, $data, $penggunaWeb, $penggunaAdmin): array {
            // Kirim ulang dengan token yang sama (jaringan lambat, klik ganda) mengembalikan permintaan awal.
            $permintaanSebelumnya = PermintaanPesanan::query()->where('token_booking', $data['token_booking'])->lockForUpdate()->first();
            if ($permintaanSebelumnya) {
                abort_unless($penggunaAdmin || $permintaanSebelumnya->user_web_id === $penggunaWeb?->id, 404);

                return [$permintaanSebelumnya, false];
            }

            $permintaan = PermintaanPesanan::query()->create([
                'token' => Str::random(64),
                'token_booking' => $data['token_booking'],
                'user_web_id' => $penggunaWeb?->id,
                'sumber' => 'booking',
                'whatsapp' => $data['whatsapp'],
                'status' => 'menunggu_tinjauan',
                'perjalanan_id' => $perjalanan->id,
                'nama_lengkap' => $data['nama_lengkap'],
                'tanggal_trip' => $data['tanggal_trip'],
                'jumlah_wisatawan' => (int) $data['pax'],
                'nama_hotel' => $data['nama_hotel'],
                'alamat_hotel' => $data['alamat_hotel'] ?? null,
                'status_penginapan' => $data['status_penginapan'],
                'lokasi_pickup' => $data['lokasi_pickup'],
                'waktu_pickup' => $data['waktu_pickup'] ?? null,
                'paket_opsional' => $data['paket_opsional'] ?? null,
                'permintaan_khusus' => $data['permintaan_khusus'] ?? null,
                'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru(),
            ]);

            return [$permintaan, true];
        });

        if ($baru) {
            $permintaan->load('perjalanan');
            User::query()
                ->whereRaw('LOWER(jabatan) IN (?, ?)', ['admin', 'admin operasional'])
                ->each(fn (User $admin) => $admin->notify(new PermintaanPesananMasukNotification($permintaan)));
        }

        // Redirect langsung ke WhatsApp admin (browser terbuka dengan draft pesan).
        return redirect()->away($this->tautanWhatsApp($permintaan, $pengirimWhatsApp));
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function tautanWhatsApp(PermintaanPesanan $permintaan, PengirimWhatsApp $pengirimWhatsApp): string
    {
        $pesan = [
            __('Halo Mistborne, saya sudah mengisi formulir booking di website.'),
            '',
            __('Perjalanan').': '.$permintaan->perjalanan?->nama,
            __('Tanggal').': '.$this->tanggalIndo($permintaan->tanggal_trip->toDateString()),
            __('Jumlah Pax').': '.$permintaan->jumlah_wisatawan,
            __('Nama').': '.$permintaan->nama_lengkap,
            __('WhatsApp').': '.$permintaan->whatsapp,
            __('Penginapan').': '.match ($permintaan->status_penginapan) {
                StatusPenginapan::Sudah => $permintaan->nama_hotel ?: '-',
                StatusPenginapan::Diurus => __('All-inclusive (penginapan dan tiket masuk diurus MISTBORNE)'),
                default => __('Belum punya, mohon rekomendasi penginapan'),
            },
            ...(filled($permintaan->alamat_hotel) ? [__('Alamat hotel').': '.$permintaan->alamat_hotel] : []),
            __('Lokasi jemput').': '.$permintaan->lokasi_pickup,
        ];

        if (filled($permintaan->waktu_pickup)) {
            $pesan[] = __('Jam jemput').': '.substr((string) $permintaan->waktu_pickup, 0, 5).' WIB';
        }

        if (filled($permintaan->paket_opsional)) {
            $pesan[] = __('Catatan tambahan').': '.$permintaan->paket_opsional;
        }

        if (filled($permintaan->permintaan_khusus)) {
            $pesan[] = __('Permintaan Khusus').': '.$permintaan->permintaan_khusus;
        }

        $pesan[] = '';
        $pesan[] = __('Mohon konfirmasi ketersediaan tanggal dan langkah selanjutnya. Terima kasih.');

        return $pengirimWhatsApp->buatTautan(
            (string) Pengaturan::current()->nomor_whatsapp_admin,
            implode("\n", $pesan),
        );
    }

    private function tanggalIndo(string $iso): string
    {
        $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        [$y, $m, $d] = explode('-', $iso);

        return "{$d} {$bulan[(int) $m - 1]} {$y}";
    }

    private function journeyMana(string $nama): ?array
    {
        // Satu sumber data (sama seperti beranda) — cocokkan nama journey (ASCII).
        $semua = Perjalanan::query()->where('template_pesanan_kustom', false)->orderBy('urutan')->get();

        foreach ($semua as $perjalanan) {
            if (strcasecmp($perjalanan->nama, $nama) === 0) {
                $journey = $perjalanan->kePublik();
                $journey['maks_orang'] = $perjalanan->maks_orang ?? 15;

                return $journey;
            }
        }

        return null;
    }

    private function adalahAdmin(?User $user): bool
    {
        return in_array(mb_strtolower(trim((string) $user?->jabatan)), ['admin', 'admin operasional'], true);
    }
}
