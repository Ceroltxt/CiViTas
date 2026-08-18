<?php

namespace App\Http\Controllers\Api\Identity;

use App\Application\Identity\Auth\LoginAction;
use App\Application\Identity\Auth\LogoutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\LoginRequest;
use App\Http\Resources\Identity\FuncionarioResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginAction $loginAction): JsonResponse
    {
        $result = $loginAction->execute(
            email: $request->validated('email'),
            password: $request->validated('password'),
            deviceName: $request->validated('device_name', 'api'),
        );

        return response()->json([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'funcionario' => new FuncionarioResource($result['funcionario']),
        ]);
    }

    public function me(Request $request): FuncionarioResource
    {
        return new FuncionarioResource(
            $request->user()->load(['departamento', 'cargo']),
        );
    }

    public function logout(Request $request, LogoutAction $logoutAction): JsonResponse
    {
        $logoutAction->execute($request->user());

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }
}
