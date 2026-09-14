<?php

namespace App\Events\Identity;

use App\Models\Identity\Funcionario;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FuncionarioRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Funcionario $funcionario
    ) {}
}
