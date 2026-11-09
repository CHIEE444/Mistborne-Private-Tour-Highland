<?php

namespace App\Mail;

use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PenugasanPersonel extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Pesanan $pesanan,
        public PersonelOperasional $personel,
    ) {}

    public function envelope(): Envelope
    {
        $kolomStatus = 'status_konfirmasi_guide';
        $awal = $this->pesanan->{$kolomStatus} === 'menunggu' ? 'Penugasan perjalanan ' : 'Pembaruan perjalanan ';

        return new Envelope(subject: $awal.$this->pesanan->kode.' — MISTBORNE');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.personel.penugasan', text: 'mail.personel.penugasan-teks');
    }
}
