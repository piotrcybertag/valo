<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WipRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $link,
        public string $okresNazwa,
        public string $dataKoncowaFormat,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Prośba o uzupełnienie WIP — ' . $this->okresNazwa,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.wip-request-html',
            text: 'emails.wip-request-text',
        );
    }
}
