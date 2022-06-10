<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
        
        //padrão do limite de caracteres para campos string
        Schema::defaultStringLength(191);
    }
    
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('path.public', function() {
          return base_path().'/'.env('DIR_PUBLIC');
        });
    }
}
