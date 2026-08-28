<?php

namespace App\Policies\Project;

use App\Domain\Authorization\Concerns\ResolvesFuncionarioAccess;
use App\Domain\Authorization\Permissions;
use App\Models\Identity\Funcionario;
use App\Models\Project\Projeto;

class ProjetoPolicy
{
    use ResolvesFuncionarioAccess;

    public function viewAny(Funcionario $funcionario): bool
    {
        return $funcionario->hasPermission(Permissions::PROJECTS_VIEW);
    }

    public function view(Funcionario $funcionario, Projeto $projeto): bool
    {
        if (! $funcionario->hasPermission(Permissions::PROJECTS_VIEW)) {
            return false;
        }

        if ($this->isAdmin($funcionario)) {
            return true;
        }

        if ($this->managesProject($funcionario, $projeto)) {
            return true;
        }

        return $this->belongsToProjectTeam($funcionario, $projeto)
            || $projeto->tarefas()
                ->whereHas(
                    'colaboradores',
                    fn ($q) => $q->where(
                        'tarefa_funcionario.matricula_colaborador',
                        $funcionario->matricula_funcionario,
                    ),
                )
                ->exists();
    }

    public function create(Funcionario $funcionario): bool
    {
        return $funcionario->hasPermission(Permissions::PROJECTS_MANAGE);
    }

    public function update(Funcionario $funcionario, Projeto $projeto): bool
    {
        if (! $funcionario->hasPermission(Permissions::PROJECTS_MANAGE)) {
            return false;
        }

        return $this->isAdmin($funcionario) || $this->managesProject($funcionario, $projeto);
    }

    public function delete(Funcionario $funcionario, Projeto $projeto): bool
    {
        return $this->isAdmin($funcionario)
            && $funcionario->hasPermission(Permissions::PROJECTS_MANAGE);
    }
}
