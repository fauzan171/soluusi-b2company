<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ChatNewMessage extends Mailable
{
    public function __construct(
        public string $konsultanType,
        public string $chatUrl,
    ) {}

    public function build(): static
    {
        return $this->subject('Pesan baru — sesi konsultasi')
            ->text('emails.chat-new-message');
    }
}
