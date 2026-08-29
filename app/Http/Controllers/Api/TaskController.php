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
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function index(Request $request, DashboardService $dashboard): AnonymousResourceCollection
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        return TaskResource::collection($dashboard->tasks($funcionario));
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
