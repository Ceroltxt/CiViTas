<?php

namespace App\Application\Gamification;

use App\Models\Task\Tarefa;
use App\Models\Gamification\PontosUsuario;
use Illuminate\Support\Facades\DB;

class TaskPointsService
{
    /**
     * Registra a pontuação da conclusão para todos os colaboradores.
     * Deve ser chamado quando o gestor aprovar a tarefa.
     */
    public function awardForCompletion(Tarefa $task): void
    {
        if ($task->pessoal || ! $task->data_conclusao) {
            return;
        }

        $task->loadMissing('colaboradores');

        $prazo = $task->data_prazo?->toDateString();
        $conclusao = $task->data_conclusao->toDateString();

        $pontos = match (true) {
            $prazo === null => 0,
            $conclusao < $prazo => 2,
            $conclusao === $prazo => 1,
            default => 0,
        };

        foreach ($task->colaboradores as $colaborador) {
            $chave = "conclusao:{$task->ID_tarefa}:{$colaborador->matricula_funcionario}";

            DB::transaction(function () use (
                $colaborador,
                $task,
                $pontos,
                $chave
            ): void {
                $registro = PontosUsuario::query()->firstOrCreate(
                    ['chave_idempotencia' => $chave],
                    [
                        'pontos' => $pontos,
                        'acao' => 'Conclusão de tarefa',
                        'data' => now(),
                        'matricula_funcionario' => $colaborador->matricula_funcionario,
                        'ID_tarefa' => $task->ID_tarefa,
                    ]
                );

                if ($registro->wasRecentlyCreated && $pontos !== 0) {
                    $colaborador->newQuery()
                        ->where(
                            'matricula_funcionario',
                            $colaborador->matricula_funcionario
                        )
                        ->increment('pontos_totais', $pontos);
                }
            });
        }
    }

    /**
     * Aplica -2 pontos por reabertura válida a cada colaborador.
     * Cada número de reabertura representa um evento independente.
     */
    public function penalizeReopening(
        Tarefa $task,
        int $numeroReabertura
    ): void {
        if (
            $task->pessoal ||
            ! $task->foi_reaberta ||
            $numeroReabertura < 1
        ) {
            return;
        }

        DB::transaction(function () use (
            $task,
            $numeroReabertura
        ): void {
            $tarefaAtualizada = Tarefa::query()
                ->where('ID_tarefa', $task->ID_tarefa)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $tarefaAtualizada->pessoal ||
                ! $tarefaAtualizada->foi_reaberta ||
                (int) $tarefaAtualizada->quantidade_reaberturas
                    < $numeroReabertura
            ) {
                return;
            }

            $tarefaAtualizada->load('colaboradores');

            foreach ($tarefaAtualizada->colaboradores as $colaborador) {
                $chave = sprintf(
                    'reabertura:%s:%s:%s',
                    $tarefaAtualizada->ID_tarefa,
                    $numeroReabertura,
                    $colaborador->matricula_funcionario
                );

                $registro = PontosUsuario::query()->firstOrCreate(
                    ['chave_idempotencia' => $chave],
                    [
                        'pontos' => -2,
                        'acao' => 'Penalidade por reabertura de tarefa',
                        'data' => now(),
                        'matricula_funcionario' =>
                            $colaborador->matricula_funcionario,
                        'ID_tarefa' => $tarefaAtualizada->ID_tarefa,
                    ]
                );

                if ($registro->wasRecentlyCreated) {
                    $colaborador->newQuery()
                        ->where(
                            'matricula_funcionario',
                            $colaborador->matricula_funcionario
                        )
                        ->decrement('pontos_totais', 2);
                }
            }

            $tarefaAtualizada->penalidade_reabertura_aplicada = true;
            $tarefaAtualizada->save();
        });
    }
}