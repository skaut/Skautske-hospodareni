<?php

declare(strict_types=1);

namespace Helper;

use Codeception\Module;
use PDO;
use PDOException;
use function sleep;

/**
 * Waits until database is ready
 */
final class WaitForDb extends Module
{
    public function _initialize() : void
    {
        $db = $this->getModule('Db');

        $retriesLeft = 5;

        while (true) {
            try {
                new PDO($db->_getConfig('dsn'), $db->_getConfig('user'), $db->_getConfig('password'));
                break;
            } catch (PDOException $e) {
                if ($retriesLeft === 0 || $e->getCode() !== 2002) {
                    throw $e;
                }

                $retriesLeft--;

                $this->debug('Connection to database refused, will retry in 5 seconds');
                sleep(5);
            }
        }
    }
}
