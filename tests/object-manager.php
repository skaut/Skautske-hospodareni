<?php

declare(strict_types=1);

/*
 * This file is used by PHPStan to obtain information about Doctrine entities
 * see https://github.com/phpstan/phpstan-doctrine#configuration
 */

use Doctrine\Persistence\ObjectManager;
use Nette\Bootstrap\Configurator;
use Nette\DI\Extensions\ExtensionsExtension;

require __DIR__ . '/../vendor/autoload.php';

$tempDir = dirname(__DIR__) . '/temp';
$logDir  = __DIR__ . '/../log';

putenv('TMPDIR=' . $tempDir);

$configurator = new Configurator();
$configurator->setDebugMode(true);
$configurator->enableTracy($logDir);
$configurator->setTempDirectory($tempDir);

$configurator->createRobotLoader()
    ->addDirectory(__DIR__ . '/../app')
    ->register(true);

$configurator->defaultExtensions = [
    'extensions' => ExtensionsExtension::class,
    'tracy' => [Tracy\Bridges\Nette\TracyExtension::class, ['%debugMode%', '%consoleMode%']],
];

$configurator->addConfig(__DIR__ . '/integration/config/doctrine.neon');

$configurator->addStaticParameters(['logDir' => $logDir]);

return $configurator->createContainer()->getByType(ObjectManager::class);
