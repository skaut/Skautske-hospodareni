<?php

define('WWW_DIR', dirname(__FILE__));

define('APP_DIR', WWW_DIR . '/../app');

//define('TEMP_DIR', APP_DIR . '/temp');

$container = require __DIR__ . '/../app/bootstrap.php';

ini_set('memory_limit', '128M');
set_time_limit(60);

$container->getService('application')->run();
