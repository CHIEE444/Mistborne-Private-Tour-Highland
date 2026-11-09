<?php

namespace App\Mail;

use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Kabar ke guide bahwa pesanan yang ditugaskan kepadanya dibatalkan, supaya tidak berangkat ke trip yang sudah batal. */
class PembatalanPenugasanPersonel extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Pesanan $pesanan,
        public PersonelOperasional $personel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Perjalanan '.$this->pesanan->kode.' DIBATALKAN — MISTBORNE');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.personel.pembatalan', text: 'mail.personel.pembatalan-teks');
    }
}
