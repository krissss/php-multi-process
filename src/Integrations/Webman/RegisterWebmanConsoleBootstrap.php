<?php

namespace Kriss\MultiProcess\Integrations\Webman;

use Kriss\MultiProcess\PendingTaskProcess;
use Webman\Bootstrap;

class RegisterWebmanConsoleBootstrap implements Bootstrap
{
    /**
     * @inheritDoc
     */
    public static function start($worker)
    {
        $appDir = dirname(__DIR__, 6);
        $existingArtisanFiles = array_filter([
            $appDir.'/webman',
            __DIR__.'/../webman',
        ], function (string $path) {
            return file_exists($path);
        });
        $artisanFile = reset($existingArtisanFiles);

        PendingTaskProcess::$globalConsoleFile = $artisanFile ?: 'webman';
    }
}