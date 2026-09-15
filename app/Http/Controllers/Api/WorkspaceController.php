<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Identity\Funcionario;
use App\Models\Workspace\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WorkspaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $funcionario = $request->user();
        
        // Se o usuário for um User (Laravel Sanctum), precisamos encontrar o Funcionario correspondente
        if ($funcionario instanceof \App\Models\User) {
            $funcionario = Funcionario::where('email', $funcionario->email)->first();
            if (!$funcionario) {
                return response()->json([]);
            }
        }
        
        $workspaces = DB::table('workspace_funcionario')
            ->join('workspaces', 'workspace_funcionario.workspace_id', '=', 'workspaces.id')
            ->where('workspace_funcionario.matricula_funcionario', $funcionario->matricula_funcionario)
            ->select('workspaces.id', 'workspaces.nome', 'workspace_funcionario.role')
            ->get()
            ->map(function ($workspace) {
                return [
                    'id' => $workspace->id,
                    'nome' => $workspace->nome,
                    'role' => $workspace->role,
                ];
            });

        return response()->json($workspaces);
    }
}
