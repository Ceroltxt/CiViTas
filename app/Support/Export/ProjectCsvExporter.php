<?php

namespace App\Support\Export;

use App\Models\Project\Projeto;
use App\Models\Task\Tarefa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectCsvExporter
{
    public function download(string $startDate, string $endDate): StreamedResponse
    {
        $workspaceId = app('workspace_id');

        $projects = Projeto::query()
            ->with([
                'admin',
                'equipes',
            ])
            ->whereHas('equipes', function (Builder $query) use ($workspaceId) {
                $query->where('workspace_id', $workspaceId);
            })
            ->whereHas('tarefas', function (Builder $query) use (
                $workspaceId,
                $startDate,
                $endDate
            ) {
                $this->applyEligibleTasks(
                    $query,
                    $workspaceId,
                    $startDate,
                    $endDate
                );
            });

        return response()->streamDownload(
            function () use (
                $projects,
                $workspaceId,
                $startDate,
                $endDate
            ) {
                $output = fopen('php://output', 'w');

                // BOM para facilitar a abertura no Excel.
                fwrite($output, "\xEF\xBB\xBF");

                fputcsv($output, [
                    'ID_projeto',
                    'nome',
                    'descricao',
                    'prioridade',
                    'status',
                    'data_inicio',
                    'data_previsao_fim',
                    'data_conclusao',
                    'administrador',
                    'equipes',
                    'total_tarefas',
                    'tarefas_concluidas',
                    'progresso_periodo',
                ], ';', '"', '');

                $projects->chunkById(200, function ($projetos) use (
                    $output,
                    $workspaceId,
                    $startDate,
                    $endDate
                ) {
                    foreach ($projetos as $projeto) {
                        if (! Gate::allows('view', $projeto)) {
                            continue;
                        }

                        $tarefas = Tarefa::query()
                            ->where('ID_projeto', $projeto->ID_projeto)
                            ->where(function (Builder $query) use (
                                $workspaceId,
                                $startDate,
                                $endDate
                            ) {
                                $this->applyEligibleTasks(
                                    $query,
                                    $workspaceId,
                                    $startDate,
                                    $endDate
                                );
                            })
                            ->with('status')
                            ->get()
                            ->filter(fn (Tarefa $tarefa) => Gate::allows('view', $tarefa));

                        $total = $tarefas->count();

                        if ($total === 0) {
                            continue;
                        }

                        $concluidas = $tarefas
                            ->filter(
                                fn (Tarefa $tarefa) =>
                                    $tarefa->status?->nome_status === 'concluido'
                            )
                            ->count();

                        $progresso = (int) round(
                            ($concluidas / $total) * 100
                        );

                        $equipes = $projeto->equipes
                            ->where('workspace_id', $workspaceId)
                            ->pluck('nome')
                            ->implode('|');

                        $administrador = $projeto->admin
                            ? trim(
                                $projeto->admin->nome . ' ' .
                                $projeto->admin->sobrenome
                            )
                            : '';

                        fputcsv($output, [
                            $projeto->ID_projeto,
                            $projeto->nome,
                            $projeto->descricao,
                            $projeto->prioridade ?? 'media',
                            $projeto->ativo ? 'ativo' : 'concluido',
                            $projeto->data_inicio?->format('Y-m-d'),
                            $projeto->data_previsao_fim?->format('Y-m-d'),
                            $projeto->data_conclusao?->format('Y-m-d'),
                            $administrador,
                            $equipes,
                            $total,
                            $concluidas,
                            $progresso,
                        ], ';', '"', '');
                    }
                }, 'ID_projeto');

                fclose($output);
            },
            'projetos-' . now()->format('Y-m-d-His') . '.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache',
            ]
        );
    }

    private function applyEligibleTasks(
        Builder $query,
        int $workspaceId,
        string $startDate,
        string $endDate
    ): void {
        $query
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
    }
}
