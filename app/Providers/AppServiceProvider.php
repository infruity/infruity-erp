<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // A wrong .env must not show stack traces, queries, or session data to visitors of the live site.
        if ($this->app->environment('production')) {
            config(['app.debug' => false, 'debugbar.enabled' => false]);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('phone_number', function($attribute, $value, $parameters)
        {
            return substr($value, 0, 2) == '01';
        });
        Validator::extend('alpha_spaces', function ($attribute, $value) {
            return preg_match('/^[\pL\s]+$/u', $value); 
        });
        
        Validator::extend('alphanum_spaces', function ($attribute, $value) {
            return preg_match('/^[a-zA-Z0-9 ]*$/u', $value); 
        });
        
        Validator::extend('gelar', function ($attribute, $value) {
            return preg_match('/^[a-zA-Z0-9 ,.(.*?)]*$/u', $value); 
        });
    }
}
