<?php

namespace Farayaz\LaravelSpy;

use Farayaz\LaravelSpy\Commands\CleanCommand;
use Illuminate\Support\ServiceProvider;

class LaravelSpyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        LaravelSpy::boot();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/spy.php' => config_path('spy.php'),
            ], 'config');

            $migrationNames = [
                'create_spy_http_logs_table.php',
                'add_duration_ms_to_spy_http_logs_table.php',
            ];
            $migrationPaths = [];

            foreach ($migrationNames as $migrationName) {
                $migrationPaths[__DIR__ . '/../database/migrations/' . $migrationName . '.stub'] = $this->getMigrationFileName($migrationName);
            }

            $this->publishes($migrationPaths, 'migrations');

            $this->commands([
                CleanCommand::class,
            ]);
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'spy');

        if (config('spy.dashboard.enabled')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/spy'),
            ], 'spy-views');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/spy.php', 'spy');
    }

    protected function getMigrationFileName(string $file): string
    {
        foreach (glob(database_path('migrations/*_' . $file)) as $existing) {
            return $existing;
        }

        return database_path('migrations/' . date('Y_m_d_His') . '_' . $file);
    }
}
