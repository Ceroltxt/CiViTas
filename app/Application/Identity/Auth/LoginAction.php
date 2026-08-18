<?php

namespace App\Application\Identity\Auth;

use App\Models\Identity\Funcionario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginAction
{
    /**
     * @return array{token: string, funcionario: Funcionario}
     */
    public function execute(string $email, string $password, string $deviceName = 'api'): array
    {
        $funcionario = Funcionario::query()->where('email', $email)->first();

        if ($funcionario === null || ! Hash::check($password, $funcionario->senha)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $token = $funcionario->createToken($deviceName)->plainTextToken;

        return [
            'token' => $token,
            'funcionario' => $funcionario->load(['departamento', 'cargo']),
        ];
    }
}
