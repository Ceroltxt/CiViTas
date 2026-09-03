<?php

namespace App\Policies\Task;

use App\Domain\Authorization\Concerns\ResolvesFuncionarioAccess;
use App\Domain\Authorization\Permissions;
use App\Models\Identity\Funcionario;
use App\Models\Task\Tarefa;

class TarefaPolicy
{
    use ResolvesFuncionarioAccess;

    public function viewAny(Funcionario $funcionario): bool
    {
        return $funcionario->hasPermission(Permissions::TASKS_VIEW);
    }

    public function view(Funcionario $funcionario, Tarefa $tarefa): bool
    {
        return $funcionario->hasPermission(Permissions::TASKS_VIEW)
            && $this->canViewTaskInScope($funcionario, $tarefa);
    }

    public function create(Funcionario $funcionario): bool
    {
        return $funcionario->hasPermission(Permissions::TASKS_CREATE)
            || $funcionario->hasPermission(Permissions::TASKS_CREATE_PERSONAL);
    }

    /**
     * Criação de tarefa pessoal (colaborador).
     */
    public function createPersonal(Funcionario $funcionario): bool
    {
        return $funcionario->hasPermission(Permissions::TASKS_CREATE_PERSONAL);
    }

    public function update(Funcionario $funcionario, Tarefa $tarefa): bool
    {
        return $funcionario->hasPermission(Permissions::TASKS_UPDATE)
            && $this->canEditOrDeleteTask($funcionario, $tarefa);
    }

    public function updateStatus(Funcionario $funcionario, Tarefa $tarefa): bool
    {
        return $funcionario->hasPermission(Permissions::TASKS_UPDATE)
            && $this->canMutateTaskInScope($funcionario, $tarefa);
    }

    public function delete(Funcionario $funcionario, Tarefa $tarefa): bool
    {
        return $funcionario->hasPermission(Permissions::TASKS_DELETE)
            && $this->canEditOrDeleteTask($funcionario, $tarefa);
    }

    /**
     * Atribuir tarefa a outros colaboradores — gestor/admin apenas.
     */
    public function assign(Funcionario $funcionario, Tarefa $tarefa): bool
    {
        if (! $funcionario->hasPermission(Permissions::TASKS_ASSIGN)) {
            return false;
        }

        if ($this->isAdmin($funcionario)) {
            return true;
        }

        if ($this->isGestor($funcionario)) {
            $tarefa->loadMissing(['projeto', 'equipe']);

            return $this->managesProject($funcionario, $tarefa->projeto)
                || $this->managesTeam($funcionario, $tarefa->equipe)
                || $this->isTaskGestor($funcionario, $tarefa);
        }

        return false;
    }

    public function attach(Funcionario $funcionario, Tarefa $tarefa): bool
    {
        return $funcionario->hasPermission(Permissions::TASKS_ATTACH)
            && $this->canMutateTaskInScope($funcionario, $tarefa);
    }
}
