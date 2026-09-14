<?php

use App\Mail\Identity\ResetPasswordMail;
use App\Models\Identity\Funcionario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('forgot password sends reset email with token when user exists', function () {
    Mail::fake();

    $funcionario = Funcionario::query()->where('email', 'ana@civitas.test')->firstOrFail();

    $response = $this->postJson(route('api.auth.forgot-password'), [
        'email' => 'ana@civitas.test',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message']);

    $record = DB::table('password_reset_tokens')->where('email', 'ana@civitas.test')->first();
    expect($record)->not()->toBeNull();

    Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($record) {
        return $mail->hasTo('ana@civitas.test')
            && Hash::check($mail->token, $record->token);
    });
});

test('forgot password returns 200 without sending email when user does not exist', function () {
    Mail::fake();

    $response = $this->postJson(route('api.auth.forgot-password'), [
        'email' => 'inexistente@gmail.com',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message']);

    Mail::assertNothingSent();

    $record = DB::table('password_reset_tokens')->where('email', 'inexistente@gmail.com')->first();
    expect($record)->toBeNull();
});

test('reset password successfully updates password, deletes token and revokes tokens', function () {
    $funcionario = Funcionario::query()->where('email', 'ana@civitas.test')->firstOrFail();

    // Cria um token Sanctum ativo
    $activeToken = $funcionario->createToken('test-device')->plainTextToken;
    expect($funcionario->tokens()->count())->toBe(1);

    // Insere o token de redefinição
    $plainToken = 'test-token-abcdef123456';
    DB::table('password_reset_tokens')->insert([
        'email' => 'ana@civitas.test',
        'token' => Hash::make($plainToken),
        'created_at' => now(),
    ]);

    $response = $this->postJson(route('api.auth.reset-password'), [
        'email' => 'ana@civitas.test',
        'token' => $plainToken,
        'password' => 'nova-senha-segura-123',
        'password_confirmation' => 'nova-senha-segura-123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message']);

    // Token de redefinição foi removido
    $record = DB::table('password_reset_tokens')->where('email', 'ana@civitas.test')->first();
    expect($record)->toBeNull();

    // Sessões / Tokens Sanctum anteriores foram revogados
    expect($funcionario->fresh()->tokens()->count())->toBe(0);

    // Login com a senha antiga deve falhar
    $this->postJson(route('api.auth.login'), [
        'email' => 'ana@civitas.test',
        'password' => 'secret-password',
    ])->assertStatus(422);

    // Login com a nova senha deve ter sucesso
    $loginResponse = $this->postJson(route('api.auth.login'), [
        'email' => 'ana@civitas.test',
        'password' => 'nova-senha-segura-123',
    ]);
    $loginResponse->assertOk()
        ->assertJsonStructure(['token', 'funcionario']);
});

test('reset password fails with invalid token', function () {
    DB::table('password_reset_tokens')->insert([
        'email' => 'ana@civitas.test',
        'token' => Hash::make('token-correto'),
        'created_at' => now(),
    ]);

    $response = $this->postJson(route('api.auth.reset-password'), [
        'email' => 'ana@civitas.test',
        'token' => 'token-errado-invalido',
        'password' => 'nova-senha-segura-123',
        'password_confirmation' => 'nova-senha-segura-123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});

test('reset password fails with expired token', function () {
    DB::table('password_reset_tokens')->insert([
        'email' => 'ana@civitas.test',
        'token' => Hash::make('token-expirado'),
        'created_at' => now()->subMinutes(61),
    ]);

    $response = $this->postJson(route('api.auth.reset-password'), [
        'email' => 'ana@civitas.test',
        'token' => 'token-expirado',
        'password' => 'nova-senha-segura-123',
        'password_confirmation' => 'nova-senha-segura-123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token']);

    // Confirma que o token expirado foi limpo do banco
    expect(DB::table('password_reset_tokens')->where('email', 'ana@civitas.test')->first())->toBeNull();
});

test('reset password requires password confirmation', function () {
    DB::table('password_reset_tokens')->insert([
        'email' => 'ana@civitas.test',
        'token' => Hash::make('token-valido'),
        'created_at' => now(),
    ]);

    $response = $this->postJson(route('api.auth.reset-password'), [
        'email' => 'ana@civitas.test',
        'token' => 'token-valido',
        'password' => 'nova-senha-segura-123',
        'password_confirmation' => 'confirmacao-diferente-123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});
