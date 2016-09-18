<?php

namespace App;

use Nette\Application\Routers\RouteList,
    Nette\Application\Routers\Route,
        \Sinacek\MyRoute;

/**
 * Router factory.
 */
class RouterFactory {

	/** @var array */
	private $routes;

	/** @var bool */
	private $debugMode;

	/**
	 * RouterFactory constructor.
	 * @param array $routes
	 * @param bool $debugMode
	 */
	public function __construct(array $routes, $debugMode)
	{
		$this->routes = $routes;
		$this->debugMode = $debugMode;
	}

	/**
     * @return \Nette\Application\IRouter
     */
    public function createRouter()
	{
        $router = new RouteList();

		// Disable https for development
		$secured = $this->debugMode ? 0 : Route::SECURED;

		foreach($this->routes as $prefix => $config) {
			$metadata = [
				'module' => 'Accountancy:' . $config['module'],
				'presenter' => [
					Route::FILTER_TABLE => $config['presenters'],
					Route::VALUE => $config['defaultPresenter'],
				],
				'action' => [
					Route::VALUE => 'default',
				]
			];
			$router[] = new Route($prefix.'/<aid [0-9]+>[/<presenter>[/<action>/]]', $metadata, $secured);
			$router[] = new Route($prefix.'[/<presenter>/][<action>/]', $metadata, $secured);
		}

        $router[] = new MyRoute('index.php', 'Default:default', Route::ONE_WAY & Route::SECURED);
        $router[] = new Route('app.manifest', 'Offline:manifest');
        $router[] = new Route("o-projektu", "Default:about");
        $router[] = new MyRoute('sign/<action>[/back-<backlink>]', array(
            "presenter" => "Auth",
            "action" => "default",
            "backlink" => NULL
                ), Route::SECURED);

        $router[] = new Route('prirucka/<action>[#<anchor>]', array(
            "presenter" => "Tutorial",
            "action" => array(
                Route::VALUE => 'default',
                Route::FILTER_TABLE => array(
                    // řetězec v URL => presenter
                    'vyprava' => 'event',
                    'tabor' => 'camp',
                    'cestovni-prikaz' => 'travelCommand',
                )),
        ));

        $router[] = new Route('offline/<action>.html', array(
            "presenter" => "Offline",
            "action" => array(
                Route::VALUE => 'list',
            ),
        ));
        $router[] = AccountancyModule\BasePresenter::createRoutes();
        $router[] = new \Sinacek\MySimpleRouter('Default:default', Route::SECURED);
        return $router;
    }

}

//$router[] = new Route('app.manifest', array(
//            "Module" => "Default",
//            "presenter" => "Offline",
//            "action" => "manifest",
//        ));


