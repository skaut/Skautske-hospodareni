<?php

declare(strict_types=1);

namespace Model\Infrastructure;

use Consistence\Doctrine\Enum\EnumPostLoadEntityListener;
use Contributte\Psr6\ICachePoolFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
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
use Model\Infrastructure\DoctrineNullableEmbeddables\Subscriber;
use Psr\Cache\CacheItemPoolInterface;

use function class_exists;

use const CASE_LOWER;

// BC wrapper pro lib očekávající Doctrine Cache
// dostupné v novějších ORM

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

        // jednotná defaultní cache pro ORMSetup factory – klidně použij "metadata" pool
        $defaultCache = $this->cache('metadata'); // CacheItemPoolInterface

        $configuration = class_exists(ORMSetup::class)
            // ✅ tímhle obejdeš Redis/Memcached autodetekci uvnitř ORMSetup
            ? ORMSetup::createConfiguration($this->debugMode, $proxyDir, $defaultCache)
            : Setup::createConfiguration($this->debugMode, $proxyDir);

        // klidně ponech separátní pooly pro jemnější řízení
        $configuration->setMetadataCache($this->cache('metadata'));
        $configuration->setQueryCache($this->cache('query'));
        // DBAL result cache
        $this->connection->getConfiguration()->setResultCache($this->cache('result'));

        $annotationsReader = $this->annotationsReader();
        $configuration->setMetadataDriverImpl(
            new AnnotationDriver($annotationsReader, [__DIR__ . '/../']),
        );

        $configuration->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER, true));
        //$configuration->addCustomStringFunction('field', Field::class);
        $configuration->addCustomStringFunction('field', Dql\FieldFunction::class);
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
