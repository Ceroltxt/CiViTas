<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class WorkspaceContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Pega do header ou usa fallback para não quebrar o frontend antigo temporariamente
        $workspaceId = $request->header('X-Workspace-Id');
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Rotas globais que não exigem workspace selecionado
        if ($request->is('api/workspaces*') || $request->is('api/invites/*/accept') || $request->is('api/auth/*')) {
            return $next($request);
        }
        if (!$workspaceId) {
            $primeiroWorkspace = DB::table('workspace_funcionario')
                ->where('matricula_funcionario', $user->matricula_funcionario)
                ->first();

            if ($primeiroWorkspace) {
                $workspaceId = $primeiroWorkspace->workspace_id;
            }
        }

        if (!$workspaceId) {
            return response()->json(['message' => 'Workspace não informado ou usuário sem workspace.'], 403);
        }

        // Valida se o usuário pertence a este workspace
        $roleInfo = DB::table('workspace_funcionario')
            ->where('workspace_id', $workspaceId)
            ->where('matricula_funcionario', $user->matricula_funcionario)
            ->first();

        if (!$roleInfo) {
            return response()->json(['message' => 'Acesso negado a este Workspace.'], 403);
        }

        // Injeta no container global e na request para os Controllers/Scopes usarem
        app()->instance('workspace_id', (int) $workspaceId);
        app()->instance('workspace_role', $roleInfo->role);
        
        $request->attributes->set('workspace_id', (int) $workspaceId);
        $request->attributes->set('workspace_role', $roleInfo->role);

        return $next($request);
    }
}
