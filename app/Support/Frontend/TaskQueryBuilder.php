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

        return Tarefa::query()
            ->with([
                'status',
                'projeto.tarefas.status',
                'equipe',
                'colaboradores.cargo',
                'subtarefas.colaborador',
                'historico.funcionario',
            ])
            ->where(function (Builder $query) use ($matricula): void {
                $query
                    ->whereHas(
                        'colaboradores',
                        fn (Builder $q) => $q->where('tarefa_funcionario.matricula_colaborador', $matricula),
                    )
                    ->orWhere('matricula_gestor', $matricula)
                    ->orWhere(function (Builder $q) use ($matricula): void {
                        $q->where('pessoal', true)->where('matricula_gestor', $matricula);
                    })
                    ->orWhereHas(
                        'projeto.equipes.membros',
                        fn (Builder $q) => $q->where('equipe_funcionario.matricula_funcionario', $matricula),
                    );
            });
    }
}
