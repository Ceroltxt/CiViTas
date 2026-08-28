<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use App\Models\Identity\Funcionario;
use App\Models\Project\Projeto;
use App\Models\Task\Tarefa;
use App\Models\Team\Equipe;
use App\Policies\Identity\FuncionarioPolicy;
use App\Policies\Project\ProjetoPolicy;
use App\Policies\Task\TarefaPolicy;
use App\Policies\Team\EquipePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->configureDefaults();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Tarefa::class, TarefaPolicy::class);
        Gate::policy(Projeto::class, ProjetoPolicy::class);
        Gate::policy(Equipe::class, EquipePolicy::class);
        Gate::policy(Funcionario::class, FuncionarioPolicy::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        JsonResource::withoutWrapping();

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
