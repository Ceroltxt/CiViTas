<?php

namespace App\Console\Commands;

use App\Models\Task\HistoricoTarefa;
use App\Models\Task\StatusTarefa;
use App\Models\Task\Tarefa;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('tasks:close-expired')]
#[Description('Fecha automaticamente tarefas cujo horário de fechamento foi atingido.')]
class CloseExpiredTasks extends Command
{
    public function handle(): int
    {
        $statusFechada = StatusTarefa::query()
            ->where('nome_status', 'fechada')
            ->first();

        if (! $statusFechada) {
            $this->error(
                'Status "fechada" não encontrado. Verifique a migration correspondente.'
            );

            return self::FAILURE;
        }

        $ids = Tarefa::query()
            ->whereNotNull('data_fechamento_automatico')
            ->where('data_fechamento_automatico', '<=', now())
            ->whereNull('data_conclusao')
            ->where(function ($query) use ($statusFechada): void {
                $query->whereNull('ID_status_tarefa')
                    ->orWhere(
                        'ID_status_tarefa',
                        '!=',
                        $statusFechada->ID_status_tarefa
                    );
            })
            ->orderBy('ID_tarefa')
            ->pluck('ID_tarefa');

        $fechadas = 0;

        foreach ($ids as $id) {
            $foiFechada = DB::transaction(function () use (
                $id,
                $statusFechada
            ): bool {
                $tarefa = Tarefa::query()
                    ->where('ID_tarefa', $id)
                    ->lockForUpdate()
                    ->first();

                if (! $tarefa) {
                    return false;
                }

                // Confere novamente as condições após adquirir o bloqueio.
                if (
                    ! $tarefa->data_fechamento_automatico ||
                    $tarefa->data_fechamento_automatico->isFuture() ||
                    $tarefa->data_conclusao !== null ||
                    (int) $tarefa->ID_status_tarefa ===
                        (int) $statusFechada->ID_status_tarefa
                ) {
                    return false;
                }

                $tarefa->ID_status_tarefa =
                    $statusFechada->ID_status_tarefa;

                $tarefa->save();

                HistoricoTarefa::query()->create([
                    'acao' => 'Fechamento automático da tarefa',
                    'detalhes' => sprintf(
                        'Tarefa fechada automaticamente. Horário configurado: %s.',
                        $tarefa->data_fechamento_automatico->toIso8601String()
                    ),
                    'ID_tarefa' => $tarefa->ID_tarefa,
                    'matricula_funcionario' => $tarefa->matricula_gestor,
                ]);

                return true;
            });

            if ($foiFechada) {
                $fechadas++;
            }
        }

        $this->info("Tarefas fechadas automaticamente: {$fechadas}");

        return self::SUCCESS;
    }
}