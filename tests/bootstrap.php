<?php

require_once __DIR__.'/../vendor/autoload.php';

date_default_timezone_set('Europe/Prague');

(new \Nette\Loaders\RobotLoader())
	->addDirectory(__DIR__.'/../app')
	->setCacheStorage(
		new \Nette\Caching\Storages\FileStorage(__DIR__.'/temp'),
		new \Nette\Caching\Storages\FileJournal(__DIR__.'/temp'))
	->register();

Tester\Environment::setup();