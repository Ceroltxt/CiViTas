<?php

namespace App\Policies\Team;

use App\Domain\Authorization\Concerns\ResolvesFuncionarioAccess;
use App\Domain\Authorization\Permissions;
use App\Models\Identity\Funcionario;
use App\Models\Team\Equipe;

class EquipePolicy
{
    use ResolvesFuncionarioAccess;

    public function viewAny(Funcionario $funcionario): bool
    {
        return $funcionario->hasPermission(Permissions::TEAMS_VIEW);
    }

    public function view(Funcionario $funcionario, Equipe $equipe): bool
    {
        if (! $funcionario->hasPermission(Permissions::TEAMS_VIEW)) {
            return false;
        }

        if ($this->isAdmin($funcionario)) {
            return true;
        }

        return $this->managesTeam($funcionario, $equipe)
            || $this->isMemberOfTeam($funcionario, $equipe);
    }

    public function create(Funcionario $funcionario): bool
    {
        return $funcionario->hasPermission(Permissions::TEAMS_MANAGE);
    }

    public function update(Funcionario $funcionario, Equipe $equipe): bool
    {
        if (! $funcionario->hasPermission(Permissions::TEAMS_MANAGE)) {
            return false;
        }

        return $this->isAdmin($funcionario) || $this->managesTeam($funcionario, $equipe);
    }

    public function delete(Funcionario $funcionario, Equipe $equipe): bool
    {
        return $this->isAdmin($funcionario)
            && $funcionario->hasPermission(Permissions::TEAMS_MANAGE);
    }
}
