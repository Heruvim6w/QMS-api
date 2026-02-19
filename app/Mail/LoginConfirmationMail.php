<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User       $user,
        public LoginToken $loginToken,
        public string     $deviceName,
    )
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Подтверждение входа в аккаунт',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.login-confirmation',
            with: [
                'user' => $this->user,
                'deviceName' => $this->deviceName,
                'confirmationUrl' => route('auth.confirm-login', ['token' => $this->loginToken->token]),
                'expiresAt' => $this->loginToken->expires_at->format('H:i (d.m.Y)'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

