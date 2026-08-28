<?php

namespace App\Policies\Identity;

use App\Domain\Authorization\Concerns\ResolvesFuncionarioAccess;
use App\Domain\Authorization\Permissions;
use App\Models\Identity\Funcionario;

class FuncionarioPolicy
{
    use ResolvesFuncionarioAccess;

    public function viewAny(Funcionario $funcionario): bool
    {
        return $funcionario->hasPermission(Permissions::ACCESS_VIEW);
    }

    public function view(Funcionario $funcionario, Funcionario $model): bool
    {
        return $funcionario->hasPermission(Permissions::ACCESS_VIEW);
    }

    public function update(Funcionario $funcionario, Funcionario $model): bool
    {
        return $funcionario->hasPermission(Permissions::ACCESS_MANAGE);
    }
}
