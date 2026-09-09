<?php

namespace SbAdmin\Dashboard;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class SbAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sbadmin.php', 'sbadmin');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sbadmin');

        // Torna os componentes anonimos em resources/views/components disponiveis
        // como <x-sbadmin::nome-do-componente> em qualquer projeto que instale o pacote.
        Blade::anonymousComponentNamespace('sbadmin::components', 'sbadmin');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sbadmin.php' => config_path('sbadmin.php'),
            ], 'sbadmin-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/sbadmin'),
            ], 'sbadmin-views');

            $this->publishes([
                __DIR__.'/../resources/sass/sbadmin' => resource_path('sass/sbadmin'),
            ], 'sbadmin-assets');

            $this->publishes([
                __DIR__.'/../resources/js/sbadmin' => resource_path('js/sbadmin'),
            ], 'sbadmin-assets');
        }
    }
}
