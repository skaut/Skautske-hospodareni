<?php

declare(strict_types=1);

namespace Console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function getenv;

class MigrationsDropCommand extends Command
{
    protected static $defaultName = 'migrations:drop-all-tables-views';

    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::$defaultName)
            ->setDescription('Drops all tables from the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (getenv('DB_TEST') !== 'true') {
            $output->writeln('Cannot run on non testing environment');

            return Command::FAILURE;
        }

        $conn          = $this->em->getConnection();
        $schemaManager = $conn->getSchemaManager();

        // Drop tables
        $tables = $schemaManager->listTables();
        $conn->executeStatement('SET foreign_key_checks = 0');
        foreach ($tables as $table) {
            $tableName = $table->getName();
            $conn->executeStatement("DROP TABLE $tableName");
            $output->writeln("Dropped table $tableName");
        }

        // Drop views
        $views = $schemaManager->listViews();
        foreach ($views as $view) {
            $viewName = $view->getName();
            $conn->executeStatement("DROP VIEW $viewName");
            $output->writeln("Dropped view $viewName");
        }

        return Command::SUCCESS;
    }
}
