<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

abstract class IntegrationTest extends Codeception\Test\Unit
{

    /** @var IntegrationTester */
    protected $tester;

    /** @var \Doctrine\ORM\Mapping\ClassMetadata[] */
    private $metadata;

    /** @var SchemaTool */
    private $schemaTool;

    protected function getTestedEntites(): array
    {
        return [];
    }

    protected function _before()
    {
        $em = $this->tester->grabService(EntityManager::class);
        $this->metadata = array_map([$em, 'getClassMetadata'], $this->getTestedEntites());
        $this->schemaTool = new SchemaTool($em);
        $this->schemaTool->dropSchema($this->metadata);
        $this->schemaTool->createSchema($this->metadata);
    }

    protected function _after()
    {
        $this->schemaTool->dropSchema($this->metadata);
    }

}
