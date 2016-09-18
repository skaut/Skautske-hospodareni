<?php

namespace App;

use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\SimpleRouter;

/**
 * Router factory.
 */
class RouterFactory {

	/** @var int */
	private $secured;

	/**
	 * RouterFactory constructor.
	 * @param bool $ssl
	 */
	public function __construct($ssl)
	{
		// Disable https for development
		$this->secured = $ssl ? 0 : $this->secured;
	}

	/**
     * @return \Nette\Application\IRouter
     */
    public function createRouter()
	{
        $router = new RouteList();

        $router[] = new Route('app.manifest', 'Offline:manifest');
        $router[] = new Route('o-projektu', 'Default:about');
        $router[] = new Route('sign/<action>[/back-<backlink>]', [
            'presenter' => 'Auth',
            'action' => 'default',
            'backlink' => NULL,
		], $this->secured);

        $router[] = new Route('prirucka/<action>[#<anchor>]', [
            'presenter' => 'Tutorial',
            'action' => [
                Route::VALUE => 'default',
                Route::FILTER_TABLE => [
                    // řetězec v URL => presenter
                    'vyprava' => 'event',
                    'tabor' => 'camp',
                    'cestovni-prikaz' => 'travelCommand',
                ]],
        ]);

        $router[] = new Route('offline/<action>.html', [
            'presenter' => 'Offline',
            'action' => 'list',
		]);

		$router[] = $accountancy = new RouteList('Accountancy');

		$accountancy[] = $this->createCampRoutes();
		$accountancy[] = $this->createEventRoutes();
		$accountancy[] = $this->createTravelRoutes();
		$accountancy[] = $this->createUnitAccountRoutes();
		$accountancy[] = $this->createPaymentRoutes();

		$accountancy[] = new Route('<module>/<presenter>[/<action>]', ['action' => 'default'], $this->secured);


		$router[] = new SimpleRouter('Default:default', $this->secured);
        return $router;
    }

	/**
	 * @return RouteList
	 */
    private function createCampRoutes()
	{
		$router = new RouteList('Camp');

		$prefix = 'tabory';
		$router[] = new Route($prefix . '/<aid [0-9]+>/[<presenter>][/<action>]', [
			'presenter' => [
//				Route::VALUE => 'Detail', //nefunguje pak report
				Route::FILTER_TABLE => [
					'ucastnici' => 'Participant',
					'kniha' => 'Cashbook',
					'rozpocet' => 'Budget',
				]],
			'action' => 'default',
		], $this->secured);

		$router[] = new Route($prefix . '[/<presenter>][/<action>]', [
			'presenter' => 'Default',
			'action' => 'default',
		], $this->secured);

		return $router;
	}

	/**
	 * @return RouteList
	 */
	private function createEventRoutes()
	{
		$router = new RouteList('Event');

		$prefix = 'akce';

		$router[] = new Route($prefix . '/<aid [0-9]+>/<presenter>[/<action>]', [
			'presenter' => [
				Route::VALUE => 'Event',
				Route::FILTER_TABLE => [
					'ucastnici' => 'Participant',
					'kniha' => 'Cashbook',
				]],
			'action' => 'default',
		], $this->secured);

		$router[] = new Route($prefix . '[<presenter>][/<action>]', [
			'presenter' => 'Default',
			'action' => 'default',
		], $this->secured);

		return $router;
	}

	/**
	 * @return RouteList
	 */
	private function createTravelRoutes()
	{
		$router = new RouteList('Travel');

		$prefix = 'cestaky';

		$router[] = new Route($prefix . '[/<presenter>[/<action>][/<id>]]', [
			'presenter' => array(
				Route::VALUE => 'Default',
				Route::FILTER_TABLE => array(
					'vozidla' => 'Vehicle',
					'smlouvy' => 'Contract',
				)),
			'action' => 'default',
		], $this->secured);

		return $router;
	}

	/**
	 * @return RouteList
	 */
	private function createUnitAccountRoutes()
	{
		$router = new RouteList('UnitAccount');

		$prefix = 'jednotka';

		$router[] = new Route($prefix . '/<aid [0-9]+>[/<presenter>][/<action>][/<year>]', array(
			'presenter' => array(
				Route::VALUE => 'Default',
				Route::FILTER_TABLE => array(
					'kniha' => 'Cashbook',
					'paragony' => 'Chit',
					'rozpocet' => 'Budget',
				)),
			'action' => 'default',
		), $this->secured);

		$router[] = new Route($prefix . '[/<presenter>][/<action>]', [
			'presenter' => 'Default',
			'action' => 'default',
		], $this->secured);

		return $router;
	}

	private function createPaymentRoutes()
	{
		$router = new RouteList('Payment');

		$prefix = 'platby';

		$router[] = new Route($prefix . '/<aid [0-9]+>[/<presenter>][/<action>][/<year>]', 'Default:default');

		$router[] = new Route($prefix . '[/<presenter>][/<action>]', [
			'presenter' => 'Default',
			'action' => 'default',
		]);
		return $router;
	}

}
