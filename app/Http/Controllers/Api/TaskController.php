<?php

namespace App\Http\Controllers\Api;

use App\Application\Dashboard\DashboardService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Frontend\TaskResource;
use App\Models\Identity\Funcionario;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Models\Task\HistoricoTarefa;
use App\Models\Task\StatusTarefa;
use App\Models\Task\Tarefa;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\Task\CreateTaskRequest;
use App\Models\Task\Subtarefa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(Request $request, DashboardService $dashboard): AnonymousResourceCollection
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        return TaskResource::collection($dashboard->tasks($funcionario));
    }

    public function store(CreateTaskRequest $request): JsonResponse
    {
        Gate::authorize('create', Tarefa::class);

        /** @var Funcionario $gestor */
        $gestor = $request->user();

        $statusAFazer = StatusTarefa::query()->where('nome_status', 'a-fazer')->firstOrFail();

        $tarefa = Tarefa::query()->create([
            'nome' => $request->validated('nome'),
            'descricao' => $request->validated('descricao'),
            'prioridade' => $request->validated('prioridade'),
            'data_prazo' => $request->validated('data_prazo'),
            'data_inicio' => now(),
            'pontos_base' => match ($request->validated('prioridade')) {
                'alta' => 200,
                'media' => 100,
                default => 50,
            },
            'pessoal' => DB::connection()->getDriverName() === 'pgsql' ? DB::raw('false') : false,
            'matricula_gestor' => $gestor->matricula_funcionario,
            'ID_projeto' => $request->validated('ID_projeto'),
            'ID_equipe' => $request->validated('ID_equipe'),
            'ID_status_tarefa' => $statusAFazer->ID_status_tarefa,
        ]);

        $colaboradores = array_filter((array) $request->validated('matricula_colaborador', []));
        if (! empty($colaboradores)) {
            $tarefa->colaboradores()->syncWithoutDetaching($colaboradores);
        }

        $subtarefas = (array) $request->validated('subtarefas', []);
        foreach ($subtarefas as $sub) {
            if (is_string($sub) && trim($sub) !== '') {
                Subtarefa::query()->create([
                    'nome' => trim($sub),
                    'ID_tarefa' => $tarefa->ID_tarefa,
                    'concluida' => DB::connection()->getDriverName() === 'pgsql' ? DB::raw('false') : false,
                    'matricula_colaborador' => ! empty($colaboradores) ? reset($colaboradores) : null,
                ]);
            }
        }

        HistoricoTarefa::query()->create([
            'acao' => 'Tarefa criada e atribuída',
            'detalhes' => "Tarefa criada pelo gestor {$gestor->nome}",
            'ID_tarefa' => $tarefa->ID_tarefa,
            'matricula_funcionario' => $gestor->matricula_funcionario,
        ]);

        return (new TaskResource($tarefa->fresh(['status', 'colaboradores', 'subtarefas'])))
            ->response()
            ->setStatusCode(201);
    }

    public function updateStatus(
        UpdateTaskStatusRequest $request,
        Tarefa $task
    ): TaskResource {
        Gate::authorize('update', $task);

        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        $statusSlug = $request->validated('status');
        $statusRecord = StatusTarefa::query()->where('nome_status', $statusSlug)->firstOrFail();

        $task->ID_status_tarefa = $statusRecord->ID_status_tarefa;

        if ($statusSlug === 'concluido') {
            $task->data_conclusao = now();
        } else {
            $task->data_conclusao = null;
        }

        $task->save();

        HistoricoTarefa::query()->create([
            'acao' => 'Status alterado',
            'detalhes' => "Status alterado para '{$statusRecord->nome_status}'",
            'ID_tarefa' => $task->ID_tarefa,
            'matricula_funcionario' => $funcionario->matricula_funcionario,
        ]);

        return new TaskResource($task->fresh(['status', 'colaboradores', 'subtarefas']));
    }
}
