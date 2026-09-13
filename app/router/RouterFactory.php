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

        // Static pages
        $router->addRoute('app.manifest', 'Offline:manifest');
        $router->addRoute('o-projektu', 'Default:about');
        $router->addRoute('posily', 'Default:reinforcement');
        $router->addRoute('changelog', 'Default:changelog');
        $router->addRoute('design', 'Design:default');
        $router->addRoute('nahlasit-problem', 'BugReport:default');

        // Sections
        $this->addTravelRoutes($router);
        $this->addEventRoutes($router);
        $this->addUnitRoutes($router);
        $this->addPaymentRoutes($router);
        $this->addAdminRoutes($router);
        $this->addSettingsRoutes($router);

        // Utility & auth
        $router->addRoute('debugging', 'Admin:Debugging:default');
        $router->addRoute('prodlouzit-prihlaseni', 'SessionKeepAlive:default');
        $router->addRoute('google/<action>', 'Settings:Google:default');
        $router->addRoute(
            'sign/<action>[/back-<backlink>]',
            [
                'presenter' => 'Auth',
                'action' => 'default',
                'backlink' => null,
            ],
        );
        $router->addRoute('nastenka', 'Dashboard:default');
        $router->addRoute(
            'prirucka/<action>[#<anchor>]',
            [
                'presenter' => 'Tutorial',
                'action' => [
                    Route::VALUE => 'default',
                    Route::FILTER_TABLE => [
                        'vyprava' => 'event',
                        'tabor' => 'camp',
                        'cestovni-prikaz' => 'travelCommand',
                    ],
                ],
            ],
        );
        $router->addRoute(
            'offline/<action>.html',
            [
                'presenter' => 'Offline',
                'action' => 'list',
            ],
        );
        $router->addRoute('export/<action>/<cashbookId>', ['presenter' => 'Unit:CashbookExport', 'action' => 'default']);

        $router->add(new SimpleRouter('Default:default'));

        return $router;
    }

    private function addTravelRoutes(RouteList $router): void
    {
        $router->add($travel = new RouteList('Travel'));
        $travel
            ->addRoute('cestaky/prikazy/new', 'Command:default')
            ->addRoute('cestaky/prikazy/<id [0-9]+>/edit', 'Command:edit')
            ->addRoute('cestaky/prikazy/<id [0-9]+>/print', 'Command:print')
            ->addRoute('cestaky/prikazy/<id [0-9]+>', 'Command:detail')
            ->addRoute('cestaky/vozidla', 'VehicleList:default')
            ->addRoute('cestaky/vozidla/<action>[/<id [0-9]+>]', ['presenter' => 'Vehicle', 'action' => 'default'])
            ->addRoute('cestaky/smlouvy[/<action>][/<id [0-9]+>]', ['presenter' => 'Contract', 'action' => 'default'])
            ->addRoute('cestaky', 'Default:default');
    }

    private function addEventRoutes(RouteList $router): void
    {
        // Education
        $router->addRoute(
            'vzdelavacky/<aid [0-9]+>[/<presenter>[/<action>]]',
            [
                'presenter' => [
                    Route::VALUE => 'Education:Education',
                    Route::PATTERN => 'ucastnici|kniha|rozpocet|opravneni',
                    Route::FILTER_TABLE => [
                        'ucastnici' => 'Education:Participant',
                        'kniha' => 'Education:Cashbook',
                        'rozpocet' => 'Education:Budget',
                        'opravneni' => 'Education:Privileges',
                    ],
                ],
                'action' => 'default',
            ],
        );
        $router->addRoute('vzdelavacky', 'Education:Default:default');

        // Camps
        $router->addRoute(
            'tabory/<aid [0-9]+>/<action>',
            [
                'presenter' => 'Camps:Detail',
                'action' => [
                    Route::PATTERN => 'report',
                ],
            ],
        );
        $router->addRoute(
            'tabory/<aid [0-9]+>[/<presenter>[/<action>]]',
            [
                'presenter' => [
                    Route::VALUE => 'Camps:Detail',
                    Route::PATTERN => 'ucastnici|kniha|rozpocet',
                    Route::FILTER_TABLE => [
                        'ucastnici' => 'Camps:Participant',
                        'kniha' => 'Camps:Cashbook',
                        'rozpocet' => 'Camps:Budget',
                    ],
                ],
                'action' => 'default',
            ],
        );
        $router->addRoute('tabory', 'Camps:Default:default');

        // Events
        $router->addRoute('akce/nova', 'Events:NewEvent:default');
        $router->addRoute(
            'akce/<aid [0-9]+>/<action>',
            [
                'presenter' => 'Events:Event',
                'action' => [
                    Route::PATTERN => 'report|print-all|logs',
                    Route::FILTER_TABLE => [
                        'print-all' => 'printAll',
                    ],
                ],
            ],
        );
        $router->addRoute(
            'akce/<aid [0-9]+>[/<presenter>[/<action>]]',
            [
                'presenter' => [
                    Route::VALUE => 'Events:Event',
                    Route::PATTERN => 'ucastnici|kniha|opravneni',
                    Route::FILTER_TABLE => [
                        'ucastnici' => 'Events:Participant',
                        'kniha' => 'Events:Cashbook',
                        'opravneni' => 'Events:Privileges',
                    ],
                ],
                'action' => 'default',
            ],
        );
        $router->addRoute('akce', 'Events:Default:default');
    }

    private function addUnitRoutes(RouteList $router): void
    {
        $router->addRoute(
            'jednotka/<unitId [0-9]+>/rozpocet[/<action>] ? rok=<year>',
            [
                'presenter' => 'Unit:Budget',
                'action' => [
                    Route::VALUE => 'default',
                    Route::FILTER_TABLE => ['pridat' => 'add'],
                ],
            ],
        );
        $router->addRoute('jednotka/<unitId [0-9]+>/paragony ? rok=<year> & onlyUnlocked=<onlyUnlocked>', 'Unit:Chit:default');
        $router->addRoute('jednotka/<unitId [0-9]+>/kniha ? rok=<year>', 'Unit:Cashbook:default');
        $router->addRoute('jednotka ? jednotka=<unitId> & rok=<year>', 'Unit:Cashbook:default');
    }

    private function addPaymentRoutes(RouteList $router): void
    {
        $router->add($payments = new RouteList('Payments'));
        $payments
            ->addRoute('platby', 'Dashboard:default')
            // Groups
            ->addRoute('platby/skupiny ? onlyOpen=<onlyOpen>', 'GroupList:default')
            ->addRoute('platby/skupiny/nova', 'Group:newGroup')
            ->addRoute('platby/skupiny/<id [0-9]+>/klonovat', 'Group:clone')
            ->addRoute('platby/skupiny/<id [0-9]+>/upravit', 'Group:edit')
            ->addRoute('platby/skupiny/<id [0-9]+>/platby', 'Payment:default')
            ->addRoute('platby/skupiny/<id [0-9]+>/ucastnici', 'Participants:default')
            ->addRoute('platby/skupiny/<id [0-9]+>/osoby', 'People:default')
            ->addRoute('platby/skupiny/<groupId [0-9]+>/casopisy', 'Journal:default')
            ->addRoute('platby/skupiny/<id [0-9]+>/vratky', 'Repayment:default')
            // Event-based group creation
            ->addRoute('platby/tabory/<campId [0-9]+>/nova', 'CampCreateGroup:default')
            ->addRoute('platby/tabory', 'CampSelectForGroup:default')
            ->addRoute('platby/akce/<eventId [0-9]+>/nova', 'EventCreateGroup:default')
            ->addRoute('platby/akce', 'EventSelectForGroup:default')
            ->addRoute('platby/vzdelavacky/<educationId [0-9]+>/nova', 'EducationCreateGroup:default')
            ->addRoute('platby/vzdelavacky', 'EducationSelectForGroup:default')
            ->addRoute('platby/registrace/nova', 'RegistrationCreateGroup:default')
            ->addRoute('platby/registrace/<id [0-9]+>/osoby', 'RegistrationAddMembers:default')
            ->addRoute('platby/registrace/<groupId [0-9]+>/casopisy', 'RegistrationJournal:default')
            // Invoices
            ->addRoute('platby/faktury/<id [0-9]+>/upravit', 'InvoiceList:edit')
            ->addRoute('platby/faktury/<id [0-9]+>', 'InvoiceList:detail')
            ->addRoute('platby/rady/<id [0-9]+>/upravit ? jednotka=<unitId>', 'InvoiceSequence:edit')
            ->addRoute('platby/rady/nova ? jednotka=<unitId>', 'InvoiceSequence:default')
            ->addRoute('platby/rady/<invoiceSequenceId [0-9]+>/nova ? jednotka=<unitId>', 'InvoiceList:create')
            ->addRoute('platby/rady/<invoiceSequenceId [0-9]+> ? jednotka=<unitId>', 'InvoiceList:default')
            ->addRoute('platby/rady ? jednotka=<unitId>', 'InvoiceSequenceList:default')
            ->addRoute('platby/faktury ? jednotka=<unitId>', 'InvoiceList:default');
    }

    private function addAdminRoutes(RouteList $router): void
    {
        $router->add($admin = new RouteList('Admin'));
        $admin
            ->addRoute('admin', 'Default:default')
            ->withPath('admin')
            ->addRoute('hlaseni-chyb/<id [0-9]+>', 'BugReports:detail')
            ->addRoute('hlaseni-chyb', 'BugReports:default')
            ->addRoute('uzivatele', 'Users:default')
            ->addRoute('statistiky ? jednotka=<unitId>', 'Statistics:default')
            ->addRoute('<presenter>[/<action>]', ['action' => 'default']);
    }

    private function addSettingsRoutes(RouteList $router): void
    {
        $router->add($settings = new RouteList('Settings'));
        $settings
            ->addRoute(
                'nastaveni/bankovni-ucty/<id [0-9]+>[/<action>] ? jednotka=<unitId>',
                [
                    'presenter' => 'BankAccounts',
                    'action' => [
                        Route::VALUE => 'detail',
                        Route::FILTER_TABLE => ['upravit' => 'edit'],
                    ],
                ],
            )
            ->addRoute(
                'nastaveni/bankovni-ucty[/<action>] ? jednotka=<unitId>',
                [
                    'presenter' => 'BankAccounts',
                    'action' => [
                        Route::VALUE => 'default',
                        Route::FILTER_TABLE => ['novy' => 'new'],
                    ],
                ],
            )
            ->addRoute('nastaveni/uzivatel ? jednotka=<unitId>', 'User:default')
            ->addRoute('nastaveni/emaily ? jednotka=<unitId>', 'Mails:default')
            ->addRoute('nastaveni/faktury ? jednotka=<unitId> & rok=<year>', 'Invoices:default')
            ->addRoute('nastaveni/automatizace ? jednotka=<unitId>', 'Automation:default')
            ->addRoute('nastaveni ? jednotka=<unitId>', 'Default:default');
    }
}
