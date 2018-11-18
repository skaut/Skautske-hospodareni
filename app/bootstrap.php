<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

putenv('TMPDIR=' . dirname(__DIR__) . '/nette-temp');

$configurator = new Nette\Configurator();
$configurator->setDebugMode(getenv('DEVELOPMENT_MACHINE') === 'true' ?: '94.113.119.27');
$configurator->enableDebugger(__DIR__ . '/../nette-log');
$configurator->setTempDirectory(__DIR__ . '/../nette-temp');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__)
    ->addDirectory(__DIR__ . '/../vendor/others')
    ->register(true);

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
