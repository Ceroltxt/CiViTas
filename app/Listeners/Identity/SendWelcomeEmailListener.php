<?php

namespace App\Listeners\Identity;

use App\Events\Identity\FuncionarioRegistered;
use App\Mail\Identity\WelcomeMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailListener
{
    /**
     * Handle the event.
     */
    public function handle(FuncionarioRegistered $event): void
    {
        try {
            Mail::to($event->funcionario->email)->send(new WelcomeMail($event->funcionario));
        } catch (\Throwable $e) {
            Log::error('Erro ao enviar e-mail de boas-vindas: '.$e->getMessage(), [
                'matricula' => $event->funcionario->matricula_funcionario,
                'email' => $event->funcionario->email,
            ]);
        }
    }
}
