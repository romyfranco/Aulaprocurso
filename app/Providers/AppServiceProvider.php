<?php

namespace App\Providers;

use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Observers\QuizAttemptObserver;
use App\Observers\TopicObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        QuizAttempt::observe(QuizAttemptObserver::class);
        Topic::observe(TopicObserver::class);
        Gate::before(fn ($user) => $user->role === 'admin' ? true : null);
    }
}
