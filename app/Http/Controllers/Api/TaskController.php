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
        $colaboradores = array_filter((array) $request->validated('matricula_colaborador', []));
        $isPessoal = $request->boolean('pessoal', false) || empty($colaboradores);

        if (empty($colaboradores)) {
            $colaboradores = [$gestor->matricula_funcionario];
        }

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
            'pessoal' => $isPessoal,
            'matricula_gestor' => $gestor->matricula_funcionario,
            'ID_projeto' => $request->validated('ID_projeto'),
            'ID_equipe' => $request->validated('ID_equipe'),
            'ID_status_tarefa' => $statusAFazer->ID_status_tarefa,
        ]);

        $tarefa->colaboradores()->syncWithoutDetaching($colaboradores);

        $subtarefas = (array) $request->validated('subtarefas', []);
        foreach ($subtarefas as $sub) {
            if (is_string($sub) && trim($sub) !== '') {
                Subtarefa::query()->create([
                    'nome' => trim($sub),
                    'ID_tarefa' => $tarefa->ID_tarefa,
                    'concluida' => false,
                    'matricula_colaborador' => reset($colaboradores) ?: $gestor->matricula_funcionario,
                ]);
            }
        }

        HistoricoTarefa::query()->create([
            'acao' => $isPessoal ? 'Tarefa pessoal criada' : 'Tarefa criada e atribuída',
            'detalhes' => $isPessoal ? "Tarefa pessoal criada por {$gestor->nome}" : "Tarefa criada pelo gestor {$gestor->nome}",
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
        Gate::authorize('updateStatus', $task);

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

    public function show(Tarefa $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task->load(['status', 'colaboradores', 'subtarefas', 'historico', 'projeto', 'equipe']));
    }

    public function toggleSubtask(Request $request, Tarefa $task, Subtarefa $subtask): TaskResource
    {
        Gate::authorize('updateStatus', $task);

        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        $subtask->concluida = ! $subtask->concluida;
        $subtask->save();

        $statusMsg = $subtask->concluida ? 'concluída' : 'reaberta';

        HistoricoTarefa::query()->create([
            'acao' => "Subtarefa {$statusMsg}",
            'detalhes' => "Subtarefa '{$subtask->nome}' foi {$statusMsg} por {$funcionario->nome}",
            'ID_tarefa' => $task->ID_tarefa,
            'matricula_funcionario' => $funcionario->matricula_funcionario,
        ]);

        return new TaskResource($task->fresh(['status', 'colaboradores', 'subtarefas', 'historico']));
    }

    public function addComment(Request $request, Tarefa $task): TaskResource
    {
        Gate::authorize('updateStatus', $task);

        $request->validate([
            'comentario' => ['required', 'string', 'max:1000'],
        ]);

        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        HistoricoTarefa::query()->create([
            'acao' => 'Comentário adicionado',
            'detalhes' => $request->input('comentario'),
            'ID_tarefa' => $task->ID_tarefa,
            'matricula_funcionario' => $funcionario->matricula_funcionario,
        ]);

        return new TaskResource($task->fresh(['status', 'colaboradores', 'subtarefas', 'historico']));
    }

    public function update(\App\Http\Requests\Task\UpdateTaskRequest $request, Tarefa $task): TaskResource
    {
        Gate::authorize('update', $task);

        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        $data = $request->validated();

        if (array_key_exists('nome', $data)) {
            $task->nome = $data['nome'];
        }
        if (array_key_exists('descricao', $data)) {
            $task->descricao = $data['descricao'];
        }
        if (array_key_exists('prioridade', $data)) {
            $task->prioridade = $data['prioridade'];
            $task->pontos_base = match ($data['prioridade']) {
                'alta' => 200,
                'media' => 100,
                default => 50,
            };
        }
        if (array_key_exists('data_prazo', $data)) {
            $task->data_prazo = $data['data_prazo'];
        }
        if (array_key_exists('ID_projeto', $data)) {
            $task->ID_projeto = $data['ID_projeto'];
        }
        if (array_key_exists('ID_equipe', $data)) {
            $task->ID_equipe = $data['ID_equipe'];
        }

        $task->save();

        if (array_key_exists('matricula_colaborador', $data)) {
            $colaboradores = array_filter((array) $data['matricula_colaborador']);
            if (! empty($colaboradores)) {
                $task->colaboradores()->sync($colaboradores);
            }
        }

        HistoricoTarefa::query()->create([
            'acao' => 'Tarefa atualizada',
            'detalhes' => "Dados da tarefa atualizados por {$funcionario->nome}",
            'ID_tarefa' => $task->ID_tarefa,
            'matricula_funcionario' => $funcionario->matricula_funcionario,
        ]);

        return new TaskResource($task->fresh(['status', 'colaboradores', 'subtarefas', 'historico']));
    }

    public function destroy(Tarefa $task): JsonResponse
    {
        Gate::authorize('delete', $task);

        DB::transaction(function () use ($task): void {
            $task->subtarefas()->delete();
            $task->historico()->delete();
            $task->colaboradores()->detach();
            $task->delete();
        });

        return response()->json(['message' => 'Tarefa excluída com sucesso.'], 200);
    }
}
