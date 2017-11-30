<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

abstract class IntegrationTest extends Codeception\Test\Unit
{

    /** @var IntegrationTester */
    protected $tester;

    /** @var \Doctrine\ORM\Mapping\ClassMetadata[] */
    private $metadata;

    /** @var EntityManager */
    protected $entityManager;

    /** @var SchemaTool */
    private $schemaTool;

    protected function getTestedEntites(): array
    {
        return [];
    }

    protected function _before()
    {
        $this->entityManager = $this->tester->grabService(EntityManager::class);
        $this->metadata = array_map([$this->entityManager, 'getClassMetadata'], $this->getTestedEntites());
        $this->schemaTool = new SchemaTool($this->entityManager);
        $this->schemaTool->dropSchema($this->metadata);
        $this->schemaTool->createSchema($this->metadata);
    }

    protected function _after()
    {
        $this->schemaTool->dropSchema($this->metadata);
    }

}
