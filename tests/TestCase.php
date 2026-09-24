<?php

namespace Siberfx\CodelabStats\Tests;

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;
use Siberfx\CodelabStats\CodelabStatsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function getPackageProviders($app): array
    {
        return [CodelabStatsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('codelab-stats.key', 'test-key');
        $app['config']->set('codelab-stats.website_id', 13);
    }
}
