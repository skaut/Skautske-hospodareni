<?php

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;
$configurator->setDebugMode(getenv('DEVELOPMENT_MACHINE') === 'true' ?: '78.45.163.71');
$configurator->enableDebugger(__DIR__ . '/../nette-log');
$configurator->setTempDirectory(__DIR__ . '/../nette-temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->addDirectory(__DIR__ . '/../vendor/others')
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
