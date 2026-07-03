<?php

declare(strict_types=1);

use Codeception\Exception\ModuleException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;

require_once __DIR__.'/../env-bootstrap.php';

abstract class IntegrationTest extends Codeception\Test\Unit
{
    /** @var IntegrationTester */
    protected $tester;

    /** @var ClassMetadata[] */
    private $metadata;

    /** @var EntityManager */
    protected $entityManager;

    /** @var SchemaTool */
    private $schemaTool;

    /**
     * @return string[] FQCN of aggregate roots
     */
    protected function getTestedAggregateRoots(): array
    {
        return [];
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function _setUp()
    {
        /** @var Contributte\Codeception\Module\NetteDIModule $module */
        $module = $this->getModule('Contributte\Codeception\Module\NetteDIModule');
        $module->onCreateConfigurator[] = function (Nette\Bootstrap\Configurator $configurator) {
            $configurator->addStaticParameters(['envConfig' => loadTestEnvironmentConfiguration()]);
        };
        parent::_setUp();
    }

    protected function _before(): void
    {
        $this->entityManager = $this->tester->grabService(EntityManager::class);
        $this->metadata = array_map([$this->entityManager, 'getClassMetadata'], $this->getTestedEntities());
        $this->schemaTool = new SchemaTool($this->entityManager);
        // pro MySQL jistota kvůli FK
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $this->clearDatabase();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        $this->schemaTool->createSchema($this->metadata);
    }

    protected function _after(): void
    {
        try {
            $conn = $this->entityManager->getConnection();
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            $this->clearDatabase();
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        } finally {
            $this->entityManager->clear();
            $this->entityManager->getConnection()->close();
        }
    }

    /**
     * Returns FQCN of entities used in test case.
     * Database schema is generated from mapping of these entities.
     *
     * @return string[]
     */
    private function getTestedEntities(): array
    {
        $entityClasses = [];
        $classesToAnalyze = array_fill_keys($this->getTestedAggregateRoots(), true);

        while ($classesToAnalyze !== []) {
            $analyzedClass = array_keys($classesToAnalyze)[0];
            $metadata = $this->entityManager->getClassMetadata($analyzedClass);

            if ($metadata->getReflectionClass()->isAbstract()) {
                foreach ($this->getChildEntityClasses($analyzedClass) as $childEntityClass) {
                    if (isset($entityClasses[$childEntityClass]) || isset($classesToAnalyze[$childEntityClass])) {
                        continue;
                    }

                    $classesToAnalyze[$childEntityClass] = true;
                }
            }

            foreach ($metadata->getAssociationNames() as $associationName) {
                $targetClass = $metadata->getAssociationTargetClass($associationName);

                if (isset($entityClasses[$targetClass]) || isset($classesToAnalyze[$targetClass])) {
                    continue;
                }

                $classesToAnalyze[$targetClass] = true;
            }

            $entityClasses[$analyzedClass] = true;
            unset($classesToAnalyze[$analyzedClass]);
        }

        return array_keys($entityClasses);
    }

    /**
     * @return string[]
     */
    private function getChildEntityClasses(string $parentEntityClass): array
    {
        $childEntities = [];

        foreach ($this->entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            if ($metadata->getReflectionClass()->isSubclassOf($parentEntityClass)) {
                $childEntities[] = $metadata->getName();
            }
        }

        return $childEntities;
    }

    private function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();

        foreach ($schemaManager->listViews() as $view) {
            $connection->executeStatement('DROP VIEW IF EXISTS '.$connection->quoteIdentifier($view->getName()));
        }

        foreach ($schemaManager->listTableNames() as $tableName) {
            $connection->executeStatement('DROP TABLE IF EXISTS '.$connection->quoteIdentifier($tableName));
        }
    }
}
