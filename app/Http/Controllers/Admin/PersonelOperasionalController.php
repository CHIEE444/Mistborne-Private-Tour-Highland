<?php

namespace App\Http\Controllers\Admin;

use App\Bantuan\AturanNama;
use App\Bantuan\NomorWhatsApp;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Kendaraan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonelOperasionalController extends Controller
{
    public function index(): View
    {
        $personel = PersonelOperasional::query()->with('user')->orderBy('peran')->orderBy('nama')->get();
        $pesananAkanDatang = Pesanan::query()
            ->whereIn('status', [BookingStatus::Dihubungi->value, BookingStatus::Payment->value, BookingStatus::HoldSchedule->value])
            ->where(function ($query): void {
                $query->whereDate('tanggal_selesai', '>=', today()->toDateString())
                    ->orWhere(function ($query): void {
                        $query->whereNull('tanggal_selesai')
                            ->whereDate('tanggal_trip', '>=', today()->toDateString());
                    });
            })
            ->orderBy('tanggal_trip')
            ->get([
                'id', 'kode', 'nama_perjalanan', 'tanggal_trip', 'tanggal_selesai', 'waktu_pickup', 'lokasi_pickup',
                'guide_personel_id', 'status_konfirmasi_guide', 'guide_dikonfirmasi_at', 'status',
            ]);

        $statusPenugasan = $personel->mapWithKeys(function (PersonelOperasional $orang) use ($pesananAkanDatang): array {
            $kolomId = 'guide_personel_id';
            $kolomStatus = 'status_konfirmasi_guide';
            $jadwalOrang = $pesananAkanDatang->filter(fn (Pesanan $pesanan) => (int) $pesanan->{$kolomId} === (int) $orang->id
                && $pesanan->{$kolomStatus} !== 'ditolak'
            );

            $menungguKonfirmasi = $jadwalOrang->first(fn (Pesanan $pesanan) => $pesanan->{$kolomStatus} === 'menunggu');
            $sedangBertugas = $jadwalOrang->first(fn (Pesanan $pesanan) => $pesanan->{$kolomStatus} === 'diterima'
                &&
                $pesanan->tanggal_trip->lte(today())
                && ($pesanan->tanggal_selesai ?? $pesanan->tanggal_trip)->gte(today())
            );
            $jadwalBerikutnya = $jadwalOrang->first(fn (Pesanan $pesanan) => $pesanan->{$kolomStatus} === 'diterima');

            return [$orang->id => [
                'status' => $sedangBertugas ? 'bertugas' : ($menungguKonfirmasi ? 'menunggu' : ($jadwalBerikutnya ? 'terjadwal' : $orang->status_operasional)),
                'jadwal' => $jadwalOrang->values(),
            ]];
        });

        return view('admin.guide.indeks', [
            'personel' => $personel,
            'statusPenugasan' => $statusPenugasan,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('foto')) {
            $pathFoto = $request->file('foto')->store('personel', 'local');
            if (! $pathFoto) {
                throw ValidationException::withMessages(['foto' => 'Foto gagal disimpan. Periksa penyimpanan lalu coba lagi.']);
            }
            $data['foto_profil'] = $pathFoto;
        }

        $buatAkunPanel = $request->boolean('buat_akun_panel');
        if ($buatAkunPanel && blank($data['email'] ?? null)) {
            throw ValidationException::withMessages(['email' => 'Isi email personel untuk membuat atau menautkan akun panel.']);
        }

        $akunBaru = null;
        try {
            DB::transaction(function () use ($data, $buatAkunPanel, &$akunBaru): void {
                if ($buatAkunPanel) {
                    [$data['user_id'], $akunBaru] = $this->akunPanelUntuk($data['email'], $data['peran'], $data['nama']);
                }

                PersonelOperasional::create($data);
            });
        } catch (\Throwable $exception) {
            if (! empty($data['foto_profil'])) {
                Storage::disk('local')->delete($data['foto_profil']);
            }

            throw $exception;
        }

        if ($akunBaru) {
            return back()->with('sukses', 'Personel ditambahkan dan akun panel dibuat. '.$this->infoMasukAkun($akunBaru));
        }

        return back()->with('sukses', $buatAkunPanel
            ? 'Personel ditambahkan dan ditautkan ke akun panel yang sudah ada.'
            : 'Data guide berhasil ditambahkan. Email disimpan sebagai kontak personel.');
    }

    /**
     * Mencari akun yang cocok untuk email personel atau membuat akun baru.
     * Akun yang sudah ada hanya boleh ditautkan jika jabatannya sama dengan peran dan belum dipakai personel lain.
     *
     * @return array{0: int, 1: ?User} id akun dan akun baru (null jika akun sudah ada)
     */
    private function akunPanelUntuk(string $email, string $peran, string $nama): array
    {
        $akun = User::query()->where('email', $email)->lockForUpdate()->first();

        if (! $akun) {
            $akun = User::query()->create([
                'name' => $nama,
                'email' => $email,
                'password' => (string) config('mistborne.sandi_bawaan_guide'),
                'jabatan' => $peran,
            ]);

            return [$akun->id, $akun];
        }

        if (mb_strtolower(trim((string) $akun->jabatan)) !== $peran) {
            throw ValidationException::withMessages(['email' => 'Email ini sudah dipakai akun lain dengan jabatan berbeda, sehingga tidak bisa dijadikan akun '.$peran.'.']);
        }

        if (PersonelOperasional::query()->where('user_id', $akun->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'Akun dengan email ini sudah tertaut ke personel lain.']);
        }

        return [$akun->id, null];
    }

    /** Akun panel baru langsung memakai kata sandi bawaan, jadi admin cukup menyampaikan data masuk ini ke guide. */
    private function infoMasukAkun(User $akun): string
    {
        return 'Masuk Panel Guide dengan email '.$akun->email.' dan kata sandi "'.config('mistborne.sandi_bawaan_guide').'".';
    }

    public function update(Request $request, PersonelOperasional $personel): RedirectResponse
    {
        $data = $this->validated($request, $personel->id);
        $fotoLama = $personel->foto_profil;
        $pathFotoBaru = null;
        $akunBaru = null;
        $fieldFoto = $request->exists('personel.'.$personel->id) ? 'personel.'.$personel->id.'.foto' : 'foto';
        $fieldPeran = $request->exists('personel.'.$personel->id) ? 'personel.'.$personel->id.'.peran' : 'peran';
        $fieldAktif = $request->exists('personel.'.$personel->id) ? 'personel.'.$personel->id.'.aktif' : 'aktif';
        if ($request->hasFile($fieldFoto)) {
            $pathFotoBaru = $request->file($fieldFoto)->store('personel', 'local');
            if (! $pathFotoBaru) {
                throw ValidationException::withMessages([$fieldFoto => 'Foto gagal disimpan. Periksa penyimpanan lalu coba lagi.']);
            }
            $data['foto_profil'] = $pathFotoBaru;
        }

        try {
            DB::transaction(function () use ($request, $personel, $data, $fieldPeran, $fieldAktif, &$akunBaru): void {
                $personel = PersonelOperasional::query()->whereKey($personel->id)->lockForUpdate()->firstOrFail();
                $peranBerubah = $data['peran'] !== $personel->peran;

                if ($peranBerubah && $this->punyaPesananAktif($personel)) {
                    throw ValidationException::withMessages([
                        $fieldPeran => 'Peran tidak dapat diubah selama personel masih ditugaskan pada pesanan aktif.',
                    ]);
                }

                if ($personel->aktif && ! $data['aktif'] && $this->punyaPesananAktif($personel)) {
                    throw ValidationException::withMessages([
                        $fieldAktif => 'Personel masih terhubung ke pesanan aktif. Alihkan atau tutup penugasan sebelum menonaktifkan profil.',
                    ]);
                }

                $akun = $personel->user;
                $personel->update($data);

                if ($request->boolean('buat_akun_panel') && ! $akun) {
                    $email = $data['email'] ?? $personel->email;
                    if (blank($email)) {
                        throw ValidationException::withMessages(['email' => 'Isi email personel untuk membuat atau menautkan akun panel.']);
                    }

                    [$idAkun, $akunBaru] = $this->akunPanelUntuk($email, $personel->peran, $personel->nama);
                    $personel->update(['user_id' => $idAkun]);
                }

                if ($peranBerubah && $akun) {
                    $jabatanAkun = mb_strtolower(trim((string) $akun->jabatan));
                    if ($jabatanAkun === 'guide') {
                        $akun->update(['jabatan' => $data['peran']]);
                    } else {
                        throw ValidationException::withMessages([
                            $fieldPeran => 'Peran akun panel terkait tidak dapat diubah menjadi Guide.',
                        ]);
                    }
                }

            });
        } catch (\Throwable $exception) {
            if ($pathFotoBaru) {
                Storage::disk('local')->delete($pathFotoBaru);
            }

            throw $exception;
        }

        if ($pathFotoBaru && $fotoLama) {
            Storage::disk('local')->delete($fotoLama);
        }

        if ($akunBaru) {
            return back()->with('sukses', 'Data personel diperbarui dan akun panel dibuat. '.$this->infoMasukAkun($akunBaru));
        }

        return back()->with('sukses', $request->boolean('buat_akun_panel')
            ? 'Data personel diperbarui dan ditautkan ke akun panel yang sudah ada.'
            : 'Data personel berhasil diperbarui. Email disimpan sebagai kontak personel.');
    }

    public function destroy(PersonelOperasional $personel): RedirectResponse
    {
        $foto = DB::transaction(function () use ($personel): string|false|null {
            $personel = PersonelOperasional::query()->whereKey($personel->id)->lockForUpdate()->firstOrFail();

            if ($this->punyaPesananAktif($personel)) {
                return false;
            }

            $foto = $personel->foto_profil;
            $personel->delete();

            return $foto;
        });

        if ($foto === false) {
            return back()->with('gagal', 'Personel masih ditugaskan pada pesanan aktif. Alihkan penugasan sebelum menghapus profil.');
        }

        if ($foto) {
            Storage::disk('local')->delete($foto);
        }

        return back()->with('sukses', 'Data personel berhasil dihapus.');
    }

    private function punyaPesananAktif(PersonelOperasional $personel): bool
    {
        return Pesanan::query()
            ->whereIn('status', [BookingStatus::Dihubungi, BookingStatus::Payment, BookingStatus::HoldSchedule])
            ->where('guide_personel_id', $personel->id)
            ->exists();
    }

    public function foto(PersonelOperasional $personel): StreamedResponse
    {
        abort_unless($personel->foto_profil && Storage::disk('local')->exists($personel->foto_profil), 404);

        return Storage::disk('local')->response($personel->foto_profil);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $personelId = null): array
    {
        $nestedDraft = $personelId !== null && $request->exists('personel.'.$personelId);
        $prefix = $nestedDraft ? "personel.{$personelId}." : '';
        $field = fn (string $name): string => $prefix.$name;

        if (filled($request->input($field('whatsapp')))) {
            $nomor = NomorWhatsApp::lokal($request->input($field('whatsapp')));
            $isian = $request->input('personel');
            if ($nestedDraft) {
                data_set($isian, "{$personelId}.whatsapp", $nomor);
            }
            $request->merge($nestedDraft ? ['personel' => $isian] : ['whatsapp' => $nomor]);
        }

        $namaMasuk = $request->input($field('nama'));
        if (is_string($namaMasuk)) {
            $isian = $request->input('personel');
            if ($nestedDraft) {
                data_set($isian, "{$personelId}.nama", AturanNama::rapikan($namaMasuk));
            }
            $request->merge($nestedDraft ? ['personel' => $isian] : ['nama' => AturanNama::rapikan($namaMasuk)]);
        }

        $data = $request->validate([
            $field('nama') => AturanNama::aturan(),
            $field('peran') => ['nullable', 'in:guide'],
            // Nomor WhatsApp dan email kontak boleh sama antar personel: selama uji coba semua guide memakai kontak bawaan (config mistborne.kontak_uji).
            $field('whatsapp') => ['nullable', 'string', 'regex:/^08[0-9]{7,12}$/'],
            $field('email') => ['nullable', 'email', 'max:255'],
            $field('wilayah_operasi') => ['nullable', 'string', 'max:255'],
            $field('pengalaman_tahun') => ['nullable', 'integer', 'min:0', 'max:60'],
            $field('keahlian') => ['nullable', 'string', 'max:2000'],
            // Guide merangkap driver: data kemampuan mengemudi wajib lengkap.
            $field('nomor_sim') => ['required', 'string', 'regex:/^[0-9A-Za-z\- ]{6,40}$/'],
            $field('jenis_sim') => ['required', Rule::in(array_keys(PersonelOperasional::JENIS_SIM))],
            $field('masa_berlaku_sim') => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now()->addYears(15)->toDateString()],
            $field('pengalaman_mengemudi_tahun') => ['required', 'integer', 'min:1', 'max:60'],
            $field('kendaraan_dikuasai') => ['required', 'array', 'min:1'],
            $field('kendaraan_dikuasai.*') => ['string', Rule::in(Kendaraan::daftarKategoriTamu())],
            $field('status_operasional') => ['required', 'in:siap,bertugas'],
            $field('catatan') => ['nullable', 'string', 'max:1000'],
            $field('foto') => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            $field('aktif') => ['nullable', 'boolean'],
        ], [
            ...collect(AturanNama::pesan('nama', 'Nama lengkap'))->mapWithKeys(fn (string $pesan, string $kunci): array => [$prefix.$kunci => $pesan])->all(),
            $field('peran.in') => 'Peran personel hanya Guide (merangkap driver).',
            $field('nomor_sim.required') => 'Nomor SIM wajib diisi: guide merangkap driver.',
            $field('jenis_sim.required') => 'Pilih golongan SIM.',
            $field('jenis_sim.in') => 'Pilih golongan SIM dari daftar.',
            $field('masa_berlaku_sim.required') => 'Masa berlaku SIM wajib diisi.',
            $field('masa_berlaku_sim.date_format') => 'Masa berlaku SIM tidak valid.',
            $field('masa_berlaku_sim.after_or_equal') => 'SIM sudah habis masa berlakunya. Perpanjang SIM dulu sebelum didaftarkan.',
            $field('masa_berlaku_sim.before_or_equal') => 'Masa berlaku SIM terlalu jauh. Periksa kembali tanggalnya.',
            $field('pengalaman_mengemudi_tahun.required') => 'Pengalaman mengemudi wajib diisi (minimal 1 tahun).',
            $field('pengalaman_mengemudi_tahun.integer') => 'Pengalaman mengemudi harus berupa jumlah tahun bulat.',
            $field('pengalaman_mengemudi_tahun.min') => 'Guide merangkap driver minimal berpengalaman mengemudi 1 tahun.',
            $field('pengalaman_mengemudi_tahun.max') => 'Pengalaman mengemudi maksimal 60 tahun.',
            $field('kendaraan_dikuasai.required') => 'Pilih minimal satu jenis kendaraan yang dikuasai.',
            $field('kendaraan_dikuasai.min') => 'Pilih minimal satu jenis kendaraan yang dikuasai.',
            $field('kendaraan_dikuasai.*.in') => 'Jenis kendaraan yang dipilih tidak dikenal.',
            $field('whatsapp.string') => 'Nomor WhatsApp harus berupa teks angka.',
            $field('whatsapp.regex') => 'Masukkan nomor WhatsApp dengan angka saja, diawali 08 atau +62, contoh 081234567890.',
            $field('email.email') => 'Masukkan alamat email yang valid, misalnya nama@email.com.',
            $field('email.max') => 'Email maksimal 255 karakter.',
            $field('wilayah_operasi.string') => 'Wilayah operasi harus berupa teks.',
            $field('wilayah_operasi.max') => 'Wilayah operasi maksimal 255 karakter.',
            $field('pengalaman_tahun.integer') => 'Pengalaman harus berupa jumlah tahun bulat atau dikosongkan untuk fresh graduate.',
            $field('pengalaman_tahun.min') => 'Pengalaman tidak boleh kurang dari 0 tahun.',
            $field('pengalaman_tahun.max') => 'Pengalaman maksimal 60 tahun.',
            $field('keahlian.string') => 'Keahlian atau spesialisasi harus berupa teks.',
            $field('keahlian.max') => 'Keahlian atau spesialisasi maksimal 2.000 karakter.',
            $field('nomor_sim.string') => 'Nomor SIM harus berupa teks.',
            $field('nomor_sim.regex') => 'Nomor SIM 6–40 karakter: huruf, angka, spasi, atau tanda hubung.',
            $field('status_operasional.required') => 'Pilih status ketersediaan personel.',
            $field('status_operasional.in') => 'Pilih status Siap ditugaskan atau Sedang bertugas.',
            $field('catatan.string') => 'Catatan tambahan harus berupa teks.',
            $field('catatan.max') => 'Catatan tambahan maksimal 1.000 karakter.',
            $field('foto.image') => 'Foto profil harus berupa gambar JPG, PNG, atau WebP.',
            $field('foto.mimes') => 'Foto profil harus berformat JPG, PNG, atau WebP.',
            $field('foto.max') => 'Ukuran foto profil maksimal 5 MB.',
            $field('foto.uploaded') => 'Foto gagal diunggah. Pastikan ukurannya maksimal 5 MB lalu coba lagi.',
            $field('aktif.boolean') => 'Status aktif personel tidak valid.',
        ]);

        if ($nestedDraft) {
            $data = $data['personel'][$personelId] ?? [];
        }

        if (filled($data['whatsapp'] ?? null) && str_starts_with($data['whatsapp'], '08')) {
            $data['whatsapp'] = '62'.substr($data['whatsapp'], 1);
        }

        $data['peran'] = PersonelOperasional::PERAN;
        $data['kendaraan_dikuasai'] = implode(', ', (array) ($data['kendaraan_dikuasai'] ?? []));

        return $data + ['aktif' => array_key_exists('aktif', $data) ? (bool) $data['aktif'] : $request->isMethod('post')];
    }
}
