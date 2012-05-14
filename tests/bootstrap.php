<?php

define('WWW_DIR', dirname(__FILE__)."/..");
define('APP_DIR', WWW_DIR . '/app');
define('LIBS_DIR', WWW_DIR . '/libs');
define('TEMP_DIR', APP_DIR . '/temp');
define('TEST_DIR', WWW_DIR . '/tests');

require LIBS_DIR . '/Nette/loader.php';

// Configure application
$configurator = new Configurator;

$configurator->setTempDirectory(APP_DIR. '/temp');
$configurator->enableDebugger(APP_DIR . '/log', "sinacek@gmail.com");

$configurator->addConfig(APP_DIR . '/config.neon', "console");

$configurator->createRobotLoader()
    ->addDirectory(APP_DIR)
    ->addDirectory(LIBS_DIR)
    ->addDirectory(TEST_DIR)

    ->register();

$container = $configurator->createContainer();

// Setup router
$router = $container->router;
$router[] = new Route('index.php', ':Default:default', Route::ONE_WAY);
$router[] = new Route('sign/<action>[/back-<backlink>]', array(
    "presenter" => "Auth",
    "action" => "default",
    "backlink" => NULL
));
Accountancy_BasePresenter::createRoutes($router);
$router[] = new SimpleRouter('Default:default');

// Configure and run the application!
$application = $container->application;
$application->catchExceptions = false;
//$application->errorPresenter = 'Error';
 if (!Environment::isConsole()){
   $application->run();
 } else {
     Environment::getSession()->start();
 }
