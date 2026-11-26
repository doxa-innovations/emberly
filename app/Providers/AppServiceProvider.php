<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Gate::define('view-reports', fn (User $user) => $user->is_operator);

        RateLimiter::for('generations', fn (Request $request) => Limit::perMinute(12)->by($request->user()->id));
    }
}
