<?php

namespace App\Http\Controllers\Api;

use App\Application\Dashboard\DashboardService;
use App\Http\Controllers\Controller;
use App\Models\Identity\Funcionario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, DashboardService $dashboard): JsonResponse
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        return response()->json($dashboard->notifications($funcionario));
    }
}
