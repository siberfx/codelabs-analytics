<?php

namespace Siberfx\CodelabStats;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Siberfx\CodelabStats\Console\ListWebsitesCommand;
use Siberfx\CodelabStats\Http\Controllers\StatsController;

class CodelabStatsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/codelab-stats.php', 'codelab-stats');

        // Not a singleton: config changes made at runtime are picked up on the next resolve.
        $this->app->bind(CodelabStats::class, fn ($app) => new CodelabStats(
            $app->make(HttpFactory::class),
            $app->make(CacheFactory::class)->store($app['config']->get('codelab-stats.cache.store')),
            $app['config']->get('codelab-stats'),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/codelab-stats.php' => config_path('codelab-stats.php'),
            ], 'codelab-stats-config');

            $this->commands([ListWebsitesCommand::class]);
        }

        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $routes = $this->app['config']->get('codelab-stats.routes', []);

        if (! ($routes['enabled'] ?? false) || $this->app->routesAreCached()) {
            return;
        }

        Route::prefix($routes['prefix'] ?? 'codelab-stats')
            ->middleware($routes['middleware'] ?? ['web', 'auth'])
            ->name($routes['name'] ?? 'codelab-stats.')
            ->group(function (): void {
                Route::get('summary', [StatsController::class, 'summary'])->name('summary');
                Route::get('stats', [StatsController::class, 'stats'])->name('stats');
            });
    }
}
