<?php

namespace App\Application\Identity\Auth;

use App\Models\Identity\Funcionario;

class LogoutAction
{
    public function execute(Funcionario $funcionario): void
    {
        $funcionario->currentAccessToken()?->delete();
    }
}
