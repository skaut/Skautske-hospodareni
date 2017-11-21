<?php

require_once __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Europe/Prague');

(new \Nette\Loaders\RobotLoader())
    ->addDirectory(__DIR__ . '/../app')
    ->addDirectory(__DIR__)
    ->setTempDirectory(__DIR__ . '/_temp')
    ->register();
