<?php

declare(strict_types=1);

use App\Environment;
use App\MaintenanceMode;

require __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../app/Environment.php';
require_once __DIR__.'/../app/MaintenanceMode.php';

$projectDir = dirname(__DIR__);

Environment::load($projectDir);
$environment = Environment::getConfiguration();
$maintenance = $environment['maintenance'];

if ($maintenance['enabled']) {
    $clientIp = MaintenanceMode::clientIpFromServer($_SERVER);

    if (! MaintenanceMode::isClientAllowed($clientIp, $maintenance['allowedIps'])) {
        require __DIR__.'/maintenance.php';
    }

    MaintenanceMode::enableDebugBypass();
    Environment::reload($projectDir);
}

$container = require __DIR__ . '/../app/bootstrap.php';

ini_set('memory_limit', '128M');
set_time_limit(120);

$container->getService('application')->run();
