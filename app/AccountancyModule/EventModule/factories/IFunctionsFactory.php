<?php

namespace App\AccountancyModule\EventModule\Factories;

use App\AccountancyModule\EventModule\Components\Functions;

interface IFunctionsFactory
{

	/** @return Functions */
	public function create($eventId);

}