<?php

namespace App\Aksi\Pesanan;

use App\Layanan\EmailPenugasanPersonelLayanan;
use App\Layanan\KetersediaanPersonelOperasionalLayanan;
use App\Models\AktivitasPersonel;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Menyimpan penugasan Guide, catatan admin, dan bukti pembayaran sebuah pesanan. */
class SimpanOperasionalPesanan
{
    private const PERAN = [
        'guide' => ['id' => 'guide_personel_id', 'nama' => 'nama_guide', 'status' => 'status_konfirmasi_guide', 'waktu' => 'guide_dikonfirmasi_at'],
    ];

    public function __construct(
        private readonly KetersediaanPersonelOperasionalLayanan $ketersediaan,
        private readonly EmailPenugasanPersonelLayanan $email,
    ) {}

    /**
     * @param  array<string, mixed>  $input  hasil validasi tanpa berkas bukti pembayaran
     * @return array{personel: list<PersonelOperasional>, tanpa_email: int}
     *                                                                      personel yang perlu menerima email penugasan dan jumlah personel tanpa alamat email
     */
    public function handle(Pesanan $pesanan, array $input, ?UploadedFile $buktiPembayaran): array
    {
        $pathBuktiBaru = null;

        try {
            $hasil = DB::transaction(function () use ($pesanan, $input, $buktiPembayaran, &$pathBuktiBaru): array {
                $pesanan = Pesanan::query()->whereKey($pesanan->id)->lockForUpdate()->firstOrFail();
                $personelTerkunci = $this->kunciPersonel($input);
                $data = $input;
                /** @var array<int, PersonelOperasional> $perluEmail */
                $perluEmail = [];
                $tanpaEmail = 0;

                foreach (self::PERAN as $peran => $kolom) {
                    $idBaru = filled($input[$kolom['id']] ?? null) ? (int) $input[$kolom['id']] : null;
                    $idLama = $pesanan->{$kolom['id']} ? (int) $pesanan->{$kolom['id']} : null;
                    $personel = $idBaru ? $personelTerkunci->get($idBaru) : null;

                    if ($idBaru === $idLama) {
                        unset($data[$kolom['id']]);

                        // Setiap kali admin menyimpan operasional, guide yang bertugas langsung dikirimi email agar tidak ada selisih informasi.
                        if ($personel) {
                            $this->antreEmail($personel, $perluEmail, $tanpaEmail);
                        }

                        continue;
                    }

                    $this->pastikanBolehDitugaskan($peran, $kolom['id'], $idBaru, $personel, $pesanan);

                    $data[$kolom['id']] = $idBaru;
                    $data[$kolom['nama']] = $personel?->nama;
                    $data[$kolom['status']] = $personel ? 'menunggu' : null;
                    $data[$kolom['waktu']] = null;
                    // Hasil email milik guide lama tidak berlaku untuk guide baru; diisi lagi setelah email dikirim.
                    $data['email_penugasan_status'] = null;
                    $data['email_penugasan_alamat'] = null;
                    $data['email_penugasan_at'] = null;
                    $data['email_penugasan_galat'] = null;

                    if ($idLama) {
                        AktivitasPersonel::catat($idLama, 'dilepas', "Penugasan {$pesanan->kode} ({$pesanan->nama_perjalanan}) dilepas oleh admin.", $pesanan);
                    }

                    if ($personel) {
                        AktivitasPersonel::catat($personel, 'ditugaskan', "Ditugaskan ke {$pesanan->kode} ({$pesanan->nama_perjalanan}) pada {$pesanan->tanggal_trip->translatedFormat('d M Y')}.", $pesanan);
                        $this->antreEmail($personel, $perluEmail, $tanpaEmail);
                    }
                }

                // Catatan admin yang baru atau berubah ikut dikirim ke email Guide yang bertugas.
                if ($this->catatanBerubah($pesanan, $input)) {
                    foreach (self::PERAN as $kolom) {
                        $idDipakai = filled($input[$kolom['id']] ?? null) ? (int) $input[$kolom['id']] : null;
                        $personelBertugas = $idDipakai ? $personelTerkunci->get($idDipakai) : null;

                        if ($personelBertugas) {
                            AktivitasPersonel::catat($personelBertugas, 'catatan_admin', "Admin menambahkan catatan untuk {$pesanan->kode}: ".Str::limit(trim((string) $input['catatan_admin']), 150), $pesanan);
                            $this->antreEmail($personelBertugas, $perluEmail, $tanpaEmail);
                        }
                    }
                }

                $buktiLama = $pesanan->bukti_pembayaran;
                if ($buktiPembayaran) {
                    $pathBuktiBaru = $buktiPembayaran->store('bukti-pembayaran', 'local');

                    if (! $pathBuktiBaru) {
                        throw ValidationException::withMessages([
                            'bukti_pembayaran' => 'Bukti pembayaran gagal disimpan. Periksa ruang penyimpanan lalu coba lagi.',
                        ]);
                    }

                    $data['bukti_pembayaran'] = $pathBuktiBaru;
                }

                $pesanan->update($data);

                return ['personel' => array_values($perluEmail), 'tanpa_email' => $tanpaEmail, 'bukti_lama' => $buktiLama];
            });
        } catch (Throwable $exception) {
            if ($pathBuktiBaru) {
                Storage::disk('local')->delete($pathBuktiBaru);
            }

            throw $exception;
        }

        if ($pathBuktiBaru && $hasil['bukti_lama']) {
            Storage::disk('local')->delete($hasil['bukti_lama']);
        }

        return ['personel' => $hasil['personel'], 'tanpa_email' => $hasil['tanpa_email']];
    }

