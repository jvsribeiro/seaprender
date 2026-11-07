<?php

namespace App\Providers;

use App\Contracts\QuizQuestionGenerator;
use App\Models\User;
use App\Support\DisabledQuizQuestionGenerator;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(QuizQuestionGenerator::class, DisabledQuizQuestionGenerator::class);

        ServeCommand::$passthroughVariables = [...ServeCommand::$passthroughVariables, 'TEMP', 'TMP'];
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('administer', fn (User $user): bool => $user->isAdmin());
    }
}
