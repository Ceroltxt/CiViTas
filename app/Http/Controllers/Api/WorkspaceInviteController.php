<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace\WorkspaceInvite;
use App\Models\Workspace\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class WorkspaceInviteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (app('workspace_role') !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $invites = WorkspaceInvite::where('workspace_id', app('workspace_id'))
            ->whereNull('accepted_at')
            ->get();

        return response()->json($invites);
    }

    public function store(Request $request): JsonResponse
    {
        if (app('workspace_role') !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:admin,gestor,colaborador',
        ]);

        $invite = WorkspaceInvite::create([
            'workspace_id' => app('workspace_id'),
            'email' => $data['email'],
            'role' => $data['role'],
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        // Simula disparo de e-mail (em ambiente real dispararia um Mailable)
        \Log::info("Convite gerado para {$invite->email} com token {$invite->token}");

        return response()->json(['message' => 'Convite enviado com sucesso.', 'invite' => $invite]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invite = WorkspaceInvite::where('token', $token)->firstOrFail();

        if ($invite->expires_at->isPast() || $invite->accepted_at !== null) {
            return response()->json(['message' => 'Convite expirado ou já aceito.'], 400);
        }

        $user = $request->user();

        // Vincula o usuário ao workspace
        DB::table('workspace_funcionario')->updateOrInsert(
            [
                'workspace_id' => $invite->workspace_id,
                'matricula_funcionario' => $user->matricula_funcionario,
            ],
            [
                'role' => $invite->role,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $invite->update(['accepted_at' => now()]);

        return response()->json(['message' => 'Convite aceito com sucesso.']);
    }
}
