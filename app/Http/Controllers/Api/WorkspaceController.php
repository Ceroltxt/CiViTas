<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WorkspaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $workspaces = $user->workspaces()->get()->map(function ($workspace) {
            return [
                'id' => $workspace->id,
                'nome' => $workspace->nome,
                'role' => $workspace->pivot->role,
            ];
        });

        return response()->json($workspaces);
    }
}
