<?php

namespace App\Http\Controllers\Api;

use App\Application\Dashboard\DashboardService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\CreateTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Resources\Frontend\TeamSummaryResource;
use App\Http\Resources\Frontend\UserSummaryResource;
use App\Models\Identity\Funcionario;
use App\Models\Team\Equipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    public function index(Request $request, DashboardService $dashboard): AnonymousResourceCollection
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        return TeamSummaryResource::collection($dashboard->teams($funcionario));
    }

    public function gestores(): AnonymousResourceCollection
    {
        $gestores = Funcionario::query()
            ->whereHas('cargo', function ($q) {
                $q->whereIn('nome_cargo', ['Gestor', 'Admin', 'Administrador']);
            })
            ->orWhereIn('email', ['gestor@civitas.test', 'admin@civitas.test'])
            ->get();

        return UserSummaryResource::collection($gestores);
    }

    public function store(CreateTeamRequest $request): JsonResponse
    {
        Gate::authorize('create', Equipe::class);

        $data = $request->validated();

        $equipe = DB::transaction(function () use ($data) {
            $team = Equipe::query()->create([
                'nome' => $data['nome'],
                'matricula_gestor' => $data['matricula_gestor'],
                'ID_projeto' => $data['ID_projeto'] ?? null,
            ]);

            if (! empty($data['membros'])) {
                $team->membros()->sync($data['membros']);
            }

            return $team;
        });

        return (new TeamSummaryResource($equipe->fresh(['membros', 'projetos'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTeamRequest $request, Equipe $team): TeamSummaryResource
    {
        Gate::authorize('update', $team);

        $data = $request->validated();

        DB::transaction(function () use ($team, $data) {
            if (array_key_exists('nome', $data)) {
                $team->nome = $data['nome'];
            }
            if (array_key_exists('matricula_gestor', $data)) {
                $team->matricula_gestor = $data['matricula_gestor'];
            }
            if (array_key_exists('ID_projeto', $data)) {
                $team->ID_projeto = $data['ID_projeto'];
            }
            $team->save();

            if (array_key_exists('membros', $data)) {
                $team->membros()->sync((array) $data['membros']);
            }
        });

        return new TeamSummaryResource($team->fresh(['membros', 'projetos']));
    }

    public function destroy(Equipe $team): JsonResponse
    {
        Gate::authorize('delete', $team);

        DB::transaction(function () use ($team) {
            $team->membros()->detach();
            $team->delete();
        });

        return response()->json(['message' => 'Equipe excluída com sucesso.']);
    }
}
