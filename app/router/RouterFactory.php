<?php

declare(strict_types=1);

namespace App;

use Nette\Application\IRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\SimpleRouter;

/**
 * Router factory.
 */
class RouterFactory
{
    public function createRouter() : IRouter
    {
        $router = new RouteList();

        $router[] = new Route('app.manifest', 'Offline:manifest');
        $router[] = new Route('o-projektu', 'Default:about');
        $router[] = new Route('changelog', 'Default:changelog');
        $router[] = new Route('debugging', 'Accountancy:Debugging:default');
        $router[] = new Route('google/<action>', 'Accountancy:Google:default');
        $router[] = new Route(
            'sign/<action>[/back-<backlink>]',
            [
                'presenter' => 'Auth',
                'action' => 'default',
                'backlink' => null,
            ]
        );

        $router[] = new Route(
            'prirucka/<action>[#<anchor>]',
            [
                'presenter' => 'Tutorial',
                'action' => [
                    Route::VALUE => 'default',
                    Route::FILTER_TABLE => [
                        // řetězec v URL => presenter
                        'vyprava' => 'event',
                        'tabor' => 'camp',
                        'cestovni-prikaz' => 'travelCommand',
                    ],
                ],
            ]
        );

        $router[] = new Route(
            'offline/<action>.html',
            [
                'presenter' => 'Offline',
                'action' => 'list',
            ]
        );

        $router[] = $accountancy = new RouteList('Accountancy');

        $accountancy[] = new Route('export/<action>/<cashbookId>', ['presenter' => 'CashbookExport']);

        $accountancy[] = $this->createCampRoutes();
        $accountancy[] = $this->createEventRoutes();
        $accountancy[] = $this->createTravelRoutes();
        $accountancy[] = $this->createUnitAccountRoutes();
        $accountancy[] = $this->createPaymentRoutes();
        $accountancy[] = $this->createStatRoutes();

        $accountancy[] = new Route('<module>/<presenter>[/<action>]', ['action' => 'default']);

        $router[] = new SimpleRouter('Default:default');

        return $router;
    }

    private function createCampRoutes() : RouteList
    {
        $router = new RouteList('Camp');

        $prefix   = 'tabory';
        $router[] = new Route(
            $prefix . '/<aid [0-9]+>[/<presenter>[/<action>]]',
            [
                'presenter' => [
                    Route::VALUE => 'Detail',
                    Route::FILTER_TABLE => [
                        'ucastnici' => 'Participant',
                        'kniha' => 'Cashbook',
                        'rozpocet' => 'Budget',
                    ],
                ],
                'action' => 'default',
            ]
        );

        $router[] = new Route(
            $prefix . '/<presenter>[/<action>]',
            [
                'presenter' => 'Default',
                'action' => 'default',
            ]
        );

        return $router;
    }

    private function createEventRoutes() : RouteList
    {
        $router = new RouteList('Event');

        $prefix = 'akce';

        $router[] = new Route(
            $prefix . '/<aid [0-9]+>/<presenter>[/<action>]',
            [
                'presenter' => [
                    Route::VALUE => 'Event',
                    Route::FILTER_TABLE => [
                        'ucastnici' => 'Participant',
                        'kniha' => 'Cashbook',
                    ],
                ],
                'action' => 'default',
            ]
        );

        $router[] = new Route(
            $prefix . '/[<presenter>][/<action>]',
            [
                'presenter' => 'Default',
                'action' => 'default',
            ]
        );

        return $router;
    }

    private function createTravelRoutes() : RouteList
    {
        $router = new RouteList('Travel');

        $prefix = 'cestaky';

        $router[] = new Route($prefix . '/vozidla', 'VehicleList:default');
        $router[] = new Route(
            $prefix . '[/<presenter>[/<action>][/<id>]]',
            [
                'presenter' => [
                    Route::VALUE => 'Default',
                    Route::FILTER_TABLE => [
                        'vozidla' => 'Vehicle',
                        'smlouvy' => 'Contract',
                    ],
                ],
                'action' => 'default',
            ]
        );

        return $router;
    }

    private function createUnitAccountRoutes() : RouteList
    {
        $router = new RouteList('UnitAccount');

        $prefix = 'jednotka';

        $router[] = new Route(
            $prefix . '/<unitId [0-9]+>[/<presenter>][/<action>][/<year>]',
            [
                'presenter' => [
                    Route::VALUE => 'Default',
                    Route::FILTER_TABLE => [
                        'kniha' => 'Cashbook',
                        'paragony' => 'Chit',
                        'rozpocet' => 'Budget',
                    ],
                ],
                'action' => 'default',
            ]
        );

        $router[] = new Route(
            $prefix . '[/<presenter>][/<action>]',
            [
                'presenter' => 'Default',
                'action' => 'default',
            ]
        );

        return $router;
    }

    private function createPaymentRoutes() : RouteList
    {
        $router = new RouteList('Payment');

        $prefix = 'platby';

        $router[] = new Route($prefix, 'GroupList:default');
        $router[] = new Route(
            $prefix . '[/<id [0-9]+>[/<presenter>[/<action>]]]',
            [
                'presenter' => [
                    Route::VALUE => 'Payment',
                    Route::FILTER_TABLE => ['bankovni-ucty' => 'BankAccounts'],
                ],
                'action' => 'default',
            ]
        );

        $router[] = new Route(
            $prefix . '[/<presenter>][/<action>]',
            [
                'presenter' => 'Default',
                'action' => 'default',
            ]
        );

        return $router;
    }

    private function createStatRoutes() : RouteList
    {
        $router = new RouteList('Statistics');

        $prefix = 'statistiky';

        $router[] = new Route(
            $prefix . '/<unitId [0-9]+>[/<presenter>][/<action>]',
            [
                'presenter' => [Route::VALUE => 'Default'],
                'action' => 'default',
            ]
        );

        $router[] = new Route(
            $prefix . '[/<presenter>][/<action>]',
            [
                'presenter' => 'Default',
                'action' => 'default',
            ]
        );

        return $router;
    }
}
