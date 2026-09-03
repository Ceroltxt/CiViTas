<?php

namespace App\Domain\Authorization\Concerns;

use App\Domain\Authorization\AppProfile;
use App\Models\Identity\Funcionario;
use App\Models\Project\Projeto;
use App\Models\Task\Tarefa;
use App\Models\Team\Equipe;

/**
 * Regras de escopo compartilhadas entre policies (o "onde", não o "se pode").
 */
trait ResolvesFuncionarioAccess
{
    protected function profile(Funcionario $funcionario): AppProfile
    {
        return AppProfile::fromCargoName($funcionario->cargo?->nome_cargo);
    }

    protected function isAdmin(Funcionario $funcionario): bool
    {
        return $this->profile($funcionario) === AppProfile::Admin;
    }

    protected function isGestor(Funcionario $funcionario): bool
    {
        return $this->profile($funcionario) === AppProfile::Gestor;
    }

    protected function isColaborador(Funcionario $funcionario): bool
    {
        return $this->profile($funcionario) === AppProfile::Colaborador;
    }

    protected function managesProject(Funcionario $funcionario, ?Projeto $projeto): bool
    {
        if ($projeto === null) {
            return false;
        }

        return (int) $projeto->ID_matricula_admin === (int) $funcionario->matricula_funcionario;
    }

    protected function managesTeam(Funcionario $funcionario, ?Equipe $equipe): bool
    {
        if ($equipe === null) {
            return false;
        }

        return (int) $equipe->matricula_gestor === (int) $funcionario->matricula_funcionario;
    }

    protected function belongsToProjectTeam(Funcionario $funcionario, ?Projeto $projeto): bool
    {
        if ($projeto === null) {
            return false;
        }

        return $projeto->equipes()
            ->whereHas(
                'membros',
                fn ($query) => $query->where(
                    'equipe_funcionario.matricula_funcionario',
                    $funcionario->matricula_funcionario,
                ),
            )
            ->exists();
    }

    protected function isMemberOfTeam(Funcionario $funcionario, ?Equipe $equipe): bool
    {
        if ($equipe === null) {
            return false;
        }

        return $equipe->membros()
            ->where('funcionario.matricula_funcionario', $funcionario->matricula_funcionario)
            ->exists();
    }

    protected function isAssignedToTask(Funcionario $funcionario, Tarefa $tarefa): bool
    {
        return $tarefa->colaboradores()
            ->where('funcionario.matricula_funcionario', $funcionario->matricula_funcionario)
            ->exists();
    }

    protected function isTaskGestor(Funcionario $funcionario, Tarefa $tarefa): bool
    {
        return (int) $tarefa->matricula_gestor === (int) $funcionario->matricula_funcionario;
    }

    protected function ownsPersonalTask(Funcionario $funcionario, Tarefa $tarefa): bool
    {
        return $tarefa->pessoal
            && (int) $tarefa->matricula_gestor === (int) $funcionario->matricula_funcionario;
    }

    /**
     * Colaborador enxerga tarefa se: pessoal dele, atribuída, gestor da tarefa,
     * ou membro de equipe/projeto vinculado.
     */
    protected function canViewTaskInScope(Funcionario $funcionario, Tarefa $tarefa): bool
    {
        if ($this->isAdmin($funcionario)) {
            return true;
        }

        $tarefa->loadMissing(['projeto.equipes.membros', 'equipe.membros', 'colaboradores']);

        if ($this->ownsPersonalTask($funcionario, $tarefa)) {
            return true;
        }

        if ($this->isAssignedToTask($funcionario, $tarefa)) {
            return true;
        }

        if ($this->isTaskGestor($funcionario, $tarefa)) {
            return true;
        }

        if ($this->managesProject($funcionario, $tarefa->projeto)) {
            return true;
        }

        if ($this->managesTeam($funcionario, $tarefa->equipe)) {
            return true;
        }

        if ($this->isMemberOfTeam($funcionario, $tarefa->equipe)) {
            return true;
        }

        return $this->belongsToProjectTeam($funcionario, $tarefa->projeto);
    }

    /**
     * Gestor edita tarefas dos projetos/equipes que administra.
     * Colaborador edita tarefas atribuídas ou pessoais.
     */
    /**
     * Permissão para alterar status, marcar subtarefa e adicionar comentário (Atribuído, Gestor, Criador, Admin).
     */
    protected function canMutateTaskInScope(Funcionario $funcionario, Tarefa $tarefa): bool
    {
        if ($this->isAdmin($funcionario)) {
            return true;
        }

        $tarefa->loadMissing(['projeto', 'equipe']);

        if ($this->ownsPersonalTask($funcionario, $tarefa)) {
            return true;
        }

        if ($this->isGestor($funcionario)) {
            return $this->managesProject($funcionario, $tarefa->projeto)
                || $this->managesTeam($funcionario, $tarefa->equipe)
                || $this->isTaskGestor($funcionario, $tarefa);
        }

        return $this->isAssignedToTask($funcionario, $tarefa)
            || $this->isTaskGestor($funcionario, $tarefa);
    }

    /**
     * Permissão estrita para Editar Título/Descrição ou Excluir a Tarefa: Apenas o criador, Gestor do projeto/equipe ou Admin.
     */
    protected function canEditOrDeleteTask(Funcionario $funcionario, Tarefa $tarefa): bool
    {
        if ($this->isAdmin($funcionario)) {
            return true;
        }

        $tarefa->loadMissing(['projeto', 'equipe']);

        if ($this->ownsPersonalTask($funcionario, $tarefa)) {
            return true;
        }

        if ($this->isTaskGestor($funcionario, $tarefa)) {
            return true;
        }

        if ($this->isGestor($funcionario)) {
            return $this->managesProject($funcionario, $tarefa->projeto)
                || $this->managesTeam($funcionario, $tarefa->equipe);
        }

        return false;
    }
}
