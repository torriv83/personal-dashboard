<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Creates the application and automatically clears config cache if present.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // CRITICAL: Auto-clear config cache to prevent tests from using production database
        $this->clearCacheIfCached($app);

        return $app;
    }

    /**
     * Automatically clears cache if config is cached.
     * This prevents tests from accidentally using production database settings.
     */
    protected function clearCacheIfCached(Application $app): void
    {
        if (! $app->configurationIsCached()) {
            return;
        }

        // Config is cached - clear it automatically
        $commands = ['config:clear', 'cache:clear'];
        foreach ($commands as $command) {
            Artisan::call($command);
        }

        // Throw exception to force re-running tests with cleared config
        throw new \RuntimeException(
            'Configuration was cached and has been automatically cleared. ' .
            'Please re-run your tests. To avoid this in the future, do not use ' .
            '`php artisan config:cache` in development.'
        );
    }
}
