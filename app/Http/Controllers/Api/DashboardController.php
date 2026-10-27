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
use Illuminate\Validation\Rule;

    class DashboardController extends Controller{
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

        public function ranking(Request $request, DashboardService $dashboard): JsonResponse{
            $mesAtual = now()->startOfMonth();

            $mesesPermitidos = collect([0, 1, 2])
                ->map(
                    fn (int $meses) => $mesAtual
                        ->copy()
                        ->subMonths($meses)
                        ->format('Y-m')
                )
                ->all();

            $validado = $request->validate([
                'month' => [
                    'sometimes',
                    'required',
                    'date_format:Y-m',
                    Rule::in($mesesPermitidos),
                ],
            ]);

            $mes = $validado['month'] ?? $mesAtual->format('Y-m');

            $cacheKey = "dashboard_ranking_{$mes}";

            $data = Cache::remember(
                $cacheKey,
                60,
                fn () => $dashboard->ranking($mes)
            );

            return response()->json($data);
        }

        public function projects(
            Request $request,
            DashboardService $dashboard
        ): AnonymousResourceCollection {
            /** @var Funcionario $funcionario */
            $funcionario = $request->user();

            return ProjectProgressResource::collection(
                $dashboard->projects($funcionario)
            );
        }
    }