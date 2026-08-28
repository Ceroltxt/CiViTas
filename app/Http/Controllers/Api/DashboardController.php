<?php

namespace App\Http\Controllers\Api;

use App\Application\Dashboard\DashboardService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Frontend\ProjectProgressResource;
use App\Models\Identity\Funcionario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DashboardController extends Controller
{
    public function metrics(Request $request, DashboardService $dashboard): JsonResponse
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        return response()->json($dashboard->metrics($funcionario));
    }

    public function agenda(Request $request, DashboardService $dashboard): JsonResponse
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        return response()->json($dashboard->agenda($funcionario));
    }

    public function ranking(DashboardService $dashboard): JsonResponse
    {
        return response()->json($dashboard->ranking());
    }

    public function projects(Request $request, DashboardService $dashboard): AnonymousResourceCollection
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        return ProjectProgressResource::collection($dashboard->projects($funcionario));
    }
}
