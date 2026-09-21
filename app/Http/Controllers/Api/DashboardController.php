<?php

namespace App\Http\Controllers\Api;

use App\Application\Dashboard\DashboardService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Frontend\ProjectProgressResource;
use App\Models\Identity\Funcionario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function metrics(Request $request, DashboardService $dashboard): JsonResponse
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();
        $wsId = app()->bound('workspace_id') ? app('workspace_id') : 'all';
        $cacheKey = "metrics_user_{$funcionario->matricula_funcionario}_ws_{$wsId}";

        $data = Cache::remember($cacheKey, 30, fn () => $dashboard->metrics($funcionario));

        return response()->json($data);
    }

    public function agenda(Request $request, DashboardService $dashboard): JsonResponse
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();
        $wsId = app()->bound('workspace_id') ? app('workspace_id') : 'all';
        $cacheKey = "agenda_user_{$funcionario->matricula_funcionario}_ws_{$wsId}";

        $data = Cache::remember($cacheKey, 30, fn () => $dashboard->agenda($funcionario));

        return response()->json($data);
    }

    public function ranking(DashboardService $dashboard): JsonResponse
    {
        $data = Cache::remember('dashboard_ranking_global', 60, fn () => $dashboard->ranking());

        return response()->json($data);
    }

    public function projects(Request $request, DashboardService $dashboard): AnonymousResourceCollection
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        return ProjectProgressResource::collection($dashboard->projects($funcionario));
    }
}
