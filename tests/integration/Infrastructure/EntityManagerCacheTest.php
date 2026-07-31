<?php

declare(strict_types=1);

namespace Infrastructure;

use App\Model\Cashbook\Category;
use IntegrationTest;

/**
 * Second-level cache musí zůstat vypnutá, dokud se používají consistence enumy —
 * viz {@see \App\Model\Cashbook\OperationSerializationTest} a
 * {@see \App\Model\Infrastructure\EntityManagerFactory::create()}.
 */
final class EntityManagerCacheTest extends IntegrationTest
{
    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();
    }

    public function testSecondLevelCacheIsDisabled(): void
    {
        self::assertFalse($this->entityManager->getConfiguration()->isSecondLevelCacheEnabled());
    }

    public function testCachedEntityIsNotConfiguredForCaching(): void
    {
        self::assertNull($this->entityManager->getClassMetadata(Category::class)->cache);
    }

    public function testMetadataQueryAndResultCachesAreConfigured(): void
    {
        $configuration = $this->entityManager->getConfiguration();

        self::assertNotNull($configuration->getMetadataCache());
        self::assertNotNull($configuration->getQueryCache());
        self::assertNotNull($this->entityManager->getConnection()->getConfiguration()->getResultCache());
    }
}
