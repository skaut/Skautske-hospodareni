<?php

declare(strict_types=1);

namespace Model\Infrastructure;

use Consistence\Doctrine\Enum\EnumPostLoadEntityListener;
use Contributte\Psr6\ICachePoolFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;
use DoctrineExtensions\Query\Mysql\Field;
use Fmasa\DoctrineNullableEmbeddables\Subscriber;

use const CASE_LOWER;

final class EntityManagerFactory
{
    public function __construct(private bool $debugMode, private string $tempDir, private Connection $connection, private ICachePoolFactory $cachePoolFactory)
    {
    }

    public function create(): EntityManager
    {
        $configuration = Setup::createConfiguration(
            $this->debugMode,
            $this->tempDir . '/doctrine/proxies',
            $this->cache('cache'),
        );

        $annotationsReader = $this->annotationsReader();

        $configuration->setMetadataDriverImpl(new AnnotationDriver($annotationsReader, [__DIR__ . '/../']));
        $configuration->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER, true));
        $configuration->addCustomStringFunction('field', Field::class);
        $configuration->setSecondLevelCacheEnabled(true);

        $cacheConfiguration = new CacheConfiguration();
        $cacheConfiguration->setCacheFactory(
            new DefaultCacheFactory(new RegionsConfiguration(), $this->cachePoolFactory->create('secondLevelCache')),
        );
        $configuration->setSecondLevelCacheConfiguration($cacheConfiguration);

        $entityManager = EntityManager::create($this->connection, $configuration);

        $eventManager = $entityManager->getEventManager();
        $eventManager->addEventSubscriber(new Subscriber($annotationsReader));
        $eventManager->addEventListener(
            Events::postLoad,
            new EnumPostLoadEntityListener($annotationsReader, $this->cache('enums')),
        );

        return $entityManager;
    }

    private function annotationsReader(): CachedReader
    {
        AnnotationRegistry::registerUniqueLoader('class_exists');

        return new CachedReader(new AnnotationReader(), $this->cache('annotations'), $this->debugMode);
    }

    private function cache(string $name): FilesystemCache
    {
        return new FilesystemCache($this->tempDir . '/cache/doctrine/' . $name);
    }
}
