<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repository;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use App\Model\BugReport\Entity\TechnicalErrorReportReply;
use App\Model\BugReport\Repository\TechnicalErrorReportRepository;
use DateTimeImmutable;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr;
use IntegrationTest;

use function array_column;
use function iterator_to_array;
use function sort;

/**
 * Kontraktní test společného předka všech 13 repozitářů.
 *
 * Testuje se přes konkrétní TechnicalErrorReportRepository, protože AbstractRepository je abstraktní.
 * Řada těchto metod se dnes v aplikaci nikde nevolá (viz poznámka v plánu), ale stojí na interních
 * API Doctrine (`getEntityPersister()`, `expandParameters()`, `newHydrator()`, `getSingleColumnResult()`),
 * které se mezi verzemi ORM mění — tichá regrese při dalším upgradu je tu reálná.
 */
final class AbstractRepositoryTest extends IntegrationTest
{
    private TechnicalErrorReportRepository $repository;

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [TechnicalErrorReport::class, TechnicalErrorReportReply::class];
    }

    protected function _before(): void
    {
        parent::_before();

        $this->repository = new TechnicalErrorReportRepository($this->tester->grabService(EntityManager::class));
    }

    public function testFindOrFailReturnsEntityOrThrows(): void
    {
        $id = $this->createReport('První')->getId();

        self::assertSame('První', $this->repository->findOrFail($id)->getDescription());

        $this->expectException(NoResultException::class);
        $this->repository->findOrFail($id + 100);
    }

    public function testFindOneByOrFailUsesCriteriaAndOrdering(): void
    {
        $this->createReport('První', unitId: 7);
        $this->createReport('Druhý', unitId: 7);

        self::assertSame('Druhý', $this->repository->findOneByOrFail(['unitId' => 7], ['id' => 'DESC'])->getDescription());

        $this->expectException(NoResultException::class);
        $this->repository->findOneByOrFail(['unitId' => 99]);
    }

    public function testGetReferenceOrFailReturnsProxyWithoutQuery(): void
    {
        $id = $this->createReport('První')->getId();
        $this->tester->grabService(EntityManager::class)->clear();

        $reference = $this->repository->getReferenceOrFail($id);

        self::assertSame($id, $reference->getId());
        self::assertSame('První', $reference->getDescription(), 'proxy se dohydratuje při prvním čtení');
    }

    public function testEagerLoadJoinsAssociationsAndSupportsArrayHydration(): void
    {
        $report = $this->createReport('S odpověďmi');
        $report->markReplySent('první odpověď');
        $report->markReplySent('druhá odpověď');
        // Nové odpovědi se do DB dostanou až cascade persist, tedy přes persist() rodiče
        // (stejně jako to dělá AbstractManager::saveEntity()) — samotný flush() na ně nestačí.
        $this->save($report);
        $id = $report->getId();
        $this->tester->grabService(EntityManager::class)->clear();

        self::assertCount(2, $this->repository->findOrFailWithEagerLoad($id, ['replies' => []])->getReplies());
        self::assertSame('S odpověďmi', $this->repository->findOrFailWithEagerLoad($id, [])->getDescription(), 'bez asociací spadne na findOrFail');

        $array = $this->repository->findOrFailWithEagerLoad($id, ['replies' => []], AbstractQuery::HYDRATE_ARRAY);
        self::assertSame('S odpověďmi', $array['description']);
        self::assertCount(2, $array['replies']);
    }

    public function testCountingWithAndWithoutCriteria(): void
    {
        self::assertSame(0, $this->repository->getCount());

        $this->createReport('První', unitId: 7);
        $this->createReport('Druhý', unitId: 7);
        $this->createReport('Třetí', unitId: 8);

        self::assertSame(3, $this->repository->getCount());
        self::assertSame(2, $this->repository->getCountBy('entity.unitId = :unitId', ['unitId' => 7]));
        self::assertSame(
            1,
            $this->repository->getCountBy(
                static fn (Expr $expr) => $expr->eq('report.unitId', ':unitId'),
                ['unitId' => 8],
                'report',
            ),
            'kritérium jde předat i callbackem nad Expr',
        );
    }

    public function testQueryByBuildsReusableQueryBuilder(): void
    {
        $this->createReport('První', unitId: 7);
        $this->createReport('Druhý', unitId: 8);

        $reports = $this->repository
            ->getQueryBy('entity.unitId = :unitId', ['unitId' => 8])
            ->getQuery()
            ->getResult();

        self::assertCount(1, $reports);
        self::assertSame('Druhý', $reports[0]->getDescription());
    }

    public function testIterableAndScalarReadsReturnTheSameRows(): void
    {
        $this->createReport('První');
        $this->createReport('Druhý');

        self::assertCount(2, iterator_to_array($this->repository->findAllIterable()));

        $descriptions = array_column($this->repository->findAllScalar('entity.description'), 'description');
        sort($descriptions);
        self::assertSame(['Druhý', 'První'], $descriptions);
    }

    public function testFindByIterableFiltersOrdersAndPaginates(): void
    {
        $this->createReport('První', unitId: 7);
        $this->createReport('Druhý', unitId: 7);
        $this->createReport('Třetí', unitId: 8);

        $found = iterator_to_array($this->repository->findByIterable(['unitId' => 7], ['id' => 'DESC']));
        self::assertCount(2, $found);
        self::assertSame('Druhý', $found[0]->getDescription());

        $limited = iterator_to_array($this->repository->findByIterable(['unitId' => 7], ['id' => 'ASC'], 1, 1));
        self::assertCount(1, $limited);
        self::assertSame('Druhý', $limited[0]->getDescription());
    }

    public function testSingleColumnUniqueValuesGroupsDuplicates(): void
    {
        $this->createReport('První', unitId: 7);
        $this->createReport('Druhý', unitId: 7);
        $this->createReport('Třetí', unitId: 8);

        $unitIds = $this->repository->getSingleColumnUniqueValues('unitId');
        sort($unitIds);

        self::assertSame([7, 8], $unitIds);
    }

    public function testAndWhereDeletedWithNullDoesNotTouchTheQuery(): void
    {
        // Žádná entita v projektu dnes SoftDeletableEntityInterface neimplementuje, takže se dá
        // ověřit jen to, že s $deleted = null helper dotaz nechá být.
        $builder = $this->repository->createQueryBuilder('entity');
        $dql = $builder->getDQL();

        $this->repository->andWhereDeleted($builder, null);

        self::assertSame($dql, $builder->getDQL());
    }

    private function createReport(string $description, ?int $unitId = 23378): TechnicalErrorReport
    {
        $report = new TechnicalErrorReport(
            $description,
            'https://moje-hospodareni.cz/ucetnictvi',
            1882,
            'Jana Kvapilová',
            'jana@example.test',
            117123,
            'Středisko: hospodář',
            $unitId,
            'středisko Pozořice',
            '127.0.0.1',
            'Test browser',
            'test-release',
            [],
            new DateTimeImmutable('2026-07-15 10:00:00'),
        );

        $entityManager = $this->tester->grabService(EntityManager::class);
        $entityManager->persist($report);
        $entityManager->flush();

        return $report;
    }

    private function save(TechnicalErrorReport $report): void
    {
        $entityManager = $this->tester->grabService(EntityManager::class);
        $entityManager->persist($report);
        $entityManager->flush();
    }
}
