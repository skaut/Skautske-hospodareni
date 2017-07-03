<?php

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\OutputWriter;

abstract class IntegrationTest extends Codeception\Test\Unit
{

    /** @var IntegrationTester */
    protected $tester;


    protected function _before()
    {
        $this->clearTables();
        $config = $this->tester->grabService(Configuration::class);
        $config->setOutputWriter(new OutputWriter());
        $migration = new Migration($config);
        $migration->migrate();
    }

    private function clearTables(): void
    {
        $connection = $this->tester->grabService(Connection::class);

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');

        $schema = $connection->getSchemaManager();
        foreach ($schema->listTableNames() as $table) {
            $connection->executeQuery('DROP TABLE ' . $table);
        }

        foreach($schema->listViews() as $view) {
            $connection->executeQuery('DROP VIEW ' . $view->getName());
        }

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
    }

}
