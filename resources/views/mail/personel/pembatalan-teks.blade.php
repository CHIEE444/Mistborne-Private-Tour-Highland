@php
    $akhir = $pesanan->tanggal_selesai ?? $pesanan->tanggal_trip;
    $tanggalTeks = $pesanan->tanggal_trip->translatedFormat('d F Y').($akhir->isSameDay($pesanan->tanggal_trip) ? '' : ' s.d. '.$akhir->translatedFormat('d F Y'));
@endphp
PERJALANAN DIBATALKAN — MISTBORNE

Halo {{ $personel->nama }},

Perjalanan berikut DIBATALKAN. Tugas Anda untuk trip ini berakhir dan Anda tidak perlu berangkat.

Kode pesanan : {{ $pesanan->kode }}
Perjalanan   : {{ $pesanan->nama_perjalanan }}
Tanggal      : {{ $tanggalTeks }}
Tamu         : {{ $pesanan->nama_lengkap }}
@if (filled($pesanan->alasan_batal))
Alasan       : {{ $pesanan->alasan_batal }}
@endif

Jadwal Anda pada tanggal tersebut kembali kosong dan bisa menerima penugasan lain.
Panel Guide: {{ route('personel.masuk') }}
