<?php

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();

class_alias('Tester\Assert', 'Assert');
date_default_timezone_set('Europe/Prague');

define('TEMP_DIR', __DIR__.'/tmp/'.getmypid());

Tester\Helpers::purge(TEMP_DIR);

$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__  . '/../app');
$loader->setCacheStorage(new Nette\Caching\Storages\FileStorage(TEMP_DIR));
$loader->register();

