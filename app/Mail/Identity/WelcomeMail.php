<?php

namespace App\Mail\Identity;

use App\Models\Identity\Funcionario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Funcionario $funcionario
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Bem-vindo ao CiViTas, {$this->funcionario->nome}!",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'funcionario' => $this->funcionario,
                'nome' => $this->funcionario->nome,
                'departamento' => $this->funcionario->departamento?->nome_departamento ?? 'Equipe Geral',
                'cargo' => $this->funcionario->cargo?->nome_cargo ?? 'Colaborador',
                'loginUrl' => config('app.url').'/login',
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
