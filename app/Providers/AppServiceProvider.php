<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
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
        $path = base_path('versions.txt');
        $appVersion = '';
        if (is_readable($path)) {
            $raw = trim((string) file_get_contents($path));
            if (preg_match('/^\d+$/', $raw) === 1) {
                $appVersion = $raw;
            }
        }
        View::share('appVersion', $appVersion);
    }
}
