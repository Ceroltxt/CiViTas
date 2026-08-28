<?php

namespace App\Http\Controllers\Api;

use App\Application\Dashboard\DashboardService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Frontend\CurrentProjectResource;
use App\Http\Resources\Frontend\UserSummaryResource;
use App\Models\Identity\Funcionario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request): UserSummaryResource
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();
        $funcionario->load('cargo');

        return new UserSummaryResource($funcionario);
    }

    public function currentProject(Request $request, DashboardService $dashboard): JsonResponse|CurrentProjectResource
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();
        $projeto = $dashboard->currentProject($funcionario);

        if (! $projeto) {
            return response()->json([
                'name' => 'Nenhum projeto',
                'deadline' => '—',
                'progress' => 0,
            ]);
        }

        $projeto->load(['tarefas.status']);

        return new CurrentProjectResource($projeto);
    }
}
