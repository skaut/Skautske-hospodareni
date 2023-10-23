<?php

declare(strict_types=1);

use Nette\Bootstrap\Configurator;

require __DIR__ . '/../vendor/autoload.php';

setlocale(LC_COLLATE, 'cs_CZ.utf8');

$tempDir = dirname(__DIR__) . '/temp';
$logDir  = __DIR__ . '/../log';

putenv('TMPDIR=' . $tempDir);

$env = getenv();

$configurator = new Configurator();
$configurator->setDebugMode(getenv('DEVELOPMENT_MACHINE') === 'true' ?: '89.177.225.213');
$configurator->enableTracy($logDir);
$configurator->setTempDirectory($tempDir);

$configurator->createRobotLoader()
    ->addDirectory(__DIR__)
    ->register(true);

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addDynamicParameters(['env' => $env]);
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$configurator->addParameters(['logDir' => $logDir]);

return $configurator->createContainer();
