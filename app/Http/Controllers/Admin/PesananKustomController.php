<?php

namespace App\Http\Controllers\Admin;

use App\Aksi\PesananKustom\JadikanPesananResmi;
use App\Bantuan\AturanPenginapan;
use App\Enums\StatusPesananKustom;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PesananKustom\JadikanResmiRequest;
use App\Http\Requests\Admin\PesananKustom\PerbaruiPesananKustomRequest;
use App\Http\Requests\Admin\PesananKustom\PerbaruiTindakLanjutPesananKustomRequest;
use App\Http\Requests\Admin\PesananKustom\SimpanPesananKustomRequest;
use App\Models\PesananKustom;
use App\Models\RiwayatPenawaranPesananKustom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PesananKustomController extends Controller
{
    public function create(): View
    {
        return view('admin.pesanan-kustom.buat');
    }

    public function store(SimpanPesananKustomRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['tanggal_fleksibel', 'tanggal_diinginkan']);
        $data['minat_perjalanan'] = $request->input('minat_perjalanan', []);
        $fleksibel = $request->boolean('tanggal_fleksibel');
        $tanggal = $fleksibel ? null : $request->date('tanggal_diinginkan');

        $pesananKustom = PesananKustom::create([
            ...$data,
            'whatsapp' => $request->whatsappTernormalisasi(),
            'tanggal_fleksibel' => $fleksibel,
            'tanggal_diinginkan' => $tanggal,
            'jadwal_diinginkan' => $fleksibel ? 'Tanggal fleksibel' : $tanggal?->format('Y-m-d'),
        ]);

        return redirect()
            ->route('admin.pesanan-kustom.detail', $pesananKustom->id)
            ->with('sukses', 'Pesanan kustom berhasil dibuat secara manual.');
    }

    public function show(int $id): View
    {
        $pesananKustom = PesananKustom::withTrashed()->with(['pesanan', 'riwayatPenawaran.pengguna'])->findOrFail($id);
        $this->authorize('view', $pesananKustom);

        return view('admin.pesanan-kustom.detail', [
            'pesananKustom' => $pesananKustom,
        ]);
    }

    public function index(): View
    {
        $arsip = request('status') === 'arsip';

        $query = PesananKustom::query()->with('pesanan')
            ->when($arsip, fn ($q) => $q->onlyTrashed())
            ->when(! $arsip && request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('cari'), function ($q, $cari) {
                $q->where(function ($q) use ($cari): void {
                    $q->whereLike('nama', "%{$cari}%")
                        ->orWhereLike('whatsapp', "%{$cari}%")
                        ->orWhereLike('destinasi_diinginkan', "%{$cari}%")
                        ->orWhereLike('lokasi_penjemputan', "%{$cari}%");
                });
            })
            ->latest('created_at');

        $daftar = $query->paginate(10)->withQueryString();

        $jumlahPerStatus = PesananKustom::query()->selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');
        $jumlahArsip = PesananKustom::onlyTrashed()->count();

        return view('admin.pesanan-kustom.indeks', [
            'daftar' => $daftar,
            'jumlahPerStatus' => $jumlahPerStatus,
            'jumlahArsip' => $jumlahArsip,
        ]);
    }

    public function update(PerbaruiPesananKustomRequest $request, PesananKustom $pesananKustom): RedirectResponse
    {
        $data = AturanPenginapan::normalisasi($request->validated(), dariTamu: false);
        $fleksibel = $request->boolean('tanggal_fleksibel');
        $tanggal = $fleksibel ? null : $request->date('tanggal_diinginkan');

        unset($data['tanggal_fleksibel'], $data['tanggal_diinginkan']);
        $data['whatsapp'] = $request->whatsappTernormalisasi();
        if ($request->exists('minat_perjalanan_present')) {
            $data['minat_perjalanan'] = $request->input('minat_perjalanan', []);
        }
        $data['tanggal_fleksibel'] = $fleksibel;
        $data['tanggal_diinginkan'] = $tanggal;
        $data['jadwal_diinginkan'] = $fleksibel ? 'Tanggal fleksibel' : $tanggal?->format('Y-m-d');

        $penawaranPerluDikirimUlang = false;
        DB::transaction(function () use ($pesananKustom, $data, &$penawaranPerluDikirimUlang): void {
            $terkunci = PesananKustom::query()->whereKey($pesananKustom->id)->lockForUpdate()->firstOrFail();
            if ($terkunci->pesanan_id !== null) {
                throw ValidationException::withMessages(['pesanan_kustom' => 'Permintaan ini sudah menjadi pesanan resmi. Ubah detailnya dari fitur Pesanan.']);
            }

            $isiPenawaranBerubah = false;
            foreach (['nama', 'whatsapp', 'destinasi_diinginkan', 'tanggal_fleksibel', 'tanggal_diinginkan', 'durasi_hari', 'lokasi_penjemputan', 'jumlah_orang', 'catatan', 'minat_perjalanan', 'tempo_perjalanan'] as $kolom) {
                if (! array_key_exists($kolom, $data)) {
                    continue;
                }
                $lama = $terkunci->{$kolom};
                $baru = $data[$kolom];
                if ($lama instanceof \DateTimeInterface) {
                    $lama = $lama->format('Y-m-d');
                }
                if ($baru instanceof \DateTimeInterface) {
                    $baru = $baru->format('Y-m-d');
                }
                if (is_array($lama) || is_array($baru)) {
                    $lama = json_encode($lama);
                    $baru = json_encode($baru);
                }
                if ((string) ($lama ?? '') !== (string) ($baru ?? '')) {
                    $isiPenawaranBerubah = true;
                    break;
                }
            }
            if ($terkunci->status !== StatusPesananKustom::TidakJadi && $isiPenawaranBerubah && $terkunci->penawaran_dikirim_pada) {
                $data['status'] = StatusPesananKustom::Diproses;
                $data['penawaran_dikirim_pada'] = null;
                $penawaranPerluDikirimUlang = true;
            }
            $terkunci->update($data);
        });

        return back()->with('sukses', $penawaranPerluDikirimUlang
            ? 'Pesanan kustom diperbarui. Penawaran kembali ke status Diproses; kirim versi terbaru kepada tamu.'
            : 'Pesanan kustom berhasil diperbarui.');
    }

    public function updateTindakLanjut(PerbaruiTindakLanjutPesananKustomRequest $request, PesananKustom $pesananKustom): RedirectResponse
    {
        $data = $request->validated();

        $penawaranPerluDikirimUlang = false;
        DB::transaction(function () use ($pesananKustom, $data, &$penawaranPerluDikirimUlang): void {
            $terkunci = PesananKustom::query()->whereKey($pesananKustom->id)->lockForUpdate()->firstOrFail();
            if ($terkunci->pesanan_id !== null) {
                throw ValidationException::withMessages(['status' => 'Permintaan ini sudah menjadi pesanan resmi dan tidak dapat diubah dari alur kustom.']);
            }

            // Formulir "Susun Penawaran" tidak memilih status: menyimpan penawaran berarti permintaan sedang diproses.
            // Status lain (Tidak jadi, buka kembali) dikirim tombol khusus di Aksi Cepat.
            if (! array_key_exists('status', $data)) {
                $data['status'] = $terkunci->status === StatusPesananKustom::Baru
                    ? StatusPesananKustom::Diproses->value
                    : $terkunci->status->value;
            }

            if (($data['status'] ?? null) === StatusPesananKustom::MenungguJawaban->value
                && $terkunci->status !== StatusPesananKustom::MenungguJawaban) {
                throw ValidationException::withMessages(['status' => 'Gunakan aksi Tandai Penawaran Terkirim setelah penawaran benar-benar dikirim ke tamu.']);
            }

            $detailPenawaranBerubah = false;
            foreach (['harga_final', 'rencana_perjalanan', 'rincian_penawaran', 'batas_penawaran'] as $kolom) {
                if (! array_key_exists($kolom, $data)) {
                    continue;
                }
                $nilaiLama = $terkunci->{$kolom};
                $nilaiBaru = $data[$kolom];
                if ($nilaiLama instanceof \DateTimeInterface) {
                    $nilaiLama = $nilaiLama->format('Y-m-d');
                }
                if ($nilaiBaru instanceof \DateTimeInterface) {
                    $nilaiBaru = $nilaiBaru->format('Y-m-d');
                }
                if ((string) ($nilaiLama ?? '') !== (string) ($nilaiBaru ?? '')) {
                    $detailPenawaranBerubah = true;
                    break;
                }
            }

            // Revisi setelah penawaran terkirim membatalkan status menunggu jawaban.
            // Admin harus mengirim ulang penawaran yang terbaru.
            if (($data['status'] ?? null) !== StatusPesananKustom::TidakJadi->value
                && $detailPenawaranBerubah
                && $terkunci->penawaran_dikirim_pada) {
                $data['status'] = StatusPesananKustom::Diproses;
                $data['penawaran_dikirim_pada'] = null;
                $penawaranPerluDikirimUlang = true;
            }
            if (in_array($data['status'] ?? null, [StatusPesananKustom::Baru->value, StatusPesananKustom::Diproses->value], true)
                && $terkunci->penawaran_dikirim_pada
                && $terkunci->status->value !== ($data['status'] ?? null)) {
                $data['penawaran_dikirim_pada'] = null;
                $penawaranPerluDikirimUlang = true;
            }
            if (($data['status'] ?? null) !== StatusPesananKustom::TidakJadi->value) {
                $data['alasan_tidak_jadi'] = null;
            }

            $terkunci->update($data);
        });

        return back()->with('sukses', $penawaranPerluDikirimUlang
            ? 'Tindak lanjut diperbarui. Penawaran kembali ke status Diproses; kirim versi terbaru kepada tamu.'
            : 'Tindak lanjut pesanan kustom berhasil diperbarui.');
    }

    /**
     * Tahap 1 "Hubungi": tombol kirim satu klik membuka WhatsApp tamu sekaligus memindahkan permintaan Baru ke Diproses.
     * Data permintaan harus lengkap dulu (tampilan menahan tombol lewat popup validasi; di sini diperiksa ulang).
     */
    public function hubungi(PesananKustom $pesananKustom): RedirectResponse
    {
        $this->authorize('update', $pesananKustom);

        if ($pesananKustom->pesanan_id !== null) {
            return back()->with('gagal', 'Permintaan ini sudah menjadi pesanan resmi.');
        }

        $belumLengkap = $pesananKustom->dataBelumLengkap();
        if ($belumLengkap !== []) {
            return back()->with('gagal', 'Lengkapi data permintaan dulu: '.implode(', ', $belumLengkap).'.');
        }

        if ($pesananKustom->status === StatusPesananKustom::Baru) {
            $pesananKustom->update(['status' => StatusPesananKustom::Diproses]);
        }

        return back()->with('sukses', 'Tamu dihubungi lewat WhatsApp. Lanjut tahap 2: susun penawaran.');
    }

    public function tandaiPenawaranTerkirim(PesananKustom $pesananKustom): RedirectResponse
    {
        $this->authorize('update', $pesananKustom);

        DB::transaction(function () use ($pesananKustom): void {
            $pesananKustom = PesananKustom::query()->whereKey($pesananKustom->id)->lockForUpdate()->firstOrFail();
            if ($pesananKustom->pesanan_id !== null) {
                throw ValidationException::withMessages(['status' => 'Permintaan ini sudah menjadi pesanan resmi.']);
            }
            if (! in_array($pesananKustom->status, [StatusPesananKustom::Diproses, StatusPesananKustom::MenungguJawaban], true)) {
                throw ValidationException::withMessages(['status' => 'Pindahkan permintaan ke status Diproses sebelum mengirim penawaran.']);
            }
            if (blank($pesananKustom->rencana_perjalanan)) {
                throw ValidationException::withMessages(['rencana_perjalanan' => 'Lengkapi rencana perjalanan sebelum mengirim penawaran.']);
            }
            if ($pesananKustom->harga_final === null || $pesananKustom->harga_final < 1 || $pesananKustom->harga_final > PesananKustom::BATAS_HARGA_PER_ORANG) {
                throw ValidationException::withMessages(['harga_final' => 'Isi harga penawaran antara Rp 1 dan Rp 1.000.000.000.000.000 sebelum mengirim.']);
            }
            if ($pesananKustom->batas_penawaran?->isBefore(today())) {
                throw ValidationException::withMessages(['batas_penawaran' => 'Tanggal berlaku penawaran sudah lewat. Perbarui sebelum mengirim.']);
            }

            $terkirimPada = now();
            $pesananKustom->update([
                'status' => StatusPesananKustom::MenungguJawaban,
                'penawaran_dikirim_pada' => $terkirimPada,
                'alasan_tidak_jadi' => null,
            ]);

            $versi = ((int) RiwayatPenawaranPesananKustom::query()
                ->where('pesanan_kustom_id', $pesananKustom->id)
                ->max('versi')) + 1;
            $pesananKustom->riwayatPenawaran()->create([
                'user_id' => auth()->id(),
                'versi' => $versi,
                'terkirim_pada' => $terkirimPada,
                'snapshot' => [
                    'nama' => $pesananKustom->nama,
                    'whatsapp' => $pesananKustom->whatsapp,
                    'destinasi' => $pesananKustom->destinasi_diinginkan,
                    'jadwal' => $pesananKustom->jadwalDiinginkanFormat(),
                    'durasi_hari' => $pesananKustom->durasi_hari,
                    'jumlah_orang' => $pesananKustom->jumlah_orang,
                    'lokasi_penjemputan' => $pesananKustom->lokasi_penjemputan,
                    'minat_perjalanan' => $pesananKustom->minat_perjalanan,
                    'tempo_perjalanan' => $pesananKustom->tempo_perjalanan,
                    'catatan_tamu' => $pesananKustom->catatan,
                    'rencana_perjalanan' => $pesananKustom->rencana_perjalanan,
                    'harga_per_orang' => $pesananKustom->harga_final,
                    'harga_total' => $pesananKustom->hargaTotal(),
                    'rincian_penawaran' => $pesananKustom->rincian_penawaran,
                    'batas_penawaran' => $pesananKustom->batas_penawaran?->toDateString(),
                ],
            ]);
        });

        return back()->with('sukses', 'Penawaran ditandai sudah dikirim. Permintaan kini menunggu jawaban tamu.');
    }

    public function arsipkan(PesananKustom $pesananKustom): RedirectResponse
    {
        $this->authorize('delete', $pesananKustom);

        DB::transaction(function () use ($pesananKustom): void {
            $terkunci = PesananKustom::query()->whereKey($pesananKustom->id)->lockForUpdate()->firstOrFail();
            if ($terkunci->pesanan_id !== null) {
                throw ValidationException::withMessages(['pesanan_kustom' => 'Permintaan ini terhubung ke Pesanan resmi dan tetap disimpan sebagai riwayat.']);
            }
            $terkunci->delete();
        });

        return redirect()->route('admin.pesanan-kustom.indeks')->with('sukses', 'Pesanan kustom diarsipkan.');
    }

    /** Hapus permanen (juga dari tab Arsip). Pesanan resmi hasil permintaan ini tetap tersimpan. */
    public function hapus(int $id): RedirectResponse
    {
        $pesananKustom = PesananKustom::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $pesananKustom);

        $pesananKustom->forceDelete();

        return redirect()->route('admin.pesanan-kustom.indeks')->with('sukses', "Pesanan kustom #{$pesananKustom->kodeTampilan()} dihapus permanen.");
    }

    public function pulihkan(int $id): RedirectResponse
    {
        $pesananKustom = PesananKustom::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $pesananKustom);

        $pesananKustom->restore();

        return back()->with('sukses', 'Pesanan kustom dipulihkan.');
    }

    public function jadikanResmi(JadikanResmiRequest $request, PesananKustom $pesananKustom, JadikanPesananResmi $aksi): RedirectResponse
    {
        $pesanan = $aksi->handle($pesananKustom, AturanPenginapan::normalisasi($request->safe()->except('tamu_menyetujui'), dariTamu: false));

        return redirect()
            ->route('admin.pesanan.detail', $pesanan)
            ->with('sukses', "Pesanan resmi {$pesanan->kode} berhasil dibuat dari pesanan kustom.");
    }
}
