<?php

declare(strict_types=1);

/*
 * This file is used by PHPStan to obtain information about Doctrine entities
 * see https://github.com/phpstan/phpstan-doctrine#configuration
 */

use Doctrine\Persistence\ObjectManager;
use Nette\Bootstrap\Configurator;
use Nette\DI\Extensions\ExtensionsExtension;
use Tracy\Bridges\Nette\TracyExtension;

require __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/env-bootstrap.php';

$tempDir = dirname(__DIR__).'/temp';
$logDir = __DIR__.'/../log';

putenv('TMPDIR='.$tempDir);

$environment = loadTestEnvironmentConfiguration();

$configurator = new Configurator();
$configurator->setDebugMode(true);
$configurator->enableTracy($logDir);
$configurator->setTempDirectory($tempDir);

$configurator->createRobotLoader()
    ->addDirectory(__DIR__.'/../app')
    ->register(true);

$configurator->defaultExtensions = [
    'extensions' => ExtensionsExtension::class,
    'tracy' => [TracyExtension::class, ['%debugMode%', '%consoleMode%']],
];

$configurator->addStaticParameters(['envConfig' => $environment]);
$configurator->addConfig(__DIR__.'/integration/config/doctrine.neon');

$configurator->addStaticParameters(['logDir' => $logDir]);

return $configurator->createContainer()->getByType(ObjectManager::class);
