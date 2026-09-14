<?php

namespace App\Application\Identity\Auth;

use App\Mail\Identity\ResetPasswordMail;
use App\Models\Identity\Funcionario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetAction
{
    /**
     * Envia o link com token de redefinição de senha se o usuário existir.
     */
    public function sendResetLink(string $email): void
    {
        $normalizedEmail = mb_strtolower(trim($email));

        $funcionario = Funcionario::query()
            ->where('email', $normalizedEmail)
            ->first();

        // Se o funcionário não existir, encerra sem expor erro para mitigar enumeração de contas
        if ($funcionario === null) {
            return;
        }

        $plainToken = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $normalizedEmail],
            [
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ]
        );

        Mail::to($funcionario->email)->send(new ResetPasswordMail($funcionario, $plainToken));
    }

    /**
     * Valida o token e atualiza a senha do funcionário, revogando sessões anteriores.
     */
    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $normalizedEmail = mb_strtolower(trim($email));

        $record = DB::table('password_reset_tokens')
            ->where('email', $normalizedEmail)
            ->first();

        if ($record === null) {
            throw ValidationException::withMessages([
                'email' => ['Este token de redefinição de senha é inválido ou já foi utilizado.'],
            ]);
        }

        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $normalizedEmail)->delete();
            throw ValidationException::withMessages([
                'token' => ['Este link de redefinição expirou. Por favor, solicite um novo.'],
            ]);
        }

        if (! Hash::check($token, $record->token)) {
            throw ValidationException::withMessages([
                'token' => ['O token de redefinição informado é inválido.'],
            ]);
        }

        $funcionario = Funcionario::query()
            ->where('email', $normalizedEmail)
            ->first();

        if ($funcionario === null) {
            throw ValidationException::withMessages([
                'email' => ['Usuário não encontrado.'],
            ]);
        }

        // Atualiza a senha (o cast 'hashed' do Funcionario cuidará da criptografia)
        $funcionario->senha = $newPassword;
        $funcionario->save();

        // Remove o token utilizado
        DB::table('password_reset_tokens')
            ->where('email', $normalizedEmail)
            ->delete();

        // Revoga todos os tokens Sanctum do funcionário para deslogar sessões antigas
        $funcionario->tokens()->delete();
    }
}
