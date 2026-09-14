<?php

namespace App\Providers;

use App\Events\Identity\FuncionarioRegistered;
use App\Listeners\Identity\SendWelcomeEmailListener;
use App\Models\Identity\Funcionario;
use App\Models\Project\Projeto;
use App\Models\Task\Tarefa;
use App\Models\Team\Equipe;
use App\Policies\Identity\FuncionarioPolicy;
use App\Policies\Project\ProjetoPolicy;
use App\Policies\Task\TarefaPolicy;
use App\Policies\Team\EquipePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        $this->registerEvents();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Tarefa::class, TarefaPolicy::class);
        Gate::policy(Projeto::class, ProjetoPolicy::class);
        Gate::policy(Equipe::class, EquipePolicy::class);
        Gate::policy(Funcionario::class, FuncionarioPolicy::class);
    }

    protected function registerEvents(): void
    {
        Event::listen(
            FuncionarioRegistered::class,
            SendWelcomeEmailListener::class,
        );
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
