<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SmsService;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsService::class, function ($app) {
            //return new SmsService(env('FAST2SMS_API_KEY')); // Replace 'your-api-key-here' with your actual API key
            return new SmsService('blYMc0vgn2ahGPxRyzoD4XCNqKf69t73kVHSrwiOE5AdjTJLBQr5hxF09gzPZWBGfDwcIUvuEeVml7Js'); // Replace 'your-api-key-here' with your actual API key
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap(); //added for laravel pagination
    }
}
