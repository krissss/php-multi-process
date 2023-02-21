<?php

namespace Kriss\MultiProcess\Integrations\Laravel;

use Illuminate\Support\ServiceProvider;
use Kriss\MultiProcess\PendingTaskProcess;
use Kriss\MultiProcess\SymfonyConsole\Commands\TaskCallCommand;

class MultiProcessServiceProvider extends ServiceProvider
{
    public function isDeferred()
    {
        return true;
    }

    public function boot()
    {
        $appDir = dirname(__DIR__, 6);
        $existingArtisanFiles = array_filter([
            $appDir.'/artisan',
            __DIR__.'/../artisan',
        ], function (string $path) {
            return file_exists($path);
        });
        $artisanFile = reset($existingArtisanFiles);

        PendingTaskProcess::$globalConsoleFile = $artisanFile ?: 'artisan';
    }

    public function register()
    {
        $this->commands([TaskCallCommand::class]);
    }

    public function provides()
    {
        return [
            TaskCallCommand::class,
        ];
    }
}