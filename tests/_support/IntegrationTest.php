<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;

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
    protected function getTestedAggregateRoots() : array
    {
        return [];
    }

    protected function _before() : void
    {
        $this->entityManager = $this->tester->grabService(EntityManager::class);
        $this->metadata      = array_map([$this->entityManager, 'getClassMetadata'], $this->getTestedEntities());
        $this->schemaTool    = new SchemaTool($this->entityManager);
        $this->schemaTool->dropSchema($this->metadata);
        $this->schemaTool->createSchema($this->metadata);
    }

    protected function _after() : void
    {
        $this->schemaTool->dropSchema($this->metadata);
    }


    /**
     * Returns FQCN of entities used in test case.
     * Database schema is generated from mapping of these entities
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
    private function getChildEntityClasses(string $parentEntityClass) : array
    {
        $childEntities = [];

        foreach ($this->entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            if ($metadata->getReflectionClass()->isSubclassOf($parentEntityClass)) {
                $childEntities[] = $metadata->getName();
            }
        }

        return $childEntities;
    }
}
