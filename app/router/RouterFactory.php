<?php

namespace App;

use Nette\Application\Routers\RouteList,
    Nette\Application\Routers\Route,
    Sinacek\MyRoute,
    Sinacek\MySimpleRouter;

/**
 * Router factory.
 */
class RouterFactory {

    /**
     * @return \Nette\Application\IRouter
     */
    public function createRouter() {
        $router = new RouteList();

        $router[] = new MyRoute('index.php', ':Default:default', Route::ONE_WAY & Route::SECURED);
        $router[] = new MyRoute('app.manifest', 'Offline:manifest');
        $router[] = new MyRoute("o-projektu", "Default:about");
        $router[] = new MyRoute('sign/<action>[/back-<backlink>]', array(
            "presenter" => "Auth",
            "action" => "default",
            "backlink" => NULL
                ), Route::SECURED);

        $router[] = new MyRoute('prirucka/<action>[#<anchor>]', array(
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

        $router[] = new MyRoute('offline/<action>.html', array(
            "presenter" => "Offline",
            "action" => array(
                Route::VALUE => 'list',
            ),
        ));
        $router[] = AccountancyModule\BasePresenter::createRoutes();
        $router[] = new MySimpleRouter('Default:default', Route::SECURED);
        return $router;
    }

}

//$router[] = new MyRoute('app.manifest', array(
//            "Module" => "Default",
//            "presenter" => "Offline",
//            "action" => "manifest",
//        ));


