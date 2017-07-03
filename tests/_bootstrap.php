<?php

require_once __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Europe/Prague');

(new \Nette\Loaders\RobotLoader())
    ->addDirectory(__DIR__ . '/../app')
    ->addDirectory(__DIR__)
    ->setCacheStorage(new \Nette\Caching\Storages\FileStorage(__DIR__ . '/_temp'))
    ->register();
