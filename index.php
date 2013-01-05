<?php

// absolute filesystem path to the web root
define('WWW_DIR', dirname(__FILE__));

// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/app');

// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/libs');

define('TEMP_DIR', APP_DIR . '/temp');

ini_set('memory_limit', '64M');

// load bootstrap file
require APP_DIR . '/bootstrap.php';
