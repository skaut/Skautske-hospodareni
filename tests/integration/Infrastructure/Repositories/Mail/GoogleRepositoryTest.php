<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Mail;

use IntegrationTest;
use Model\Common\UnitId;
use Model\Google\Exception\OAuthNotFound;
use Model\Google\OAuth;
use Model\Google\OAuthId;
use function array_map;

final class GoogleRepositoryTest extends IntegrationTest
{
    /**
     * @return string[]
     *
     * @phpstan-return list<String>
     */
    protected function getTestedAggregateRoots() : array
    {
        return [OAuth::class];
    }

    public function testAggregateIsCorrectlySavedAndHydrated() : void
    {
        $repository = $this->repository();

        $aggregate = OAuth::create(new UnitId(123), 'foo', 'test@skaut.cz');

        $repository->save($aggregate);
        $this->entityManager->clear();

        $hydratedAggregate = $repository->find($aggregate->getId());

        self::assertSame($aggregate->getId()->toString(), $hydratedAggregate->getId()->toString());
        self::assertSame(
            $aggregate->getUpdatedAt()->format('Y-m-d H:i:s'),
            $hydratedAggregate->getUpdatedAt()->format('Y-m-d H:i:s'),
        );
        self::assertSame($aggregate->getUnitId()->toInt(), $hydratedAggregate->getUnitId()->toInt());
        self::assertSame($aggregate->getEmail(), $hydratedAggregate->getEmail());
        self::assertSame($aggregate->getToken(), $hydratedAggregate->getToken());
    }

    public function testFindThrowsExceptionIfAggregateDoesNotExist() : void
    {
        $this->expectException(OAuthNotFound::class);

        $this->repository()->find(OAuthId::generate());
    }

    public function testFindByUnitsReturnsCorrectData() : void
    {
        $unitIds = [1, 2, 3];

        $aggregates = [
            OAuth::create(new UnitId(1), 'a', 'mail3@skaut.cz'),
            OAuth::create(new UnitId(2), 'b', 'mail2@skaut.cz'),
            OAuth::create(new UnitId(1), 'c', 'mail1@skaut.cz'),
        ];

        $repository = $this->repository();

        foreach ($aggregates as $aggregate) {
            $repository->save($aggregate);
        }

        $this->entityManager->clear();

        $returnedIds = array_map(
            fn(array $oauths) => array_map(fn(OAuth $oauth) => $oauth->getId()->toString(), $oauths),
            $repository->findByUnits($unitIds)
        );

        self::assertSame(
            [
                1 => [$aggregates[2]->getId()->toString(), $aggregates[0]->getId()->toString()],
                2 => [$aggregates[1]->getId()->toString()],
                3 => [],
            ],
            $returnedIds,
        );
    }

    public function testFindByUnitIdAndEmailThrowsExceptionIfAggregateDoesNotExist() : void
    {
        $unitId = new UnitId(1);
        $email  = 'test@skaut.cz';

        $repository = $this->repository();

        // Just Unit ID matching
        $repository->save(OAuth::create($unitId, 'code', 'different@email.cz'));

        // Just email matching
        $repository->save(OAuth::create(new UnitId(2), 'code', $email));

        $this->entityManager->clear();

        $this->expectException(OAuthNotFound::class);

        $repository->findByUnitAndEmail($unitId, $email);
    }

    public function testFindByUnitIdAndEmailReturnsAggregateIfItExists() : void
    {
        $aggregate = OAuth::create(new UnitId(1), 'code', 'test@skaut.cz');

        $repository = $this->repository();

        $repository->save($aggregate);

        $this->entityManager->clear();

        self::assertSame(
            $aggregate->getId()->toString(),
            $repository->findByUnitAndEmail($aggregate->getUnitId(), $aggregate->getEmail())->getId()->toString(),
        );
    }

    public function testRemoveDeletesAggregateFromDatabase() : void
    {
        $aggregate = OAuth::create(new UnitId(1), 'code', 'test@skaut.cz');

        $repository = $this->repository();

        $repository->save($aggregate);
        $repository->remove($aggregate);

        $this->entityManager->clear();

        $this->expectException(OAuthNotFound::class);

        $repository->find($aggregate->getId());
    }

    private function repository() : GoogleRepository
    {
        return new GoogleRepository($this->entityManager);
    }
}
