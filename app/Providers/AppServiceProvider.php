<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('borrow-pin', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->route('token'));
        });

        RateLimiter::for('borrow-lookup', function (Request $request) {
            return Limit::perMinute(12)->by($request->ip().'|'.$request->route('token'));
        });

        RateLimiter::for('borrow-submit', function (Request $request) {
            return Limit::perMinutes(10, 5)->by($request->ip().'|'.$request->route('token'));
        });

        RateLimiter::for('return-submit', function (Request $request) {
            return Limit::perMinutes(10, 8)->by($request->ip().'|'.$request->route('token'));
        });
    }
}
