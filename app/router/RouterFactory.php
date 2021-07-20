<?php

declare(strict_types=1);

namespace App;

use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\SimpleRouter;
use Nette\Routing\Router;

/**
 * Router factory.
 */
class RouterFactory
{
    public function createRouter(): Router
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

        $accountancy->addRoute('export/<action>/<cashbookId>', ['presenter' => 'CashbookExport']);

        $this->createCampRoutes($accountancy);
        $this->createEventRoutes($accountancy);
        $this->createTravelRoutes($accountancy);
        $this->createUnitAccountRoutes($accountancy);
        $this->createPaymentRoutes($accountancy);
        $this->createStatRoutes($accountancy);

        $accountancy->addRoute('<module>/<presenter>[/<action>]', ['action' => 'default']);

        $router->add(new SimpleRouter('Default:default'));

        return $router;
    }

    private function createCampRoutes(RouteList $parent): void
    {
        $parent
            ->withModule('Camp')
            ->addRoute('tabory', 'Default:default')
            ->withPath('tabory')
            ->addRoute(
                '<aid [0-9]+>[/<presenter>[/<action>]]',
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
            )
            ->addRoute('<presenter>[/<action>]', ['action' => 'default']);
    }

    private function createEventRoutes(RouteList $parent): void
    {
        $parent
            ->withModule('Event')
            ->addRoute('akce', 'Default:default')
            ->withPath('akce')
            ->addRoute(
                '<aid [0-9]+>/<presenter>[/<action>]',
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
            )
            ->addRoute('<presenter>[/<action>]', ['action' => 'default']);
    }

    private function createTravelRoutes(RouteList $parent): void
    {
        $parent
            ->withModule('Travel')
            ->addRoute('cestaky', 'Default:default')
            ->withPath('cestaky')
            ->addRoute('vozidla', 'VehicleList:default')
            ->addRoute(
                '<presenter>[/<action>][/<id>]',
                [
                    'presenter' => [
                        Route::FILTER_TABLE => [
                            'vozidla' => 'Vehicle',
                            'smlouvy' => 'Contract',
                        ],
                    ],
                    'action' => 'default',
                ]
            );
    }

    private function createUnitAccountRoutes(RouteList $parent): void
    {
        $parent
            ->withModule('UnitAccount')
            ->addRoute('jednotka', 'Default:default')
            ->withPath('jednotka')
            ->addRoute(
                '<unitId [0-9]+>[/<presenter>][/<action>][/<year>]',
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
            )
            ->addRoute('<presenter>[/<action>]', ['action' => 'default']);
    }

    private function createPaymentRoutes(RouteList $parent): void
    {
        $parent
            ->withModule('Payment')
            ->withPath('platby')
            ->addRoute('emaily ? jednotka=<unitId>', 'Mail:default')
            ->addRoute('bankovni-ucty ? jednotka=<unitId>', 'BankAccounts:default')
            ->addRoute(
                'bankovni-ucty/<id [0-9]+>[/<action>] ? jednotka=<unitId>',
                [
                    'presenter' => 'BankAccounts',
                    'action' => [
                        Route::VALUE => 'default',
                        Route::FILTER_TABLE => ['upravit' => 'edit'],
                    ],
                ]
            )
            ->addRoute('', 'GroupList:default')
            ->addRoute('skupiny/<id [0-9]+>[/<action>]', [
                'presenter' => 'Payment',
                'action' => 'default',
            ])
            ->addRoute('[<presenter>[/<action>]]', ['action' => 'default']);
    }

    private function createStatRoutes(RouteList $parent): void
    {
        $parent
            ->withModule('Statistics')
            ->addRoute('statistiky', 'Default:default')
            ->withPath('statistiky')
            ->addRoute(
                '<unitId [0-9]+>[/<presenter>][/<action>]',
                [
                    'presenter' => [Route::VALUE => 'Default'],
                    'action' => 'default',
                ]
            )
            ->addRoute('[<presenter>][/<action>]', ['action' => 'default']);
    }
}
