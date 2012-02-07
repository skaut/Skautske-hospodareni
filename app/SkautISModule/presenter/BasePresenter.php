<?php

class SkautIS_BasePresenter extends BasePresenter {

    protected $service;

    protected function startup() {
        parent::startup();
    }

  
    //vrati routy pro modull
    static function createRoutes($router, $prefix ="") {

        $router[] = new Route($prefix . 'skautIS/<presenter>/<action>', array(
                    'module' => "skautIS",
                    'presenter' => 'Default',
                    'action' => 'default',
                ));
//        $router[] = new Route($prefix . 'Ucetnictvi/<presenter>/<action>', array(
//                    'module' => "Ucetnictvi",
//                    'presenter' => 'Default',
//                    'action' => 'default',
//                ));
    }

}
