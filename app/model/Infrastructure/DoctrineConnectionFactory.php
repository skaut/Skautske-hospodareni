<?php

namespace Model\Infrastructure;

use Dibi\Connection as Dibi;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class DoctrineConnectionFactory
{

    public static function create(Dibi $dibi) : Connection
    {
        $config = $dibi->getConfig();
        return DriverManager::getConnection([
            'host' => $config['host'],
            'dbname' => $config['database'],
            'user' => $config['username'],
            'password' => $config['password'],
            'driver' => $config['driver'],
        ]);
    }

}
