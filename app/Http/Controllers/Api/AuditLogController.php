<?php

namespace App\Http\Controllers\Api;

use App\Application\Dashboard\DashboardService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Frontend\AuditLogResource;
use App\Models\Identity\Funcionario;
use App\Models\Task\HistoricoTarefa;
use App\Support\Frontend\TaskQueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(
        Request $request,
        DashboardService $dashboard
    ): AnonymousResourceCollection {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        return AuditLogResource::collection(
            $dashboard->auditLogs(
                $funcionario,
                (int) ($filters['limit'] ?? 10),
                $filters['start_date'] ?? null,
                $filters['end_date'] ?? null,
            ),
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
        ]);

        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        $tarefaIds = TaskQueryBuilder::forFuncionario($funcionario)
            ->pluck('ID_tarefa');

        $query = HistoricoTarefa::query()
            ->with('funcionario')
            ->whereIn('ID_tarefa', $tarefaIds);

        if (! empty($filters['start_date'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $filters['start_date']
            );
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['end_date']
            );
        }

        $filename = 'historico-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');

            // BOM para melhorar a abertura de caracteres acentuados no Excel.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'ID',
                'Data',
                'Usuário',
                'Ação',
                'Detalhes',
            ], ';');

            $query->orderBy('created_at')
                ->chunk(500, function ($logs) use ($output): void {
                    foreach ($logs as $log) {
                        /** @var HistoricoTarefa $log */
                        $user = $log->funcionario
                            ? trim(
                                $log->funcionario->nome . ' ' .
                                $log->funcionario->sobrenome
                            )
                            : 'Sistema';

                        fputcsv($output, [
                            $log->ID_historico,
                            $log->created_at?->format('Y-m-d H:i:s') ?? '',
                            $user,
                            $log->acao,
                            $log->detalhes ?? '',
                        ], ';');
                    }
                });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
