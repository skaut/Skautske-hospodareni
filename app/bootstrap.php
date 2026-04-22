<?php

declare(strict_types=1);

use App\Environment;
use Nette\Bootstrap\Configurator;
use Nette\Utils\FileSystem;

require __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/Environment.php';

setlocale(LC_COLLATE, 'cs_CZ.utf8');

$projectDir = dirname(__DIR__);
$tempDir = dirname(__DIR__).'/temp';
$logDir = __DIR__.'/../log';

putenv('TMPDIR='.$tempDir);

Environment::load($projectDir);
$environment = Environment::getConfiguration();
$appEnv = $environment['appEnv'];

$configurator = new Configurator();
$configurator->setDebugMode($appEnv === 'dev' || $appEnv === 'ci');
$configurator->enableTracy($logDir);
$configurator->setTempDirectory($tempDir);

FileSystem::createDir($tempDir.'/sessions');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__)
    ->register(true);

$configurator->addStaticParameters([
    'envConfig' => $environment,
    'logDir' => $logDir,
]);
$configurator->addConfig(__DIR__.'/config/config.neon');
$environmentConfig = __DIR__.'/config/config.'.$appEnv.'.neon';
if (is_file($environmentConfig)) {
    $configurator->addConfig($environmentConfig);
}

return $configurator->createContainer();
