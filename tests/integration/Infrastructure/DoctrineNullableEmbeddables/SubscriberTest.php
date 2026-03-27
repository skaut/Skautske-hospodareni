<?php

declare(strict_types=1);

namespace Infrastructure\DoctrineNullableEmbeddables;

use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Infrastructure\DoctrineNullableEmbeddables\Subscriber;
use Doctrine\Common\Annotations\Reader;
use IntegrationTest;
use ReflectionClass;
use ReflectionMethod;

final class SubscriberTest extends IntegrationTest
{
    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();
    }

    public function testEmptyEmbeddableCheckIgnoresUninitializedTypedProperties(): void
    {
        $subscriber = new Subscriber($this->createMock(Reader::class));
        $accountNumber = (new ReflectionClass(AccountNumber::class))->newInstanceWithoutConstructor();
        $metadata = $this->entityManager->getClassMetadata(AccountNumber::class);

        $isEmpty = new ReflectionMethod(Subscriber::class, 'isEmpty');
        $isEmpty->setAccessible(true);

        self::assertTrue($isEmpty->invoke($subscriber, $accountNumber, $metadata));
    }
}