    /**
     * Personel dikunci berurutan berdasarkan id agar dua penugasan paralel tidak saling menunggu (deadlock).
     *
     * @param  array<string, mixed>  $input
     * @return Collection<int, PersonelOperasional>
     */
    private function kunciPersonel(array $input)
    {
        $idTerpilih = collect(self::PERAN)
            ->map(fn (array $kolom): ?int => filled($input[$kolom['id']] ?? null) ? (int) $input[$kolom['id']] : null)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return PersonelOperasional::query()
            ->with('user')
            ->whereIn('id', $idTerpilih)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function pastikanBolehDitugaskan(string $peran, string $kolomId, ?int $idBaru, ?PersonelOperasional $personel, Pesanan $pesanan): void
    {
        if ($idBaru && (! $personel || $personel->peran !== $peran || ! $personel->aktif)) {
            throw ValidationException::withMessages([$kolomId => 'Pilih personel aktif dengan peran yang sesuai.']);
        }

        if (! $personel) {
            return;
        }

        if ($personel->status_operasional !== 'siap') {
            throw ValidationException::withMessages([$kolomId => 'Personel sedang bertugas dan tidak bisa dipilih untuk jadwal ini.']);
        }

        if ($this->ketersediaan->memilikiJadwalBentrok((int) $personel->id, $pesanan)) {
            throw ValidationException::withMessages([$kolomId => 'Personel memiliki pesanan aktif lain pada rentang tanggal tersebut.']);
        }

        $akhirTrip = $pesanan->tanggal_selesai ?? $pesanan->tanggal_trip;
        if (! $personel->simBerlakuSampai($akhirTrip)) {
            throw ValidationException::withMessages([$kolomId => $personel->masa_berlaku_sim
                ? "SIM guide habis pada {$personel->masa_berlaku_sim->translatedFormat('d M Y')}, sebelum trip selesai ({$akhirTrip->translatedFormat('d M Y')}). Perbarui data SIM atau pilih guide lain."
                : 'Data SIM guide belum diisi. Lengkapi dulu di menu Guide agar bisa ditugaskan sebagai driver.']);
        }
    }

    /** @param  array<string, mixed>  $input */
    private function catatanBerubah(Pesanan $pesanan, array $input): bool
    {
        if (! array_key_exists('catatan_admin', $input)) {
            return false;
        }

        $baru = trim((string) $input['catatan_admin']);

        return $baru !== '' && $baru !== trim((string) $pesanan->catatan_admin);
    }

    /**
     * Satu personel hanya menerima satu email per penyimpanan, walau ada penugasan baru sekaligus catatan baru.
     *
     * @param  array<int, PersonelOperasional>  $perluEmail
     */
    private function antreEmail(PersonelOperasional $personel, array &$perluEmail, int &$tanpaEmail): void
    {
        if (isset($perluEmail[$personel->id])) {
            return;
        }

        if ($this->email->alamatTujuan($personel)) {
            $perluEmail[$personel->id] = $personel;
        } else {
            $tanpaEmail++;
        }
    }
}
