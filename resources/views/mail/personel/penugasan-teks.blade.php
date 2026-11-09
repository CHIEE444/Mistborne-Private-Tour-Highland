@php
    $peranLabel = 'Guide';
    $kolomStatus = 'status_konfirmasi_guide';
    $penugasanBaru = $pesanan->{$kolomStatus} === 'menunggu';
    $akhir = $pesanan->tanggal_selesai ?? $pesanan->tanggal_trip;
    $tanggalTeks = $pesanan->tanggal_trip->translatedFormat('d F Y').($akhir->isSameDay($pesanan->tanggal_trip) ? '' : ' s.d. '.$akhir->translatedFormat('d F Y'));
    $jam = \App\Bantuan\JamPenjemputan::untukInput($pesanan->waktu_pickup);
@endphp
{{ $penugasanBaru ? 'PENUGASAN PERJALANAN BARU' : 'PEMBARUAN DARI ADMIN' }} — MISTBORNE

Halo {{ $personel->nama }},
@if ($penugasanBaru)
Anda mendapat penugasan sebagai Guide sekaligus Driver. Terima atau tolak tugas ini di panel.
@else
Admin memperbarui informasi untuk perjalanan yang Anda tangani.
@endif
@if (filled($pesanan->catatan_admin))

CATATAN ADMIN:
{{ $pesanan->catatan_admin }}
@endif

Kode pesanan : {{ $pesanan->kode }}
Perjalanan   : {{ $pesanan->nama_perjalanan }}
Tanggal      : {{ $tanggalTeks }}
Jam jemput   : {{ $jam ? $jam.' WIB (estimasi)' : 'Belum ditentukan' }}
Titik jemput : {{ $pesanan->lokasi_pickup }}
Jumlah tamu  : {{ $pesanan->jumlah_wisatawan }} orang
@if ($pesanan->kendaraan)
Kendaraan    : {{ $pesanan->kendaraan->nama }} · {{ $pesanan->kendaraan->plat_nomor }}
@endif

Panel Guide: {{ route('personel.masuk') }}
