<?php

declare(strict_types=1);

namespace App\Model\Infrastructure;

use App\Model\Infrastructure\DoctrineNullableEmbeddables\Subscriber;
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
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
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

        // --- DRIVER CHAIN: Attributes pro Model\*, Annotations pro Model\Legacy\* ---
        $driverChain = new MappingDriverChain();

        // Attributes – hlavní doména
        $attributesPaths = [__DIR__.'/../'];              // = %appDir%/Model/
        $attributeDriver = new AttributeDriver($attributesPaths);
        $driverChain->addDriver($attributeDriver, 'Entity');
        $driverChain->addDriver($attributeDriver, 'App\Model\Infrastructure\Entity');
        $driverChain->addDriver($attributeDriver, 'App\Model\User\Entity');
        $driverChain->addDriver($attributeDriver, 'App\Model\BugReport\Entity');
        $driverChain->addDriver($attributeDriver, 'App\Model\Invoice\Entity');
        $driverChain->addDriver($attributeDriver, 'App\Model\Invoice\Embeddable');
        $driverChain->addDriver($attributeDriver, 'App\Model\Bank\Entity');
        $driverChain->addDriver($attributeDriver, 'App\Model\Google\Entity');
        $driverChain->addDriver($attributeDriver, 'App\Model\Common\Embeddable');

        // Annotations – legacy podsložka
        $annotationsReader = $this->annotationsReader();
        $annotationsPaths = [__DIR__.'/../', __DIR__.'/../../Model/'];  // = %appDir%/model/ + %appDir%/Model/
        $annotationDriver = new AnnotationDriver($annotationsReader, $annotationsPaths);
        $driverChain->addDriver($annotationDriver, 'Model');
        $driverChain->addDriver($annotationDriver, 'App\Model\Travel');
        $driverChain->addDriver($annotationDriver, 'App\Model\Google');
        $driverChain->addDriver($annotationDriver, 'App\Model\Budget');
        $driverChain->addDriver($annotationDriver, 'App\Model\Cashbook');
        $driverChain->addDriver($annotationDriver, 'App\Model\Chit');
        $driverChain->addDriver($annotationDriver, 'App\Model\Event');
        $driverChain->addDriver($annotationDriver, 'App\Model\Grant');
        $driverChain->addDriver($annotationDriver, 'App\Model\Logger');
        $driverChain->addDriver($annotationDriver, 'App\Model\Participant');
        $driverChain->addDriver($annotationDriver, 'App\Model\Payment');
        $driverChain->addDriver($annotationDriver, 'App\Model\Stat');
        $driverChain->addDriver($annotationDriver, 'App\Model\Unit');
        $driverChain->addDriver($annotationDriver, 'App\Model\Common');

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
