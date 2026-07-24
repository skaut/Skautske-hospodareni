<?php

declare(strict_types=1);

namespace App\Model\Infrastructure;

use App\Model\Infrastructure\DoctrineNullableEmbeddables\Subscriber;
use Contributte\Psr6\ICachePoolFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\Setup;
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

        $configuration->setAutoGenerateProxyClasses(
            $this->debugMode ? ProxyFactory::AUTOGENERATE_ALWAYS : ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS,
        );

        // Celá doména App\Model\* je mapovaná PHP atributy (#[ORM\...]).
        $attributeDriver = new AttributeDriver([__DIR__.'/../']);   // = %appDir%/Model/
        $configuration->setMetadataDriverImpl($attributeDriver);

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

        // Čistí prázdné nullable embeddables po načtení (čte #[Nullable] reflexí).
        $em->getEventManager()->addEventSubscriber(new Subscriber());

        return $em;
    }

    private function cache(string $name): CacheItemPoolInterface
    {
        // vytvoří PSR-6 pool (Filesystem/Redis/… podle implementace ICachePoolFactory)
        return $this->cachePoolFactory->create('doctrine.'.$name);
    }
}
