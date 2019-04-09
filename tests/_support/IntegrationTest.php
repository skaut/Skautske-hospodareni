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
     * Returns FQCN of entities used in test case.
     * Database schema is generated from mapping of these entities
     *
     * @return string[]
     */
    protected function getTestedEntites() : array
    {
        return [];
    }

    protected function _before() : void
    {
        $this->entityManager = $this->tester->grabService(EntityManager::class);
        $this->metadata      = array_map([$this->entityManager, 'getClassMetadata'], $this->getTestedEntites());
        $this->schemaTool    = new SchemaTool($this->entityManager);
        $this->schemaTool->dropSchema($this->metadata);
        $this->schemaTool->createSchema($this->metadata);
    }

    protected function _after() : void
    {
        $this->schemaTool->dropSchema($this->metadata);
    }
}
