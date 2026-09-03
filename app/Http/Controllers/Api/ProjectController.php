<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\CreateProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\Frontend\ProjectProgressResource;
use App\Models\Identity\Funcionario;
use App\Models\Project\Projeto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = Projeto::query()
            ->with(['equipes.membros', 'equipes.gestor', 'tarefas'])
            ->get();

        return ProjectProgressResource::collection($projects);
    }

    public function store(CreateProjectRequest $request): JsonResponse
    {
        Gate::authorize('create', Projeto::class);

        /** @var Funcionario $user */
        $user = $request->user();
        $data = $request->validated();

        $projeto = Projeto::query()->create([
            'nome' => $data['nome'],
            'descricao' => $data['descricao'] ?? null,
            'prioridade' => $data['prioridade'] ?? 'media',
            'data_inicio' => $data['data_inicio'] ?? now(),
            'data_previsao_fim' => $data['data_previsao_fim'] ?? null,
            'ativo' => true,
            'ID_matricula_admin' => $user->matricula_funcionario,
        ]);

        return (new ProjectProgressResource($projeto->fresh(['equipes.membros', 'tarefas'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectRequest $request, Projeto $project): ProjectProgressResource
    {
        Gate::authorize('update', $project);

        $data = $request->validated();

        if (array_key_exists('nome', $data)) {
            $project->nome = $data['nome'];
        }
        if (array_key_exists('descricao', $data)) {
            $project->descricao = $data['descricao'];
        }
        if (array_key_exists('prioridade', $data)) {
            $project->prioridade = $data['prioridade'];
        }
        if (array_key_exists('data_inicio', $data)) {
            $project->data_inicio = $data['data_inicio'];
        }
        if (array_key_exists('data_previsao_fim', $data)) {
            $project->data_previsao_fim = $data['data_previsao_fim'];
        }
        if (array_key_exists('ativo', $data)) {
            $project->ativo = $data['ativo'];
        }

        $project->save();

        return new ProjectProgressResource($project->fresh(['equipes.membros', 'tarefas']));
    }

    public function destroy(Projeto $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        DB::transaction(function () use ($project) {
            $project->equipes()->detach();
            $project->delete();
        });

        return response()->json(['message' => 'Projeto excluído com sucesso.']);
    }
}
