<?php

namespace App\Http\Controllers\Api;

use App\Application\Gamification\TaskPointsService;
use App\Application\Dashboard\DashboardService;
use App\Domain\Authorization\AppProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\CreateTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Http\Resources\Frontend\TaskResource;
use App\Models\Identity\Funcionario;
use App\Models\Task\HistoricoTarefa;
use App\Models\Task\StatusTarefa;
use App\Models\Task\Subtarefa;
use App\Models\Task\Tarefa;
use App\Models\Project\Projeto;
use App\Models\Team\Equipe;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        abort_unless(
            app()->bound('workspace_id'),
            403,
            'Workspace ativo não identificado.'
        );

        $workspaceId = (int) app('workspace_id');
        $dados = $request->validated();

        $perfil = $funcionario->workspaceProfile();
        $isPessoal = $perfil === AppProfile::Colaborador;

        if ($isPessoal) {
            Gate::authorize('createPersonal', Tarefa::class);

            $projetoId = null;
            $equipeId = null;
            $colaboradores = [$funcionario->matricula_funcionario];
        } else {
            Gate::authorize('create', Tarefa::class);

            $projetoId = $dados['ID_projeto'] ?? null;
            $equipeId = $dados['ID_equipe'] ?? null;

            if (! $projetoId && ! $equipeId) {
                throw ValidationException::withMessages([
                    'ID_projeto' => 'Informe um projeto ou uma equipe para a tarefa.',
                ]);
            }

            $projeto = null;
            $equipe = null;

            if ($projetoId) {
                $projeto = Projeto::query()
                    ->whereHas('equipes', function ($query) use ($workspaceId) {
                        $query->where('equipe.workspace_id', $workspaceId);
                    })
                    ->find($projetoId);

                if (! $projeto) {
                    throw ValidationException::withMessages([
                        'ID_projeto' => 'O projeto não pertence ao workspace ativo.',
                    ]);
                }
            }

            if ($equipeId) {
                $equipe = Equipe::query()
                    ->where('workspace_id', $workspaceId)
                    ->find($equipeId);

                if (! $equipe) {
                    throw ValidationException::withMessages([
                        'ID_equipe' => 'A equipe não pertence ao workspace ativo.',
                    ]);
                }
            }

            if ($projeto && $equipe) {
                $equipeAssociada = $projeto->equipes()
                    ->where('equipe.ID_equipe', $equipe->ID_equipe)
                    ->exists();

                if (! $equipeAssociada) {
                    throw ValidationException::withMessages([
                        'ID_equipe' => 'A equipe informada não está associada ao projeto.',
                    ]);
                }
            }

            $colaboradores = array_values(array_unique(
                array_filter(
                    (array) ($dados['matricula_colaborador'] ?? [])
                )
            ));

            if (empty($colaboradores)) {
                $colaboradores = [$funcionario->matricula_funcionario];
            }

            $colaboradoresNoWorkspace = DB::table('workspace_funcionario')
                ->where('workspace_id', $workspaceId)
                ->whereIn('matricula_funcionario', $colaboradores)
                ->pluck('matricula_funcionario')
                ->map(fn ($matricula) => (string) $matricula)
                ->all();

            foreach ($colaboradores as $matricula) {
                if (! in_array((string) $matricula, $colaboradoresNoWorkspace, true)) {
                    throw ValidationException::withMessages([
                        'matricula_colaborador' =>
                            "O colaborador {$matricula} não pertence ao workspace ativo.",
                    ]);
                }
            }
        }

        $statusAFazer = StatusTarefa::query()
            ->where('nome_status', 'a-fazer')
            ->firstOrFail();

        $tarefa = Tarefa::query()->create([
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'prioridade' => $dados['prioridade'],
            'data_prazo' => $dados['data_prazo'],
            'data_inicio' => now(),
            'pontos_base' => match ($dados['prioridade']) {
                'alta' => 200,
                'media' => 100,
                default => 50,
            },
            'pessoal' => DB::connection()->getDriverName() === 'pgsql'
                ? ($isPessoal ? DB::raw('true') : DB::raw('false'))
                : $isPessoal,
            'matricula_gestor' => $funcionario->matricula_funcionario,
            'ID_projeto' => $projetoId,
            'ID_equipe' => $equipeId,
            'ID_status_tarefa' => $statusAFazer->ID_status_tarefa,
            'data_fechamento_automatico' =>
                $dados['data_fechamento_automatico'] ?? null,
        ]);

        $tarefa->colaboradores()->sync($colaboradores);

        foreach (($dados['subtarefas'] ?? []) as $sub) {
            if (is_string($sub) && trim($sub) !== '') {
                Subtarefa::query()->create([
                    'nome' => trim($sub),
                    'ID_tarefa' => $tarefa->ID_tarefa,
                    'concluida' => false,
                    'matricula_colaborador' => $colaboradores[0],
                ]);
            }
        }

        HistoricoTarefa::query()->create([
            'acao' => $isPessoal
                ? 'Tarefa pessoal criada'
                : 'Tarefa criada e atribuída',
            'detalhes' => $isPessoal
                ? "Tarefa pessoal criada por {$funcionario->nome}"
                : "Tarefa criada pelo gestor {$funcionario->nome}",
            'ID_tarefa' => $tarefa->ID_tarefa,
            'matricula_funcionario' => $funcionario->matricula_funcionario,
        ]);

        return (new TaskResource(
            $tarefa->fresh(['status', 'colaboradores', 'subtarefas'])
        ))
            ->response()
            ->setStatusCode(201);
    }

    public function updateStatus(
    UpdateTaskStatusRequest $request,
    Tarefa $task,
    TaskPointsService $taskPoints
    ): TaskResource {
        $taskId = $task->ID_tarefa;

        return DB::transaction(function () use (
            $request,
            $taskId,
            $taskPoints
        ): TaskResource {
            // Relê e bloqueia a tarefa antes de validar a transição.
            $task = Tarefa::query()
                ->where('ID_tarefa', $taskId)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('updateStatus', $task);

            /** @var Funcionario $funcionario */
            $funcionario = $request->user();

            $perfil = $funcionario->workspaceProfile();
            $isGestor = $perfil === AppProfile::Gestor;
            $isColaborador = $perfil === AppProfile::Colaborador;

            $statusSlug = $request->validated('status');

            $task->loadMissing('status');
            $statusAtual = $task->status?->nome_status;
            $reabriuTarefa = $statusAtual === 'fechada';

            // Fechar uma tarefa é uma ação exclusiva do gestor.
            if ($statusSlug === 'fechada') {
                abort_unless(
                    $isGestor,
                    403,
                    'Somente o gestor pode fechar uma tarefa.'
                );

                abort_if(
                    $task->data_conclusao !== null,
                    422,
                    'Uma tarefa já concluída não pode ser fechada como pendente.'
                );
            }

            // Reabrir uma tarefa fechada é exclusivo do gestor.
            if ($reabriuTarefa) {
                abort_unless(
                    $isGestor,
                    403,
                    'Somente o gestor pode reabrir uma tarefa fechada.'
                );

                abort_if(
                    $task->data_conclusao !== null,
                    422,
                    'Esta tarefa já foi concluída e não pode ser reaberta por este fluxo.'
                );

                abort_unless(
                    in_array($statusSlug, ['a-fazer', 'em-andamento'], true),
                    422,
                    'Ao reabrir uma tarefa, selecione a fazer ou em andamento.'
                );

                $task->foi_reaberta = true;
                $task->quantidade_reaberturas =
                    (int) $task->quantidade_reaberturas + 1;
            }

            // Tarefas de trabalho seguem o fluxo de revisão.
            if (! $task->pessoal) {
                if ($isColaborador) {
                    abort_unless(
                        in_array(
                            $statusSlug,
                            [
                                'a-fazer',
                                'em-andamento',
                                'em-revisao',
                                'concluido',
                            ],
                            true
                        ),
                        403,
                        'Você não tem permissão para definir esse status.'
                    );

                    // O colaborador solicita a conclusão; o gestor aprova.
                    if ($statusSlug === 'concluido') {
                        $statusSlug = 'em-revisao';
                    }
                }

                // Apenas o gestor pode aprovar a conclusão de tarefas de trabalho.
                if ($statusSlug === 'concluido') {
                    abort_unless(
                        $isGestor,
                        403,
                        'Somente o gestor pode aprovar a conclusão desta tarefa.'
                    );
                }
            }

            $statusRecord = StatusTarefa::query()
                ->where('nome_status', $statusSlug)
                ->firstOrFail();

            $task->ID_status_tarefa = $statusRecord->ID_status_tarefa;

            if ($statusSlug === 'concluido') {
                $task->data_conclusao = now();
            } elseif ($statusSlug !== 'fechada') {
                $task->data_conclusao = null;
            }

            $task->save();

            // A penalidade faz parte da mesma transação do status.
            if ($reabriuTarefa) {
                $taskPoints->penalizeReopening(
                    $task,
                    (int) $task->quantidade_reaberturas
                );
            }

            // Registra a pontuação quando a conclusão é aprovada.
            if ($statusSlug === 'concluido') {
                $taskPoints->awardForCompletion($task);
            }

            // O histórico também precisa ser persistido na mesma transação.
            HistoricoTarefa::query()->create([
                'acao' => 'Status alterado',
                'detalhes' => "Status alterado para '{$statusRecord->nome_status}'",
                'ID_tarefa' => $task->ID_tarefa,
                'matricula_funcionario' =>
                    $funcionario->matricula_funcionario,
            ]);

            return new TaskResource(
                $task->fresh(['status', 'colaboradores', 'subtarefas'])
            );
        });
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

        // Se concluiu uma subtarefa e a tarefa principal estava 'a-fazer' ou sem status, passa para 'em-andamento'
        $task->loadMissing('status');
        if ($subtask->concluida && ($task->status?->nome_status === 'a-fazer' || $task->status === null)) {
            $emAndamentoStatus = StatusTarefa::query()->where('nome_status', 'em-andamento')->first();
            if ($emAndamentoStatus) {
                $task->ID_status_tarefa = $emAndamentoStatus->ID_status_tarefa;
                $task->save();
            }
        }

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

    public function update(UpdateTaskRequest $request, Tarefa $task): TaskResource
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

        if (array_key_exists('data_fechamento_automatico', $data)) {
            $task->data_fechamento_automatico = $data['data_fechamento_automatico'];
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
