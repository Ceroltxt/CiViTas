<?php

namespace App\Http\Controllers\Api\Identity;

use App\Application\Identity\Auth\LoginAction;
use App\Application\Identity\Auth\LogoutAction;
use App\Application\Identity\Auth\PasswordResetAction;
use App\Application\Identity\Auth\RegisterAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\ForgotPasswordRequest;
use App\Http\Requests\Identity\LoginRequest;
use App\Http\Requests\Identity\RegisterRequest;
use App\Http\Requests\Identity\ResetPasswordRequest;
use App\Http\Resources\Identity\FuncionarioResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterAction $registerAction): JsonResponse
    {
        $result = $registerAction->execute(
            nome: $request->validated('nome'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            sobrenome: $request->validated('sobrenome'),
            nomeDepartamento: $request->validated('departamento'),
            cpf: $request->validated('CPF'),
        );

        return response()->json([
            'message' => 'Conta criada com sucesso! Faça seu login para acessar o sistema.',
            'funcionario' => new FuncionarioResource($result['funcionario']),
        ], 201);
    }

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

    public function forgotPassword(ForgotPasswordRequest $request, PasswordResetAction $passwordResetAction): JsonResponse
    {
        $passwordResetAction->sendResetLink($request->validated('email'));

        return response()->json([
            'message' => 'Se o e-mail informado estiver cadastrado, as instruções para redefinição de senha foram enviadas.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request, PasswordResetAction $passwordResetAction): JsonResponse
    {
        $passwordResetAction->resetPassword(
            email: $request->validated('email'),
            token: $request->validated('token'),
            newPassword: $request->validated('password'),
        );

        return response()->json([
            'message' => 'Sua senha foi redefinida com sucesso! Você já pode realizar o login.',
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
