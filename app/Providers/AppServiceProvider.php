<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // @active('route.name') — returns 'active' CSS class if route matches
        Blade::directive('active', function ($expression) {
            return "<?php echo (request()->routeIs({$expression}) ? 'active' : ''); ?>";
        });
    }
}
