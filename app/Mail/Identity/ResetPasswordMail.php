<?php

namespace App\Mail\Identity;

use App\Models\Identity\Funcionario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Funcionario $funcionario,
        public string $token,
        public int $expiresInMinutes = 60
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperação de Senha - CiViTas',
        );
    }

    public function content(): Content
    {
        $resetUrl = config('app.url').'/reset-password?token='.$this->token.'&email='.urlencode($this->funcionario->email);

        return new Content(
            view: 'emails.reset-password',
            with: [
                'funcionario' => $this->funcionario,
                'nome' => $this->funcionario->nome,
                'resetUrl' => $resetUrl,
                'token' => $this->token,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
