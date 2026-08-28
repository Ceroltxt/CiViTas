<?php

namespace App\Http\Controllers\Api;

use App\Application\Dashboard\DashboardService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Frontend\AuditLogResource;
use App\Models\Identity\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    public function index(Request $request, DashboardService $dashboard): AnonymousResourceCollection
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();
        $limit = (int) $request->query('limit', 10);

        return AuditLogResource::collection(
            $dashboard->auditLogs($funcionario, max(1, min($limit, 100))),
        );
    }
}
