@props(['status', 'pesanan' => null])

@php
    // Peta satu-satunya untuk warna lencana status pesanan (dipakai Pesanan & Pesanan Kustom).
    // Kunci cocok dengan nilai enum BookingStatus yang akan dibuat di fase Pesanan.
    $peta = [
        'dihubungi' => ['label' => 'Dihubungi', 'bg' => 'bg-status-dihubungi-bg', 'teks' => 'text-status-dihubungi-teks'],
        'payment' => ['label' => 'Pembayaran', 'bg' => 'bg-status-payment-bg', 'teks' => 'text-status-payment-teks'],
        'hold_schedule' => ['label' => 'Jadwal Ditahan', 'bg' => 'bg-status-hold-schedule-bg', 'teks' => 'text-status-hold-schedule-teks'],
        'completed' => ['label' => 'Trip Selesai', 'bg' => 'bg-status-completed-bg', 'teks' => 'text-status-completed-teks'],
        'dibatalkan' => ['label' => 'Dibatalkan', 'bg' => 'bg-status-dibatalkan-bg', 'teks' => 'text-status-dibatalkan-teks'],
    ];

    $kunci = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $kunci = match ($kunci) {
        'baru' => 'dihubungi',
        'diproses' => 'payment',
        'menunggu_jawaban' => 'hold_schedule',
        'disetujui' => 'completed',
        'tidak_jadi' => 'dibatalkan',
        default => $kunci,
    };
    $gaya = $peta[$kunci] ?? ['label' => $kunci, 'bg' => 'bg-gray-100', 'teks' => 'text-gray-600'];

    if ($status instanceof \App\Enums\StatusPesananKustom) {
        $gaya['label'] = $status->label();
    }

    // Pesanan biasa memakai tahap alur (Pesanan::tahap()): satu label dan warna per tahap.
    if ($pesanan instanceof \App\Models\Pesanan) {
        $warnaTahap = [
            'dihubungi' => 'bg-status-dihubungi-bg text-status-dihubungi-teks',
            'menunggu_pembayaran' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-700/20',
            'sudah_bayar' => 'bg-status-payment-bg text-status-payment-teks',
            'lunas' => 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-700/20',
            'berlangsung' => 'bg-admin-sidebar text-white',
            'selesai' => 'bg-status-completed-bg text-status-completed-teks',
            'jadwal_ditahan' => 'bg-status-hold-schedule-bg text-status-hold-schedule-teks',
            'dibatalkan' => 'bg-status-dibatalkan-bg text-status-dibatalkan-teks',
        ];
        $gaya = ['label' => $pesanan->labelTahap(), 'bg' => $warnaTahap[$pesanan->tahap()], 'teks' => ''];
    }
@endphp

<span {{ $attributes->class(["inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {$gaya['bg']} {$gaya['teks']}"]) }}>
    {{ $gaya['label'] }}
</span>
