<?php

namespace App\Http\Controllers\Admin;

use App\Aksi\Pesanan\BatalkanPesanan;
use App\Aksi\Pesanan\BuatPesananManual;
use App\Aksi\Pesanan\CatatPembayaranDiterima;
use App\Aksi\Pesanan\GantiJadwalPesanan;
use App\Aksi\Pesanan\KonfirmasiPesanan;
use App\Aksi\Pesanan\SelesaikanPesanan;
use App\Aksi\Pesanan\SimpanOperasionalPesanan;
use App\Aksi\Pesanan\SinkronkanTahapPesanan;
use App\Aksi\Pesanan\TandaiLunas;
use App\Aksi\Pesanan\ToggleHoldPesanan;
use App\Bantuan\AturanPenginapan;
use App\Bantuan\PerubahanDataTamu;
use App\Enums\BookingStatus;
use App\Enums\StatusPenginapan;
use App\Enums\StatusReservasiHotel;
use App\Enums\SumberPesanan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pesanan\BatalkanPesananRequest;
use App\Http\Requests\Admin\Pesanan\CatatPembayaranDiterimaRequest;
use App\Http\Requests\Admin\Pesanan\GantiJadwalPesananRequest;
use App\Http\Requests\Admin\Pesanan\KonfirmasiPesananRequest;
use App\Http\Requests\Admin\Pesanan\PerbaruiOperasionalRequest;
use App\Http\Requests\Admin\Pesanan\PerbaruiPesananRequest;
use App\Http\Requests\Admin\Pesanan\SimpanHotelPesananRequest;
use App\Http\Requests\Admin\Pesanan\SimpanPembayaranHotelRequest;
use App\Http\Requests\Admin\Pesanan\SimpanPesananRequest;
use App\Layanan\AlurReservasiHotel;
use App\Layanan\BatasBayarLayanan;
use App\Layanan\BiayaJadwalLayanan;
use App\Layanan\EmailPenugasanPersonelLayanan;
use App\Layanan\KapasitasTripLayanan;
use App\Models\Hotel;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use App\Models\Ulasan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PesananController extends Controller
{
    public function destroy(Pesanan $pesanan, EmailPenugasanPersonelLayanan $email): RedirectResponse
    {
        $this->authorize('delete', $pesanan);

        // Guide yang masih memegang trip ini dikabari lewat email sebelum datanya hilang.
        if ($pesanan->guide_personel_id && ! $pesanan->terkunci()) {
            $email->kirimPembatalan($pesanan);
        }

        $berkas = DB::transaction(function () use ($pesanan): array {
            $pesanan = Pesanan::query()->whereKey($pesanan->id)->lockForUpdate()->firstOrFail();
            $ulasan = Ulasan::withTrashed()->where('pesanan_id', $pesanan->id)->first();
            $berkas = array_filter([
                ['disk' => 'local', 'path' => $pesanan->bukti_pembayaran],
                ['disk' => 'public', 'path' => $ulasan?->video_path],
            ], fn (array $file): bool => filled($file['path']));

            $pesanan->delete();

            return $berkas;
        });

        foreach ($berkas as $berkasItem) {
            Storage::disk($berkasItem['disk'])->delete($berkasItem['path']);
        }

        return redirect()->route('admin.pesanan.indeks')->with('sukses', 'Data tamu dan pesanan terkait berhasil dihapus.');
    }

    public function index(Request $request, BatasBayarLayanan $batasBayar, SinkronkanTahapPesanan $sinkron): View
    {
        $sinkron->handleBilaPerlu();

        $filter = $request->validate([
            'tahap' => ['nullable', Rule::in(array_keys(Pesanan::TAHAP))],
            'status' => ['nullable', Rule::in(array_map(fn (BookingStatus $status): string => $status->value, BookingStatus::cases()))],
            'cari' => ['nullable', 'string', 'max:100'],
            'journey' => ['nullable', 'integer', 'exists:perjalanan,id'],
            'penugasan' => ['nullable', Rule::in(['ditolak', 'menunggu', 'belum'])],
            'penginapan' => ['nullable', Rule::in(['belum', 'hotel'])],
            'hari' => ['nullable', Rule::in(['7', '3', '2'])],
            'aturan' => ['nullable', Rule::in(['batal_gratis', 'batal_separuh', 'ganti_jadwal', 'terkunci'])],
            'sumber' => ['nullable', Rule::in(array_map(fn (SumberPesanan $sumber): string => $sumber->value, SumberPesanan::cases()))],
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
            'per_halaman' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $statusTab = $filter['status'] ?? null;

        $query = Pesanan::query()
            ->with(['perjalanan', 'penggantian'])
            ->when($statusTab, fn ($q, $status) => $q->where('status', $status))
            ->when($filter['tahap'] ?? null, fn ($q, string $tahap) => $q->tahap($tahap))
            ->when($filter['cari'] ?? null, function ($q, $cari) {
                $q->where(function ($q) use ($cari): void {
                    $q->whereLike('kode', "%{$cari}%")
                        ->orWhereLike('nama_lengkap', "%{$cari}%")
                        ->orWhereLike('whatsapp', "%{$cari}%");
                });
            })
            ->when($filter['penugasan'] ?? null, fn ($q, string $penugasan) => match ($penugasan) {
                'ditolak' => $q->adaPenolakanPersonel(),
                'menunggu' => $q->menungguKonfirmasiPersonel(),
                default => $q->penugasanBelumLengkap(),
            })
            ->when($filter['penginapan'] ?? null, fn ($q, string $penginapan) => $penginapan === 'hotel' ? $q->hotelBelumTerkonfirmasi() : $q->butuhRekomendasiPenginapan())
            ->when($filter['hari'] ?? null, fn ($q, string $hari) => $q->whereDate('tanggal_trip', now()->addDays((int) $hari)->toDateString()))
            ->when($filter['aturan'] ?? null, fn ($q, string $aturan) => $q->aturanBerlaku($aturan))
            ->when($filter['journey'] ?? null, fn ($q, $id) => $q->where('perjalanan_id', $id))
            ->when($filter['sumber'] ?? null, fn ($q, $sumber) => $q->where('sumber', $sumber))
            ->when($filter['dari'] ?? null, fn ($q, $dari) => $q->whereDate('tanggal_trip', '>=', $dari))
            ->when($filter['sampai'] ?? null, fn ($q, $sampai) => $q->whereDate('tanggal_trip', '<=', $sampai))
            ->latest('created_at');

        $pesanan = $query->paginate((int) ($filter['per_halaman'] ?? 10))->withQueryString();

        $statusPesananAktif = [BookingStatus::Dihubungi, BookingStatus::Payment, BookingStatus::HoldSchedule];

        $duplikatNomor = Pesanan::query()
            ->select('whatsapp', 'tanggal_trip')
            ->whereIn('status', $statusPesananAktif)
            ->groupBy('whatsapp', 'tanggal_trip')
            ->havingRaw('count(*) > 1')
            ->get()
            ->map(fn ($baris) => $baris->whatsapp.'|'.$baris->tanggal_trip->toDateString())
            ->all();

        $duplikatDestinasi = Pesanan::query()
            ->select('whatsapp', 'perjalanan_id', 'tanggal_trip')
            ->whereIn('status', $statusPesananAktif)
            ->groupBy('whatsapp', 'perjalanan_id', 'tanggal_trip')
            ->havingRaw('count(*) > 1')
            ->get()
            ->map(fn ($baris) => $baris->whatsapp.'|'.$baris->perjalanan_id.'|'.$baris->tanggal_trip->toDateString())
            ->all();

        // Hari-hari yang trip aktifnya melebihi jumlah guide (sekali hitung untuk seluruh halaman).
        $kapasitas = app(KapasitasTripLayanan::class);
        $rentangKelebihanGuide = $kapasitas->rentangKelebihanGuide();

        $penanda = $pesanan->mapWithKeys(function (Pesanan $item) use ($batasBayar, $duplikatNomor, $duplikatDestinasi, $kapasitas, $rentangKelebihanGuide) {
            $tanggal = $item->tanggal_trip->toDateString();

            return [$item->id => [
                'batas_bayar_lewat' => $item->status === BookingStatus::Dihubungi && $batasBayar->sudahLewat($item),
                'duplikat_nomor' => in_array($item->whatsapp.'|'.$tanggal, $duplikatNomor, true),
                'duplikat_destinasi' => in_array($item->whatsapp.'|'.$item->perjalanan_id.'|'.$tanggal, $duplikatDestinasi, true),
                'guide_tidak_cukup' => $kapasitas->pesananKelebihanGuide($item, $rentangKelebihanGuide),
                'penugasan_ditolak' => ! $item->terkunci() && $item->penugasanDitolak(),
                'penugasan_mendesak' => $item->penugasanMendesak(),
            ]];
        });

        return view('admin.pesanan.indeks', [
            'pesanan' => $pesanan,
            'penanda' => $penanda,
            'jumlahPerTahap' => Pesanan::jumlahPerTahap(),
            'daftarJourney' => Perjalanan::query()->where('template_pesanan_kustom', false)->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function create(): View
    {
        $daftarJourney = Perjalanan::query()
            ->where('template_pesanan_kustom', false)
            ->orderBy('nama')
            ->get();

        // Pilihan tanggal per journey = Tanggal Tersedia beserta sisa slot nyatanya (kuota journey dan guide bebas).
        $kapasitas = app(KapasitasTripLayanan::class);
        $pilihanTanggal = $daftarJourney->mapWithKeys(fn (Perjalanan $journey): array => [$journey->id => $kapasitas->daftarTanggal($journey)
            ->map(fn (array $opsi): array => [
                'tanggal' => $opsi['tanggal'],
                'penuh' => ! $opsi['tersedia'],
                'label' => Carbon::parse($opsi['tanggal'])->translatedFormat('l, d F Y').' · '.($opsi['tersedia'] ? $opsi['label'] : $opsi['label'].' ('.$opsi['alasan'].')'),
            ])->all()]);

        return view('admin.pesanan.buat', [
            'daftarJourney' => $daftarJourney,
            'pilihanTanggal' => $pilihanTanggal,
        ]);
    }

    public function store(SimpanPesananRequest $request, BuatPesananManual $aksi): RedirectResponse
    {
        $perjalanan = Perjalanan::findOrFail($request->integer('perjalanan_id'));

        $data = AturanPenginapan::normalisasi($request->safe()->except('perjalanan_id'), dariTamu: false);
        $data['whatsapp'] = $request->whatsappTernormalisasi();
        $pesanan = $aksi->handle($perjalanan, $data);

        return redirect()
            ->route('admin.pesanan.detail', $pesanan)
            ->with('sukses', "Pesanan {$pesanan->kode} berhasil dibuat.");
    }

    public function show(Pesanan $pesanan, BatasBayarLayanan $batasBayar, BiayaJadwalLayanan $biayaJadwal): View
    {
        $pesanan->load(['perjalanan', 'penggantian']);
        $dariPermintaan = PermintaanPesanan::query()->where('pesanan_id', $pesanan->id)->exists();
        $kustomAsal = PesananKustom::withTrashed()->where('pesanan_id', $pesanan->id)->first();

        return view('admin.pesanan.detail', [
            'pesanan' => $pesanan,
            'dariPermintaan' => $dariPermintaan,
            'kustomAsal' => $kustomAsal,
            'batasBayarWaktu' => $batasBayar->batasWaktu($pesanan),
            'bolehGantiJadwal' => $biayaJadwal->bolehGantiJadwal($pesanan),
            'chargeGantiJadwal' => $biayaJadwal->hitungCharge($pesanan),
        ]);
    }

    public function edit(Pesanan $pesanan): View
    {
        return view('admin.pesanan.ubah', ['pesanan' => $pesanan]);
    }

    public function update(PerbaruiPesananRequest $request, Pesanan $pesanan): RedirectResponse
    {
        $data = $this->selaraskanReservasiHotel(AturanPenginapan::normalisasi($request->validated(), dariTamu: false), $pesanan);
        $data['whatsapp'] = $request->whatsappTernormalisasi();
        $data['harga'] = filled($data['harga'] ?? null)
            ? (int) $data['harga']
            : $pesanan->perjalanan?->hargaDefault($data['status_penginapan'] ?? null, (int) $data['jumlah_wisatawan']);

        if (($data['hotel_id'] ?? $pesanan->hotel_id) === null && $pesanan->bayar_hotel_bukti) {
            Storage::disk('local')->delete($pesanan->bayar_hotel_bukti);
        }

        $perubahan = PerubahanDataTamu::bandingkan($pesanan, $data);

        if ($perubahan !== [] && ! $request->boolean('konfirmasi_tamu')) {
            throw ValidationException::withMessages([
                'konfirmasi_tamu' => 'Data tamu berubah. Konfirmasikan perubahan ini ke tamu terlebih dahulu, lalu centang konfirmasi sebelum menyimpan.',
            ]);
        }

        $pesanan->update($data);

        $redirect = redirect()->route('admin.pesanan.detail', $pesanan);

        return $perubahan === []
            ? $redirect->with('sukses', 'Data pesanan berhasil diperbarui.')
            : $redirect->with('sukses', 'Data pesanan diperbarui. Kirim pesan konfirmasi perubahan ke tamu lewat kartu Pesan WhatsApp.')->with('perubahan_data', $perubahan);
    }

    public function konfirmasi(KonfirmasiPesananRequest $request, Pesanan $pesanan, KonfirmasiPesanan $aksi): RedirectResponse
    {
        $aksi->handle($pesanan);

        return back()->with('sukses', 'Konfirmasi tercatat. Status tetap Dihubungi dan tenggat pembayaran dimulai; kirim instruksi pembayaran melalui WhatsApp.');
    }

    public function ubahKePayment(Pesanan $pesanan, TandaiLunas $aksi): RedirectResponse
    {
        $this->authorize('update', $pesanan);

        $aksi->handle($pesanan);

        return back()->with('sukses', 'Pesanan ditandai Sudah Bayar.');
    }

    public function catatPembayaranDiterima(CatatPembayaranDiterimaRequest $request, Pesanan $pesanan, CatatPembayaranDiterima $aksi): RedirectResponse
    {
        $pesanan = $aksi->handle(
            $pesanan,
            $request->string('jenis_pembayaran')->value(),
            $request->filled('nominal_dibayar') ? $request->integer('nominal_dibayar') : null,
            $request->string('metode_pembayaran')->value(),
        );

        if ($request->string('jenis_pembayaran')->value() === 'lunas') {
            return redirect()->to(route('admin.pesanan.detail', $pesanan).'#operasional-pesanan')
                ->with('sukses', 'Pembayaran lunas dicatat. Pesanan siap dilanjutkan ke penugasan Guide.');
        }

        return back()->with('sukses', $pesanan->sisaTagihan() === 0
            ? 'Pelunasan berhasil dicatat. Sisa tagihan sekarang Rp0.'
            : 'Pembayaran berhasil dicatat dan sisa tagihan telah diperbarui.');
    }

    public function selesaikan(Pesanan $pesanan, SelesaikanPesanan $aksi): RedirectResponse
    {
        $this->authorize('update', $pesanan);

        $aksi->handle($pesanan);

        return back()->with('sukses', 'Pesanan ditandai Trip Selesai.');
    }

    public function toggleHold(Pesanan $pesanan, ToggleHoldPesanan $aksi): RedirectResponse
    {
        $this->authorize('update', $pesanan);

        $sudahHold = $pesanan->status === BookingStatus::HoldSchedule;

        $aksi->handle($pesanan);

        return back()->with('sukses', $sudahHold ? 'Penahanan jadwal dilepas.' : 'Pesanan dipindah ke Jadwal Ditahan.');
    }

    public function gantiJadwal(GantiJadwalPesananRequest $request, Pesanan $pesanan, GantiJadwalPesanan $aksi, EmailPenugasanPersonelLayanan $email): RedirectResponse
    {
        $hotelIkutPindah = $pesanan->hotel_id !== null && $pesanan->status_reservasi_hotel !== StatusReservasiHotel::Dibatalkan;
        $pesanan = $aksi->handle($pesanan, Carbon::parse($request->string('tanggal_trip')));

        $personel = PersonelOperasional::query()
            ->with('user')
            ->whereIn('id', array_filter([$pesanan->guide_personel_id]))
            ->get()
            ->filter(fn (PersonelOperasional $orang): bool => (bool) $email->alamatTujuan($orang));
        $jumlahEmailGagal = $email->kirim($pesanan, $personel)['gagal'];

        $redirect = back()->with('sukses', 'Jadwal pesanan berhasil diganti. Konfirmasi personel direset dan pemberitahuan jadwal baru diproses.'.($hotelIkutPindah
            ? ' Tanggal reservasi hotel ikut dipindah dan statusnya kembali menunggu konfirmasi hotel: kabari hotel soal tanggal baru.'
            : ''));

        if ($jumlahEmailGagal > 0) {
            $redirect->with('gagal', "{$jumlahEmailGagal} email jadwal baru gagal dikirim. Periksa konfigurasi SMTP Gmail dan log aplikasi.");
        }

        return $redirect;
    }

    public function batalkan(BatalkanPesananRequest $request, Pesanan $pesanan, BatalkanPesanan $aksi, EmailPenugasanPersonelLayanan $email): RedirectResponse
    {
        $pesanan = $aksi->handle(
            $pesanan,
            $request->string('pihak_pembatal')->value(),
            $request->string('alasan_batal')->value(),
            $request->integer('nominal_refund'),
        );

        $adaGuide = $pesanan->guide_personel_id !== null;
        $guideDikabari = $adaGuide && $email->kirimPembatalan($pesanan);

        return back()->with('sukses', match (true) {
            $guideDikabari => 'Pesanan berhasil dibatalkan dan guide sudah dikabari lewat email.',
            default => 'Pesanan berhasil dibatalkan.',
        })->with('gagal', $adaGuide && ! $guideDikabari ? 'Guide yang bertugas belum bisa dikabari lewat email (alamat belum ada atau email gagal terkirim). Kabari guide langsung.' : null);
    }

    public function simpanOperasional(PerbaruiOperasionalRequest $request, Pesanan $pesanan, SimpanOperasionalPesanan $aksi, EmailPenugasanPersonelLayanan $email): RedirectResponse
    {
        $idUnitLama = $pesanan->kendaraan_id;

        $hasil = $aksi->handle($pesanan, $request->safe()->except('bukti_pembayaran'), $request->file('bukti_pembayaran'));
        $pesananBaru = $pesanan->fresh(['kendaraan']);
        $pengiriman = $email->kirim($pesananBaru, $hasil['personel']);
        $unitBerubah = $pesananBaru->kendaraan_id !== null && $pesananBaru->kendaraan_id !== $idUnitLama;

        if ($hasil['tanpa_email'] > 0 && $pesananBaru->guide) {
            $email->catatHasil($pesananBaru, $pesananBaru->guide, 'tanpa_email', null, 'Guide belum punya alamat email. Lengkapi email kontaknya di menu Guide.');
        }

        // Bila ada email penugasan, hasilnya tampil di popup (yang juga mengabarkan data tersimpan) dan toast tidak dipakai agar tidak bertabrakan.
        $pesanSukses = $unitBerubah ? 'Data operasional disimpan. Kirim pesan perubahan unit ke tamu lewat kartu Pesan WhatsApp.' : 'Data operasional disimpan.';
        $redirect = $pengiriman['terkirim'] + $pengiriman['gagal'] + $hasil['tanpa_email'] > 0
            ? back()->with('popup_email_penugasan', $pesanSukses)
            : back()->with('sukses', $pesanSukses);
        if ($unitBerubah) {
            $redirect->with('perubahan_unit', ['baru' => $pesananBaru->kendaraan->namaUntukTamu()]);
        }

        return $redirect;
    }

    /** Status penugasan guide untuk panel Operasional yang diperbarui otomatis (konfirmasi guide + hasil email). */
    public function statusPenugasan(Pesanan $pesanan): JsonResponse
    {
        $this->authorize('view', $pesanan);

        return response()->json($pesanan->ringkasanPenugasanGuide());
    }

    /** Kirim ulang email penugasan ke guide yang bertugas tanpa harus menyimpan ulang seluruh formulir Operasional. */
    public function kirimUlangPenugasan(Pesanan $pesanan, EmailPenugasanPersonelLayanan $email): RedirectResponse
    {
        $this->authorize('update', $pesanan);

        $guide = $pesanan->guide()->with('user')->first();
        if ($pesanan->terkunci() || $guide === null) {
            return back()->with('gagal', 'Tidak ada guide aktif yang bisa dikirimi email penugasan pada pesanan ini.');
        }

        if ($email->alamatTujuan($guide) === null) {
            $email->catatHasil($pesanan, $guide, 'tanpa_email', null, 'Guide belum punya alamat email. Lengkapi email kontaknya di menu Guide.');

            return back()->with('popup_email_penugasan', 'Email penugasan belum bisa dikirim ulang.');
        }

        $email->kirim($pesanan, [$guide]);

        return back()->with('popup_email_penugasan', 'Pengiriman ulang email penugasan selesai.');
    }

    /**
     * Tahap "Pesan kamar": simpan hotel, tanggal, dan kamar. Status tidak lagi dipilih admin: reservasi baru atau hotel
     * pengganti selalu "diminta" dan kemajuan alurnya dimulai dari awal, sedangkan perubahan tanggal/kamar pada hotel
     * yang sama mempertahankan status. Modal draft ke hotel hanya terbuka bila permintaan memang perlu dikirim.
     */
    public function simpanHotel(SimpanHotelPesananRequest $request, Pesanan $pesanan): RedirectResponse
    {
        $data = Arr::except($request->validated(), ['setuju_biaya_ganti_hotel']);
        $hotel = Hotel::withTrashed()->findOrFail($data['hotel_id']);
        $reservasiBaru = $pesanan->hotel_id === null
            || (int) $pesanan->hotel_id !== $hotel->id
            || $pesanan->status_reservasi_hotel === StatusReservasiHotel::Dibatalkan;
        $status = $data['status_reservasi_hotel'] ?? ($reservasiBaru
            ? StatusReservasiHotel::Diminta->value
            : ($pesanan->status_reservasi_hotel?->value ?? StatusReservasiHotel::Diminta->value));
        $dibatalkan = $status === StatusReservasiHotel::Dibatalkan->value;
        $buktiLama = $reservasiBaru ? $pesanan->bayar_hotel_bukti : null;

        $pesanan->update([
            // Hotel pengganti adalah reservasi baru: tanda kirim, pembayaran, dan kode booking hotel lama tidak berlaku.
            ...($reservasiBaru && $pesanan->hotel_id !== null ? [...$this->kolomAlurHotelKosong(), 'kode_booking_hotel' => null] : []),
            ...$data,
            'status_reservasi_hotel' => $status,
            // Nama hotel dipakai di seluruh tampilan (manifest, dashboard pemandu); dikosongkan bila reservasi dibatalkan.
            'nama_hotel' => $dibatalkan ? null : $hotel->nama,
            // Tamu yang minta dicarikan resmi berstatus "dipesankan" sejak hotel dipilih.
            'status_penginapan' => $pesanan->status_penginapan === StatusPenginapan::Belum ? StatusPenginapan::Dipesankan : $pesanan->status_penginapan,
        ]);

        if ($buktiLama && $pesanan->bayar_hotel_bukti === null) {
            Storage::disk('local')->delete($buktiLama);
        }

        $perluKirimPermintaan = $status === StatusReservasiHotel::Diminta->value && $pesanan->permintaan_hotel_dikirim_at === null;
        $redirect = back()->with('sukses', match (true) {
            $dibatalkan => 'Reservasi hotel ditandai dibatalkan.',
            $perluKirimPermintaan => 'Reservasi hotel disimpan. Kirim permintaan ke hotel lewat draft yang terbuka.',
            default => 'Perubahan reservasi hotel disimpan. Kabari hotel bila tanggal atau jumlah kamar berubah.',
        });

        return $dibatalkan || $perluKirimPermintaan ? $redirect->with('modal_reservasi_hotel', $status) : $redirect;
    }

    /** Tahap "Hotel setuju": catat kode booking dan tandai terkonfirmasi. Hotel yang setuju pasti sudah menerima permintaan. */
    public function konfirmasiHotel(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $this->authorize('update', $pesanan);

        if ($pesanan->hotel_id === null || in_array($pesanan->status->value, ['completed', 'dibatalkan'], true)) {
            return back()->with('gagal', 'Pesanan ini tidak memiliki reservasi hotel yang bisa dikonfirmasi.');
        }

        $data = $request->validate([
            'kode_booking_hotel' => ['required', 'string', 'max:100', 'not_regex:/[<>]/'],
        ], [
            'kode_booking_hotel.required' => 'Isi kode booking atau nama petugas hotel yang mengonfirmasi.',
            'kode_booking_hotel.max' => 'Kode booking maksimal 100 karakter.',
            'kode_booking_hotel.not_regex' => 'Kode booking tidak boleh memuat karakter < atau >.',
        ]);

        $pesanan->update([
            ...$data,
            'status_reservasi_hotel' => StatusReservasiHotel::Terkonfirmasi,
            'permintaan_hotel_dikirim_at' => $pesanan->permintaan_hotel_dikirim_at ?? now(),
        ]);

        return back()->with('sukses', $pesanan->status_penginapan === StatusPenginapan::Diurus
            ? 'Hotel ditandai sudah setuju. Lanjut tahap Bayar hotel: catat transfer dan unggah buktinya.'
            : 'Hotel ditandai sudah setuju. Lanjut tahap Kabari tamu.');
    }

    /** Membatalkan reservasi yang sudah disetujui hotel; draft pembatalan ke hotel terbuka otomatis. */
    public function batalkanHotel(Pesanan $pesanan): RedirectResponse
    {
        $this->authorize('update', $pesanan);

        if ($pesanan->hotel_id === null || $pesanan->status_reservasi_hotel === StatusReservasiHotel::Dibatalkan || in_array($pesanan->status->value, ['completed', 'dibatalkan'], true)) {
            return back()->with('gagal', 'Tidak ada reservasi hotel aktif yang bisa dibatalkan.');
        }

        $pesanan->update(['status_reservasi_hotel' => StatusReservasiHotel::Dibatalkan, 'nama_hotel' => null]);

        return back()
            ->with('sukses', 'Reservasi hotel dibatalkan. Kirim pembatalan ke hotel lewat draft yang terbuka.')
            ->with('modal_reservasi_hotel', StatusReservasiHotel::Dibatalkan->value);
    }

    /**
     * Menjaga reservasi hotel dan status penginapan tetap searah saat admin mengubah data pesanan:
     * nama hotel mengikuti reservasi, dan reservasi dilepas bila tamu kini punya penginapan sendiri atau belum butuh hotel.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function selaraskanReservasiHotel(array $data, Pesanan $pesanan): array
    {
        if ($pesanan->hotel_id === null) {
            return $data;
        }

        if (in_array($data['status_penginapan'] ?? null, [StatusPenginapan::Dipesankan->value, StatusPenginapan::Diurus->value], true)) {
            $data['nama_hotel'] = $pesanan->status_reservasi_hotel === StatusReservasiHotel::Dibatalkan ? null : $pesanan->hotel?->nama;

            return $data;
        }

        return [
            ...$data,
            'hotel_id' => null,
            'hotel_check_in' => null,
            'hotel_check_out' => null,
            'jumlah_kamar' => null,
            'tipe_kamar' => null,
            'kode_booking_hotel' => null,
            'status_reservasi_hotel' => null,
            'catatan_reservasi_hotel' => null,
            ...$this->kolomAlurHotelKosong(),
        ];
    }

    /**
     * Kolom kemajuan alur Reservasi Hotel yang dikosongkan saat reservasi dilepas.
     *
     * @return array<string, null>
     */
    private function kolomAlurHotelKosong(): array
    {
        return [
            'permintaan_hotel_dikirim_at' => null,
            'bayar_hotel_nominal' => null,
            'bayar_hotel_tanggal' => null,
            'bayar_hotel_bukti' => null,
            'bukti_bayar_hotel_dikirim_at' => null,
            'bayar_hotel_dikonfirmasi_at' => null,
            'tamu_dikabari_hotel_at' => null,
            'konfirmasi_ulang_hotel_at' => null,
        ];
    }

    public function lepasHotel(Pesanan $pesanan): RedirectResponse
    {
        $this->authorize('update', $pesanan);

        if ($pesanan->status_reservasi_hotel === StatusReservasiHotel::Terkonfirmasi) {
            return back()->with('gagal', 'Hotel yang sudah terkonfirmasi tidak bisa dilepas. Untuk mengganti hotel, pilih hotel baru di formulir reservasi dan centang persetujuan biaya ganti hotel (charge).');
        }

        if ($pesanan->bayar_hotel_bukti) {
            Storage::disk('local')->delete($pesanan->bayar_hotel_bukti);
        }

        $pesanan->update([
            'hotel_id' => null,
            'hotel_check_in' => null,
            'hotel_check_out' => null,
            'jumlah_kamar' => null,
            'tipe_kamar' => null,
            'kode_booking_hotel' => null,
            'status_reservasi_hotel' => null,
            'catatan_reservasi_hotel' => null,
            ...$this->kolomAlurHotelKosong(),
            'nama_hotel' => null,
            'status_penginapan' => $pesanan->status_penginapan === StatusPenginapan::Dipesankan ? StatusPenginapan::Belum : $pesanan->status_penginapan,
        ]);

        return back()->with('sukses', 'Reservasi hotel dilepas dari pesanan.');
    }

    /** Tombol hijau "Sudah dikirim" pada stepper Reservasi Hotel: mencatat waktu langkah (idempoten). */
    public function tandaiLangkahHotel(Pesanan $pesanan, string $langkah, AlurReservasiHotel $alur): RedirectResponse
    {
        $this->authorize('update', $pesanan);
        abort_unless(array_key_exists($langkah, AlurReservasiHotel::TANDA), 404);

        if (in_array($pesanan->status->value, ['completed', 'dibatalkan'], true) || ! $alur->bolehDitandai($pesanan, $langkah)) {
            return back()->with('gagal', 'Langkah ini belum bisa ditandai. Selesaikan langkah sebelumnya dulu.');
        }

        $kolom = AlurReservasiHotel::TANDA[$langkah];
        if ($pesanan->{$kolom} === null) {
            $pesanan->update([$kolom => now()]);
        }

        return back()->with('sukses', 'Langkah ditandai selesai.');
    }

    /** Membatalkan tanda langkah bila admin salah menekan tombol. */
    public function batalkanTandaLangkahHotel(Pesanan $pesanan, string $langkah): RedirectResponse
    {
        $this->authorize('update', $pesanan);
        abort_unless(array_key_exists($langkah, AlurReservasiHotel::TANDA), 404);

        if (in_array($pesanan->status->value, ['completed', 'dibatalkan'], true)) {
            return back()->with('gagal', 'Pesanan ini sudah ditutup, tanda langkah tidak bisa diubah.');
        }

        $pesanan->update([AlurReservasiHotel::TANDA[$langkah] => null]);

        return back()->with('sukses', 'Tanda langkah dibatalkan.');
    }

    /** Mencatat transfer MISTBORNE ke hotel (paket all-inclusive) beserta bukti transfernya. */
    public function simpanPembayaranHotel(SimpanPembayaranHotelRequest $request, Pesanan $pesanan, AlurReservasiHotel $alur): RedirectResponse
    {
        if (! $alur->perluBayarKeHotel($pesanan) || $pesanan->status_reservasi_hotel !== StatusReservasiHotel::Terkonfirmasi || in_array($pesanan->status->value, ['completed', 'dibatalkan'], true)) {
            return back()->with('gagal', 'Transfer ke hotel hanya dicatat untuk paket all-inclusive setelah hotel setuju.');
        }

        $data = $request->safe()->only(['bayar_hotel_nominal', 'bayar_hotel_tanggal']);
        $buktiLama = $pesanan->bayar_hotel_bukti;

        if ($request->hasFile('bayar_hotel_bukti')) {
            $path = $request->file('bayar_hotel_bukti')->store('bukti-bayar-hotel', 'local');

            if (! $path) {
                return back()->with('gagal', 'Bukti transfer gagal disimpan. Periksa ruang penyimpanan lalu coba lagi.');
            }

            $data['bayar_hotel_bukti'] = $path;
        }

        $pesanan->update($data);

        if (isset($data['bayar_hotel_bukti']) && $buktiLama) {
            Storage::disk('local')->delete($buktiLama);
        }

        return back()->with('sukses', 'Transfer ke hotel dicatat. Kirim bukti transfer ke admin hotel lewat draft WhatsApp.');
    }

    /** Bukti transfer ke hotel disimpan di disk private dan hanya bisa dilihat admin lewat rute ini. */
    public function lihatBuktiHotel(Pesanan $pesanan): StreamedResponse
    {
        $this->authorize('view', $pesanan);

        abort_unless($pesanan->bayar_hotel_bukti && Storage::disk('local')->exists($pesanan->bayar_hotel_bukti), 404);

        return Storage::disk('local')->response($pesanan->bayar_hotel_bukti);
    }

    public function hapusBukti(Pesanan $pesanan): RedirectResponse
    {
        $this->authorize('update', $pesanan);

        if ($pesanan->bukti_pembayaran) {
            Storage::disk('local')->delete($pesanan->bukti_pembayaran);
            $pesanan->update(['bukti_pembayaran' => null]);
        }

        return back()->with('sukses', 'Bukti pembayaran dihapus.');
    }

    /** Bukti pembayaran disimpan di disk private — hanya bisa diakses lewat rute ini (admin saja). */
    public function lihatBukti(Pesanan $pesanan): StreamedResponse
    {
        $this->authorize('view', $pesanan);

        abort_unless($pesanan->bukti_pembayaran && Storage::disk('local')->exists($pesanan->bukti_pembayaran), 404);

        return Storage::disk('local')->response($pesanan->bukti_pembayaran);
    }

    /** Checkbox "Sudah terkirim" pada widget Pengingat hari ini (dashboard) — murni penanda tampilan. */
    /**
     * Tanda pengingat H-7/H-3/H-2. Tombol kirim satu klik di Aksi Cepat mengirim `terkirim` secara eksplisit (klik ganda
     * tidak membatalkan tanda); kotak centang di dashboard tanpa `terkirim` tetap membalik tanda.
     */
    public function tandaiPengingat(Request $request, Pesanan $pesanan, string $jenis): RedirectResponse
    {
        $this->authorize('update', $pesanan);

        abort_unless(in_array($jenis, ['h7', 'h3', 'h2'], true), 404);

        $kolom = "pengingat_{$jenis}_terkirim";
        $pesanan->update([$kolom => $request->has('terkirim') ? $request->boolean('terkirim') : ! $pesanan->{$kolom}]);

        return back()->with('sukses', 'Penanda pengingat diperbarui.');
    }
}
