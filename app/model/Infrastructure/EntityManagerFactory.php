<?php

declare(strict_types=1);

namespace Model\Infrastructure;

use Consistence\Doctrine\Enum\EnumPostLoadEntityListener;
use Contributte\Psr6\ICachePoolFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
// BC wrapper pro lib očekávající Doctrine Cache
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\Setup;
// dostupné v novějších ORM
use DoctrineExtensions\Query\Mysql\Field;
use Model\Infrastructure\DoctrineNullableEmbeddables\Subscriber;
use Psr\Cache\CacheItemPoolInterface;

use function class_exists;

use const CASE_LOWER;

final class EntityManagerFactory
{
    public function __construct(
        private bool $debugMode,
        private string $tempDir,
        private Connection $connection,
        private ICachePoolFactory $cachePoolFactory,
    ) {
    }

    public function create(): EntityManager
    {
        $proxyDir = $this->tempDir . '/doctrine/proxies';

        // Preferuj ORMSetup, fallback na Tools\Setup pro starší ORM
        $configuration = class_exists(ORMSetup::class)
            ? ORMSetup::createConfiguration($this->debugMode, $proxyDir)
            : Setup::createConfiguration($this->debugMode, $proxyDir);

        // PSR-6 cache (doporučeno od ORM 2.9+, v ORM 3 povinné)
        $configuration->setMetadataCache($this->cache('metadata'));
        $configuration->setQueryCache($this->cache('query'));
        // Result cache patří do DBAL: $this->connection->getConfiguration()->setResultCache($this->cache('result'));

        $annotationsReader = $this->annotationsReader();

        // POZOR: Annotation driver je v ORM 3 deprecated – dlouhodobě zvaž Attributes/XML.
        $configuration->setMetadataDriverImpl(
            new AnnotationDriver($annotationsReader, [__DIR__ . '/../']),
        );

        $configuration->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER, true));
        $configuration->addCustomStringFunction('field', Field::class);
        $configuration->setSecondLevelCacheEnabled(true);

        $cacheConfiguration = new CacheConfiguration();
        $cacheConfiguration->setCacheFactory(
            new DefaultCacheFactory(new RegionsConfiguration(), $this->cache('secondLevel')),
        );
        $configuration->setSecondLevelCacheConfiguration($cacheConfiguration);

        $entityManager = EntityManager::create($this->connection, $configuration);

        $eventManager = $entityManager->getEventManager();
        $eventManager->addEventSubscriber(new Subscriber($annotationsReader));
        // Enum listener typicky očekává Doctrine Cache → zabalíme PSR-6 pool
        $eventManager->addEventListener(
            Events::postLoad,
            new EnumPostLoadEntityListener($annotationsReader, DoctrineProvider::wrap($this->cache('enums'))),
        );

        return $entityManager;
    }

    private function annotationsReader(): Reader
    {
        return new PsrCachedReader(
            new AnnotationReader(),
            $this->cache('annotations'),
            $this->debugMode,
        );
    }

    private function cache(string $name): CacheItemPoolInterface
    {
        // vytvoří PSR-6 pool (Filesystem/Redis/… podle implementace ICachePoolFactory)
        return $this->cachePoolFactory->create('doctrine.' . $name);
    }
}
