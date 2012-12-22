<?php

require LIBS_DIR . '/Nette/loader.php';

// Configure application
$configurator = new Configurator;

$configurator->setTempDirectory(dirname(__FILE__) . '/temp');
$configurator->enableDebugger(dirname(__FILE__) . '/log', "sinacek@gmail.com");
//Debugger::enable(FALSE);
//Debugger::$strictMode = FALSE;
//Debugger::$maxDepth = 6;

$configurator->addConfig(dirname(__FILE__) . '/config.neon');

$configurator->createRobotLoader()
    ->addDirectory(APP_DIR)
    ->addDirectory(LIBS_DIR)
    ->register();

$container = $configurator->createContainer();

// Setup router
$router = new RouteList;
$router[] = new Route('index.php', ':Default:default', Route::ONE_WAY);
$router[] = new Route('sign/<action>[/back-<backlink>]', array(
    "presenter" => "Auth",
    "action" => "default",
    "backlink" => NULL
));

$router[] = new Route('prirucka/<action>', array(
    "presenter" => "Tutorial",
    "action" => array(
        Route::VALUE => 'default',
        Route::FILTER_TABLE => array(
            // řetězec v URL => presenter
            'vyprava' => 'event',
            'tabor' => 'Camp',
            'cestovni-prikaz' => 'Travel',
        )),
));

$router[] = Accountancy_BasePresenter::createRoutes();
$router[] = new SimpleRouter('Default:default');

$container->router = $router;



//if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
//} else {
//    $router[] = new SimpleRouter('Default:default');
//}

// Configure and run the application!
$application = $container->application;
$application->catchExceptions = $configurator->isProductionMode();
$application->errorPresenter = 'Error';
 if (!Environment::isConsole())
   $application->run();
