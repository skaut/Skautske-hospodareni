<?php

declare(strict_types=1);

use Nette\Loaders\RobotLoader;
use Nette\Utils\FileSystem;

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/env-bootstrap.php';

date_default_timezone_set('Europe/Prague');

loadTestEnvironmentConfiguration();

FileSystem::createDir(__DIR__.'/../temp/cache/integration');

(new RobotLoader())
    ->addDirectory(__DIR__.'/../app')
    ->addDirectory(__DIR__)
    ->setTempDirectory(__DIR__.'/../temp/cache')
    ->register();
