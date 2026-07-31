<?php

declare(strict_types=1);

namespace App\Model\Infrastructure;

use App\Model\Infrastructure\Cache\DoctrineCachePool;
use App\Model\Infrastructure\Cache\DoctrineCachePoolFactory;
use App\Model\Infrastructure\DoctrineNullableEmbeddables\Subscriber;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Proxy\ProxyFactory;
use Psr\Cache\CacheItemPoolInterface;

use const CASE_LOWER;

final class EntityManagerFactory
{
    public function __construct(
        private bool $debugMode,
        private string $tempDir,
        private Connection $connection,
        private DoctrineCachePoolFactory $cachePoolFactory,
    ) {
    }

    public function create(): EntityManager
    {
        $proxyDir = $this->tempDir.'/doctrine/proxies';

        // Neutrální konfigurace s explicitní PSR-6 cache (obchází Redis/Memcached
        // autodetekci uvnitř ORMSetup); metadata driver nastavíme ručně níže.
        $configuration = ORMSetup::createConfiguration($this->debugMode, $proxyDir, $this->cache(DoctrineCachePool::Metadata));

        $configuration->setAutoGenerateProxyClasses(
            $this->debugMode ? ProxyFactory::AUTOGENERATE_ALWAYS : ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS,
        );

        // Celá doména App\Model\* je mapovaná PHP atributy (#[ORM\...]).
        $attributeDriver = new AttributeDriver([__DIR__.'/../']);   // = %appDir%/Model/
        $configuration->setMetadataDriverImpl($attributeDriver);

        // Cache
        $configuration->setMetadataCache($this->cache(DoctrineCachePool::Metadata));
        $configuration->setQueryCache($this->cache(DoctrineCachePool::Query));
        // DBAL result cache
        $this->connection->getConfiguration()->setResultCache($this->cache(DoctrineCachePool::Result));

        // Naming, DQL
        $configuration->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));
        $configuration->addCustomStringFunction('field', Dql\FieldFunction::class);

        // Second-level cache je záměrně vypnutá.
        //
        // Historicky tu zapnutá byla, ale reálně nikdy nic nevrátila: PSR-6 pooly stavěl
        // skaut/psr6-caching, jehož `getItems()` vracel list místo mapy `key => item`, takže
        // `Doctrine\ORM\Cache\Region\DefaultRegion::getMultiple()` (kontrola `isset($items[$key])`)
        // vždycky minul. Cache se tedy jen zapisovala.
        //
        // Jakmile ji obsluhuje funkční pool (symfony/cache), rozbije aplikaci: entity se z cache
        // hydratují unserializací, což u consistence enumů vyrobí novou instanci — a
        // `Consistence\Enum\Enum::equals()` porovnává identitou (`$this === $that`). Filtr
        // v CategoryPairsQueryHandler pak zahodí všechny kategorie a formulář dokladu má prázdné
        // selecty (pokryto v OperationSerializationTest a EntityManagerCacheTest).
        //
        // Zapnout ji lze až po migraci consistence enumů na nativní PHP enumy — jejich case
        // přežije unserializaci jako tatáž instance.
        $configuration->setSecondLevelCacheEnabled(false);

        $em = new EntityManager($this->connection, $configuration);

        // Čistí prázdné nullable embeddables po načtení (čte #[Nullable] reflexí).
        $em->getEventManager()->addEventSubscriber(new Subscriber());

        return $em;
    }

    private function cache(DoctrineCachePool $pool): CacheItemPoolInterface
    {
        return $this->cachePoolFactory->create($pool);
    }
}
