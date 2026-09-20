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
        // Pega todos os funcionários do site que tenham cargo de Gestor ou Admin, independentemente de projeto ou workspace.
        $gestores = Funcionario::with('cargo')->get()->filter(function ($f) {
            $cargo = mb_strtolower(trim($f->cargo?->nome_cargo ?? ''));
            return in_array($cargo, ['gestor', 'admin', 'administrador']) ||
                   in_array($f->email, ['gestor@civitas.test', 'admin@civitas.test']);
        })->values();

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
                'pontos_totais' => 0,
            ]);

            if (! empty($data['ID_projeto'])) {
                $team->projetos()->attach($data['ID_projeto']);
            }

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

    public function members(Request $request, Equipe $team): JsonResponse
    {
        $team->load(['gestor', 'membros.cargo']);

        $gestor = $team->gestor
            ? (new UserSummaryResource($team->gestor))->additional(['role_override' => 'Gestor'])->toArray($request)
            : null;

        if ($gestor !== null) {
            $gestor['role'] = 'Gestor';
        }

        $members = $team->membros->map(function (Funcionario $membro) use ($request) {
            $data = (new UserSummaryResource($membro))->toArray($request);
            $data['role'] = 'Colaborador';

            return $data;
        })->values();

        return response()->json([
            'gestor' => $gestor,
            'members' => $members,
        ]);
    }

    public function addMember(Request $request, Equipe $team): JsonResponse
    {
        Gate::authorize('update', $team);

        $request->validate([
            'matricula_funcionario' => [
                'required',
                'integer',
                'exists:funcionario,matricula_funcionario',
            ],
        ]);

        $team->membros()->syncWithoutDetaching([$request->matricula_funcionario]);

        return $this->members($request, $team);
    }

    public function removeMember(Request $request, Equipe $team, Funcionario $funcionario): JsonResponse
    {
        Gate::authorize('update', $team);

        $team->membros()->detach($funcionario->matricula_funcionario);

        return response()->json(['message' => 'Membro removido.']);
    }

    public function colaboradores(): AnonymousResourceCollection
    {
        // Pega todos os funcionários do site que não sejam Admin, independentemente de projeto ou workspace.
        $colaboradores = Funcionario::with('cargo')->get()->filter(function ($f) {
            $cargo = mb_strtolower(trim($f->cargo?->nome_cargo ?? ''));
            return !in_array($cargo, ['admin', 'administrador']);
        })->values();

        return UserSummaryResource::collection($colaboradores);
    }
}
