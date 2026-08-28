<?php

namespace App\Http\Controllers\Api;

use App\Application\Dashboard\DashboardService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Frontend\TaskResource;
use App\Models\Identity\Funcionario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BoardController extends Controller
{
    public function tasks(Request $request, DashboardService $dashboard): AnonymousResourceCollection
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        return TaskResource::collection($dashboard->boardTasks($funcionario));
    }

    public function timeline(Request $request, DashboardService $dashboard): JsonResponse
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        return response()->json($dashboard->timeline($funcionario));
    }
}
