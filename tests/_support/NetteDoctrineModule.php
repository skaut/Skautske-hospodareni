<?php

use Doctrine\ORM\EntityManager;
use Arachne\Codeception\Module\NetteDIModule;

class NetteDoctrineModule extends \Codeception\Module implements \Codeception\Lib\Interfaces\DoctrineProvider
{

    public function _getEntityManager()
    {
        /* @var $container \Nette\DI\Container */
        $container = $this->getModule(NetteDIModule::class)->getContainer();
        return $container->getByType(EntityManager::class, FALSE);
    }

}
