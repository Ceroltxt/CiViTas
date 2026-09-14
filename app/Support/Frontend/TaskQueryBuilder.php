<?php

namespace App\Support\Frontend;

use App\Models\Identity\Funcionario;
use App\Models\Task\Tarefa;
use Illuminate\Database\Eloquent\Builder;

class TaskQueryBuilder
{
    /**
     * @return Builder<Tarefa>
     */
    public static function forFuncionario(Funcionario $funcionario): Builder
    {
        $matricula = $funcionario->matricula_funcionario;
        $workspaceId = app()->bound('workspace_id') ? app('workspace_id') : null;
        $role = app()->bound('workspace_role') ? app('workspace_role') : 'colaborador';

        $query = Tarefa::query()
            ->with([
                'status',
                'projeto.tarefas.status',
                'equipe',
                'colaboradores.cargo',
                'subtarefas.colaborador',
                'historico.funcionario',
            ]);

        // Se estiver num contexto de workspace, filtra as tarefas para este workspace (ou tarefas pessoais)
        if ($workspaceId) {
            $query->where(function (Builder $q) use ($workspaceId) {
                $q->whereHas('equipe', fn ($eq) => $eq->where('workspace_id', $workspaceId))
                  ->orWhere('pessoal', true);
            });
        }

        // Se for admin do workspace, ele vê tudo deste workspace.
        // Se não for admin (gestor/colaborador), vê apenas o que está envolvido:
        if ($role !== 'admin') {
            $query->where(function (Builder $q) use ($matricula): void {
                $q->whereHas(
                        'colaboradores',
                        fn (Builder $sq) => $sq->where('tarefa_funcionario.matricula_colaborador', $matricula),
                    )
                    ->orWhere('matricula_gestor', $matricula)
                    ->orWhere(function (Builder $sq) use ($matricula): void {
                        $sq->where('pessoal', true)->where('matricula_gestor', $matricula);
                    })
                    ->orWhereHas(
                        'projeto.equipes.membros',
                        fn (Builder $sq) => $sq->where('equipe_funcionario.matricula_funcionario', $matricula),
                    );
            });
        }

        return $query;
    }
}
