<?php

// src/Infrastructure/Doctrine/NetteManagerRegistry.php

declare(strict_types=1);

namespace Infrastructure\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\Proxy;

final class NetteManagerRegistry extends AbstractManagerRegistry
{
    /** @var array<string, object> */
    private array $services = [];

    public function __construct(EntityManagerInterface $em, Connection $connection)
    {
        parent::__construct(
            'orm',
            ['default' => 'dbal.connection'],          // connection name => service id
            ['default' => 'doctrine.entityManager'],   // manager name => service id
            'default',
            'default',
            Proxy::class,
        );

        $this->services['dbal.connection'] = $connection;
        $this->services['doctrine.entityManager'] = $em;
    }

    protected function getService($name)
    {
        return $this->services[$name] ?? null;
    }

    protected function resetService($name): void
    {
        if (
            $name !== 'doctrine.entityManager'
            || ! isset($this->services[$name])
            || ! ($this->services[$name] instanceof EntityManagerInterface)
        ) {
            return;
        }

        $this->services[$name]->clear();
    }
}
