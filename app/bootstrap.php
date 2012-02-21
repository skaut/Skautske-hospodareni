<?php

require dirname(__FILE__) . '/../libs/Nette/loader.php';

// Configure application
$configurator = new Configurator;
$configurator->setTempDirectory(dirname(__FILE__) . '/temp');
$configurator->enableDebugger(dirname(__FILE__) . '/log', "sinacek@gmail.com");
Debugger::$strictMode = FALSE;
Debugger::$maxDepth = 6;

$configurator->addConfig(dirname(__FILE__) . '/config.neon');

$configurator->createRobotLoader()
    ->addDirectory(APP_DIR)
    ->addDirectory(LIBS_DIR)
    ->register();

$container = $configurator->createContainer();

dibi::connect($container->params['database']);


// Setup router
$router = $container->router;
$router[] = new Route('index.php', ':Default:default', Route::ONE_WAY);
$router[] = new Route('sign/<action>[/back-<backlink>]', array(
    "presenter" => "Auth",
    "action" => "default",
    "backlink" => NULL
));

//$router[] = new Route('Ucetnictvi/<presenter>/<action>', 'Ucetnictvi:Default:default');

//$router[] = new Route('Ucetnictvi/<presenter>/<action>', array(
//        'module' => "Ucetnictvi",
//        'presenter' => 'Default',
//        'action' => 'default',
//));

//Accountancy_BasePresenter::createRoutes($router);
//SkautIS_BasePresenter::createRoutes($router);

$router[] = new SimpleRouter('Default:default');

//if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
//} else {
//    $router[] = new SimpleRouter('Default:default');
//}


// Configure and run the application!
$application = $container->application;
//$application->catchExceptions = TRUE;
$application->errorPresenter = 'Error';
$application->run();
