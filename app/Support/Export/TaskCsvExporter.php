<?php

namespace App\Support\Export;

use App\Models\Task\Tarefa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskCsvExporter
{
    public function download(string $startDate, string $endDate): StreamedResponse
    {
        $workspaceId = app('workspace_id');

        $query = Tarefa::query()
            ->with([
                'status',
                'projeto',
                'equipe',
                'gestor',
                'colaboradores',
                'subtarefas',
                'projeto.equipes.membros',
                'equipe.membros',
            ])
            ->where('pessoal', false)
            ->where(function (Builder $query) use ($workspaceId) {
                $query->whereHas('equipe', function (Builder $equipe) use ($workspaceId) {
                    $equipe->where('workspace_id', $workspaceId);
                })->orWhere(function (Builder $query) use ($workspaceId) {
                    $query->whereNull('ID_equipe')
                        ->whereHas('projeto.equipes', function (Builder $equipe) use ($workspaceId) {
                            $equipe->where('workspace_id', $workspaceId);
                        });
                });
            })
            ->where(function (Builder $query) use ($startDate, $endDate) {
                $query->where(function (Builder $ativa) use ($startDate, $endDate) {
                    $ativa->whereNotNull('data_inicio')
                        ->where('data_inicio', '<=', $endDate)
                        ->where(function (Builder $conclusao) use ($startDate) {
                            $conclusao->whereNull('data_conclusao')
                                ->orWhere('data_conclusao', '>=', $startDate);
                        });
                })->orWhereBetween('data_prazo', [$startDate, $endDate]);
            });

        return response()->streamDownload(
            function () use ($query) {
                $output = fopen('php://output', 'w');

                // BOM para facilitar a abertura do CSV no Excel.
                fwrite($output, "\xEF\xBB\xBF");

                fputcsv($output, [
                    'ID_tarefa',
                    'nome',
                    'descricao',
                    'prioridade',
                    'data_inicio',
                    'data_prazo',
                    'data_conclusao',
                    'status',
                    'projeto',
                    'equipe',
                    'gestor',
                    'colaboradores',
                    'subtarefas',
                ], ';', '"', '');

                $query->chunkById(500, function ($tarefas) use ($output) {
                    foreach ($tarefas as $tarefa) {
                        // A consulta já limita o workspace; aqui verificamos
                        // também a permissão individual do usuário.
                        if (! Gate::allows('view', $tarefa)) {
                            continue;
                        }

                        $gestor = $tarefa->gestor
                            ? trim($tarefa->gestor->nome . ' ' . $tarefa->gestor->sobrenome)
                            : '';

                        $colaboradores = $tarefa->colaboradores
                            ->map(fn ($colaborador) => trim(
                                $colaborador->nome . ' ' . $colaborador->sobrenome
                            ))
                            ->filter()
                            ->implode('|');

                        $subtarefas = $tarefa->subtarefas
                            ->pluck('nome')
                            ->filter()
                            ->implode('|');

                        fputcsv($output, [
                            $tarefa->ID_tarefa,
                            $tarefa->nome,
                            $tarefa->descricao,
                            $tarefa->prioridade,
                            $tarefa->data_inicio?->format('Y-m-d'),
                            $tarefa->data_prazo?->format('Y-m-d'),
                            $tarefa->data_conclusao?->format('Y-m-d'),
                            $tarefa->status?->nome_status,
                            $tarefa->projeto?->nome,
                            $tarefa->equipe?->nome,
                            $gestor,
                            $colaboradores,
                            $subtarefas,
                        ], ';', '"', '');
                    }
                }, 'ID_tarefa');

                fclose($output);
            },
            'tarefas-' . now()->format('Y-m-d-His') . '.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache',
            ]
        );
    }
}