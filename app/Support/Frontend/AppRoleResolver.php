<?php

namespace App\Support\Frontend;

use App\Domain\Authorization\AppProfile;
use App\Models\Identity\Funcionario;

class AppRoleResolver
{
    public static function resolve(?string $cargoNome): string
    {
        return AppProfile::fromCargoName($cargoNome)->label();
    }

    public static function resolveFromFuncionario(Funcionario $funcionario): string
    {
        return $funcionario->profile()->label();
    }

    public static function appRoleKey(Funcionario $funcionario): string
    {
        return $funcionario->profile()->value;
    }
}
