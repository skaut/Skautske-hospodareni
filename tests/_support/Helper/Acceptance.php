<?php

declare(strict_types=1);

namespace Helper;

use Codeception\Module;
use Codeception\Module\Db;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends Module
{
    /**
     * Delete rows from a database table matching the given criteria.
     *
     * @param string               $table    Table name
     * @param array<string, mixed> $criteria Column-value pairs to match
     */
    public function deleteFromDatabase(string $table, array $criteria): void
    {
        /** @var Db $db */
        $db = $this->getModule('Db');
        $db->driver->deleteQueryByCriteria($table, $criteria);
    }
}
