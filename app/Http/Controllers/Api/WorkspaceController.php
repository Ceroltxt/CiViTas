<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Identity\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
        
        $workspaces = $funcionario->workspaces()->get()->map(function ($workspace) {
            return [
                'id' => $workspace->id,
                'nome' => $workspace->nome,
                'role' => $workspace->pivot->role,
            ];
        });

        return response()->json($workspaces);
    }
}
