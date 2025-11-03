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
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
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
        $proxyDir = $this->tempDir.'/doctrine/proxies';

        // jednotná defaultní cache pro ORMSetup factory – klidně použij "metadata" pool
        $defaultCache = $this->cache('metadata'); // CacheItemPoolInterface

        // neutrální konfigurace; driver nastavíme ručně (chain)
        $configuration = class_exists(ORMSetup::class)
            // ✅ tímhle obejdeš Redis/Memcached autodetekci uvnitř ORMSetup
            ? ORMSetup::createConfiguration($this->debugMode, $proxyDir, $defaultCache)
            : Setup::createConfiguration($this->debugMode, $proxyDir);

        // --- DRIVER CHAIN: Attributes pro Model\*, Annotations pro Model\Legacy\* ---
        $driverChain = new MappingDriverChain();

        // Attributes – hlavní doména
        $attributesPaths = [__DIR__.'/../../'];          // = %appDir%/Entity
        $attributeDriver = new AttributeDriver($attributesPaths);
        $driverChain->addDriver($attributeDriver, 'Entity');

        // Annotations – legacy podsložka
        $annotationsReader = $this->annotationsReader();
        $annotationsPaths = [__DIR__.'/../'];  // = %appDir%/model/
        $annotationDriver = new AnnotationDriver($annotationsReader, $annotationsPaths);
        $driverChain->addDriver($annotationDriver, 'Model');

        $configuration->setMetadataDriverImpl($driverChain);

        // Cache
        $configuration->setMetadataCache($this->cache('metadata'));
        $configuration->setQueryCache($this->cache('query'));
        // DBAL result cache
        $this->connection->getConfiguration()->setResultCache($this->cache('result'));

        // Naming, DQL
        $configuration->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER, true));
        // $configuration->addCustomStringFunction('field', Field::class);
        $configuration->addCustomStringFunction('field', Dql\FieldFunction::class);

        // 2nd level cache
        $configuration->setSecondLevelCacheEnabled(true);

        $cacheConfiguration = new CacheConfiguration();
        $cacheConfiguration->setCacheFactory(
            new DefaultCacheFactory(new RegionsConfiguration(), $this->cache('secondLevel')),
        );
        $configuration->setSecondLevelCacheConfiguration($cacheConfiguration);

        $em = EntityManager::create($this->connection, $configuration);

        // Eventy závislé na anotacích (zůstávají jen kvůli legacy)
        $evm = $em->getEventManager();
        $evm->addEventSubscriber(new Subscriber($annotationsReader));
        $evm->addEventListener(
            Events::postLoad,
            new EnumPostLoadEntityListener(
                $annotationsReader,
                DoctrineProvider::wrap($this->cache('enums')),
            ),
        );

        return $em;
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
        return $this->cachePoolFactory->create('doctrine.'.$name);
    }
}
