<?php

declare(strict_types=1);

$container = require __DIR__ . '/../app/bootstrap.php';

ini_set('memory_limit', '128M');
set_time_limit(60);

$container->getService('application')->run();
