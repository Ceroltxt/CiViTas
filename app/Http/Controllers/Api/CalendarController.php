<?php

namespace App\Http\Controllers\Api;

use App\Application\Dashboard\DashboardService;
use App\Http\Controllers\Controller;
use App\Models\Identity\Funcionario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function events(Request $request, DashboardService $dashboard): JsonResponse
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();

        return response()->json($dashboard->calendarEvents($funcionario));
    }
}
