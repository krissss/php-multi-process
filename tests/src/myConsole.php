#!/usr/bin/env php
<?php

use Symfony\Component\Console\Application;

$existingAutoloaderFiles = array_filter([
    __DIR__.'/../../../autoload.php',
    __DIR__.'/../../autoload.php',
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../vendor/autoload.php',
], function (string $path) {
    return file_exists($path);
});
$autoloader = reset($existingAutoloaderFiles);
if (!$autoloader) {
    throw new RuntimeException('Unable to locate autoload.php file.');
}

require_once $autoloader;

$app = new Application();
$app->add(new \Kriss\MultiProcessTests\MyTaskCallCommand());
$app->run();
