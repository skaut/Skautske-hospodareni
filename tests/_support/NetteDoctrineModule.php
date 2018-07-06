<?php

declare(strict_types=1);

use Arachne\Codeception\Module\NetteDIModule;
use Codeception\Lib\Interfaces\DoctrineProvider;
use Codeception\Module;
use Doctrine\ORM\EntityManager;
use Nette\DI\Container;

class NetteDoctrineModule extends Module implements DoctrineProvider
{
    public function _getEntityManager()
    {
        /** @var Container $container */
        $container = $this->getModule(NetteDIModule::class)->getContainer();
        return $container->getByType(EntityManager::class, false);
    }
}
