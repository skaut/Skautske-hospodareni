<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Manager;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use App\Model\BugReport\Entity\TechnicalErrorReportReply;
use App\Model\BugReport\Repository\TechnicalErrorReportRepository;
use App\Model\Infrastructure\Repository\AbstractRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use IntegrationTest;
use InvalidArgumentException;
use ReflectionProperty;
use RuntimeException;

use function count;

/**
 * Kontraktní test společného předka všech 13 manažerů. Testuje se přes potomka definovaného níž,
 * protože AbstractManager je abstraktní a chráněné metody jinak nejsou dosažitelné.
 */
final class AbstractManagerTest extends IntegrationTest
{
    private AbstractManagerTestManager $manager;

    private TechnicalErrorReportRepository $repository;

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [TechnicalErrorReport::class, TechnicalErrorReportReply::class];
    }

    protected function _before(): void
    {
        parent::_before();

        $this->manager = new AbstractManagerTestManager($this->entityManager);
        $this->repository = new TechnicalErrorReportRepository($this->entityManager);
    }

    public function testSaveAndDeleteEntityGoThroughTheEntityManager(): void
    {
        $report = $this->manager->save($this->createReport('První'));

        self::assertSame(1, $this->repository->getCount());

        $this->manager->delete($report);

        self::assertSame(0, $this->repository->getCount(), 'entita bez soft-delete se maže natvrdo');
    }

    public function testHardDeleteRemovesEntityIncludingItsCascade(): void
    {
        $report = $this->createReport('S odpověďmi');
        $report->markReplySent('odpověď');
        $this->manager->save($report);

        self::assertSame(1, $this->replyCount());

        $this->manager->hardDelete($report);

        self::assertSame(0, $this->repository->getCount());
        self::assertSame(0, $this->replyCount(), 'orphanRemoval smaže i odpovědi');
    }

    public function testRestoreDeletedEntityIgnoresEntitiesWithoutSoftDelete(): void
    {
        $report = $this->manager->save($this->createReport('První'));

        // TechnicalErrorReport neimplementuje SoftDeletableEntityInterface, takže je to no-op.
        $this->manager->restore($report);

        self::assertSame(1, $this->repository->getCount());
    }

    public function testWrapInTransactionReturnsCallbackResultAndRollsBackOnFailure(): void
    {
        $description = $this->manager->wrapInTransaction(function (EntityManagerInterface $entityManager): string {
            $report = $this->createReport('V transakci');
            $entityManager->persist($report);

            return $report->getDescription();
        });

        self::assertSame('V transakci', $description);
        self::assertSame(1, $this->repository->getCount());

        try {
            $this->manager->wrapInTransaction(function (EntityManagerInterface $entityManager): void {
                $entityManager->persist($this->createReport('Nedokončená'));
                $entityManager->flush();

                throw new RuntimeException('něco spadlo');
            });
            self::fail('Výjimka z callbacku musí probublat.');
        } catch (RuntimeException $e) {
            self::assertSame('něco spadlo', $e->getMessage());
        }

        $this->entityManager->clear();
        self::assertSame(1, $this->repository->getCount(), 'neúspěšná transakce se odroluje');
    }

    public function testRefreshDropsInMemoryChanges(): void
    {
        $report = $this->manager->save($this->createReport('První'));

        $this->entityManager->getConnection()->executeStatement(
            'UPDATE technical_error_report SET description = ? WHERE id = ?',
            ['Změněno v DB', $report->getId()],
        );

        $this->manager->refresh($report);

        self::assertSame('Změněno v DB', $report->getDescription());
    }

    public function testLockUsesRequestedLockMode(): void
    {
        $report = $this->manager->save($this->createReport('První'));

        $this->manager->wrapInTransaction(function () use ($report): void {
            $this->manager->lock($report, LockMode::PESSIMISTIC_WRITE);
        });

        self::assertSame(1, $this->repository->getCount());
    }

    public function testEntityManagerStateHelpers(): void
    {
        $report = $this->manager->save($this->createReport('První'));

        self::assertTrue($this->manager->isEntityManagerOpen());
        self::assertFalse($this->manager->clearOrResetEntityManager(), 'otevřený EM se jen vyčistí');
        self::assertFalse($this->entityManager->contains($report), 'clear() odpojí entity');

        $this->manager->clearEntityManager();

        self::assertSame(1, $this->repository->getCount());
    }

    public function testResetIsNotSupported(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Entity manager reset is not supported in this implementation.');

        $this->manager->resetEntityManager();
    }

    public function testInitializeEntityHydratesProxy(): void
    {
        $id = $this->manager->save($this->createReport('První'))->getId();
        $this->entityManager->clear();

        $reference = $this->repository->getReferenceOrFail($id);
        $this->manager->initialize($reference);

        self::assertSame('První', $reference->getDescription());
    }

    public function testGetClassMetadataDescribesEntity(): void
    {
        $metadata = $this->manager->getClassMetadata(TechnicalErrorReport::class);

        self::assertSame('technical_error_report', $metadata->getTableName());
        self::assertSame(['id'], $metadata->getIdentifierFieldNames());
    }

    public function testUpdateCollectionByIdAddsAndRemovesByIdentifier(): void
    {
        $report = $this->createReport('S odpověďmi');
        $first = $report->markReplySent('první');
        $second = $report->markReplySent('druhá');
        $this->manager->save($report);

        $replyRepository = new AbstractManagerTestReplyRepository($this->entityManager);

        // Ponecháme jen první odpověď — druhá z kolekce vypadne.
        $collection = new ArrayCollection($report->getReplies());
        $this->manager->updateCollectionById($report, $collection, $replyRepository, [$first->getId()]);

        self::assertCount(1, $collection);
        $remaining = $collection->first();
        self::assertInstanceOf(TechnicalErrorReportReply::class, $remaining);
        self::assertSame($first->getId(), $remaining->getId());

        // Vrácení podle ID i podle instance dá stejný výsledek.
        $this->manager->updateCollectionById($report, $collection, $replyRepository, [$first->getId(), $second]);

        self::assertCount(2, $collection);
    }

    public function testUpdateCollectionByIdCanIndexCollectionById(): void
    {
        $report = $this->createReport('S odpověďmi');
        $reply = $report->markReplySent('první');
        $this->manager->save($report);

        $collection = new ArrayCollection();
        $this->manager->updateCollectionById(
            $report,
            $collection,
            new AbstractManagerTestReplyRepository($this->entityManager),
            [$reply->getId()],
            indexedById: true,
        );

        self::assertSame($reply, $collection[$reply->getId()]);
    }

    public function testUpdateMultiplierCollectionCreatesEditsAndDeletes(): void
    {
        $report = $this->createReport('S odpověďmi');
        $kept = $report->markReplySent('původní');
        $removed = $report->markReplySent('ke smazání');
        $this->manager->save($report);

        $created = [];
        $edited = [];
        $deleted = [];

        $this->manager->updateMultiplierCollection(
            new ArrayCollection($report->getReplies()),
            [
                ['id' => $kept->getId(), 'message' => 'upravená'],
                ['id' => null, 'message' => 'nová'],
            ],
            static function (array $row) use (&$created, $report): TechnicalErrorReportReply {
                $created[] = $row['message'];

                return new TechnicalErrorReportReply($report, (string) $row['message']);
            },
            static function (TechnicalErrorReportReply $reply, array $row) use (&$edited): TechnicalErrorReportReply {
                $edited[] = $reply->getId().':'.$row['message'];

                return $reply;
            },
            static function (TechnicalErrorReportReply $reply) use (&$deleted): void {
                $deleted[] = $reply->getId();
            },
        );

        self::assertSame(['nová'], $created);
        self::assertSame([$kept->getId().':upravená'], $edited);
        self::assertSame([$removed->getId()], $deleted, 'co nepřišlo v datech, jde do delete callbacku');
    }

    public function testUpdateMultiplierCollectionRejectsUnknownIdentifier(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Entity with identifier '99' does not exist");

        $this->manager->updateMultiplierCollection(
            new ArrayCollection(),
            [['id' => 99]],
            static fn (): never => throw new RuntimeException('create se nemá zavolat'),
            static fn (): never => throw new RuntimeException('edit se nemá zavolat'),
            static fn (): never => throw new RuntimeException('delete se nemá zavolat'),
        );
    }

    public function testUpdateMultiplierCollectionCanCreateMissingEntities(): void
    {
        $created = [];
        $report = $this->createReport('S odpověďmi');

        $this->manager->updateMultiplierCollection(
            new ArrayCollection(),
            ['first' => ['key' => 42, 'name' => 'nové']],
            static function (array $row, string|int $index) use (&$created, $report): TechnicalErrorReportReply {
                $created[] = $index.':'.$row['name'];

                return new TechnicalErrorReportReply($report, (string) $row['name']);
            },
            static fn (TechnicalErrorReportReply $reply): TechnicalErrorReportReply => $reply,
            static function (TechnicalErrorReportReply $reply): void {
            },
            rowIdentifierCallback: static fn (array $row) => $row['key'],
            entityIdentifierCallback: static fn (TechnicalErrorReportReply $reply) => $reply->getId(),
            createWhenMissingInCollection: true,
        );

        self::assertSame(['first:nové'], $created);
    }

    public function testBulkInsertUpdateInsertsRowsAndUpdatesDuplicates(): void
    {
        $rows = [
            $this->reportRow(1, 'První'),
            $this->reportRow(2, 'Druhý'),
        ];

        self::assertSame(0, $this->manager->bulkInsertUpdate('technical_error_report', [], ['description']), 'prázdný vstup nic nedělá');
        self::assertSame(2, $this->manager->bulkInsertUpdate('technical_error_report', $rows, []));
        self::assertSame(2, $this->repository->getCount());

        // Stejné ID + ON DUPLICATE KEY UPDATE přepíše popis.
        $this->manager->bulkInsertUpdate('technical_error_report', [$this->reportRow(1, 'Přepsaný')], ['description']);

        self::assertSame('Přepsaný', $this->repository->findOrFail(1)->getDescription());
        self::assertSame(2, $this->repository->getCount());
    }

    public function testBulkInsertUpdateRejectsRowsWithDifferentColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->manager->bulkInsertUpdate(
            'technical_error_report',
            [
                $this->reportRow(1, 'První'),
                ['id' => 2, 'description' => 'Chybí zbytek sloupců'],
            ],
            [],
        );
    }

    public function testCollectionHelpersRespectInitializationAndDuplicates(): void
    {
        $report = $this->createReport('S odpověďmi');
        $reply = $report->markReplySent('první');
        $this->manager->save($report);

        $collection = new ArrayCollection();

        self::assertTrue($this->manager->isCollectionInitialized($collection), 'ArrayCollection je vždy inicializovaná');
        self::assertTrue($this->manager->addElementIfInitialized($collection, $reply));
        self::assertFalse($this->manager->addElementIfInitialized($collection, $reply), 'stejný prvek se nepřidá dvakrát');
        self::assertTrue($this->manager->removeElementIfInitialized($collection, $reply));
        self::assertFalse($this->manager->removeElementIfInitialized($collection, $reply));

        // Neinicializovaná PersistentCollection se nesmí kvůli helperu načítat z DB.
        $this->entityManager->clear();
        $lazyReplies = $this->readRepliesCollection($this->repository->findOrFail($report->getId()));

        self::assertFalse($this->manager->isCollectionInitialized($lazyReplies));
        self::assertFalse($this->manager->addElementIfInitialized($lazyReplies, $reply));
        self::assertFalse($this->manager->removeElementIfInitialized($lazyReplies, $reply));
    }

    public function testBatchFlushesEveryBatchAndTheRemainder(): void
    {
        $flushes = 0;

        $this->manager->batch(
            [$this->createReport('První'), $this->createReport('Druhý'), $this->createReport('Třetí')],
            2,
            function (TechnicalErrorReport $report, EntityManagerInterface $entityManager): void {
                $entityManager->persist($report);
            },
            static function () use (&$flushes): void {
                ++$flushes;
            },
        );

        self::assertSame(3, $this->repository->getCount());
        self::assertSame(2, $flushes, 'jeden flush po plné dávce, jeden po zbytku');
    }

    public function testBatchWithoutFlushCallbackAndWithZeroBatchSize(): void
    {
        $this->manager->batch(
            [$this->createReport('První')],
            0,
            function (TechnicalErrorReport $report, EntityManagerInterface $entityManager): void {
                $entityManager->persist($report);
            },
        );

        self::assertSame(1, $this->repository->getCount(), 'nulová velikost dávky se srovná na 1');
    }

    /** @return array<string, mixed> */
    private function reportRow(int $id, string $description): array
    {
        return [
            'id' => $id,
            'description' => $description,
            'reporter_user_id' => 1882,
            'reporter_display_name' => 'Jana Kvapilová',
            'app_release' => 'test-release',
            'diagnostics' => '[]',
            'created_at' => '2026-07-15 10:00:00',
        ];
    }

    private function createReport(string $description): TechnicalErrorReport
    {
        return new TechnicalErrorReport(
            $description,
            null,
            1882,
            'Jana Kvapilová',
            'jana@example.test',
            null,
            null,
            23378,
            'středisko Pozořice',
            null,
            null,
            'test-release',
            [],
            new DateTimeImmutable('2026-07-15 10:00:00'),
        );
    }

    /** @return \Doctrine\Common\Collections\Collection<int, TechnicalErrorReportReply> */
    private function readRepliesCollection(TechnicalErrorReport $report): \Doctrine\Common\Collections\Collection
    {
        $property = new ReflectionProperty($report, 'replies');
        $property->setAccessible(true);

        return $property->getValue($report);
    }

    private function replyCount(): int
    {
        return count($this->entityManager->getConnection()->fetchAllAssociative('SELECT id FROM technical_error_report_reply'));
    }
}

/**
 * Zpřístupňuje chráněné metody předka, aby se daly ověřit bez presenteru a bez konkrétního manažeru.
 */
final class AbstractManagerTestManager extends AbstractManager
{
    public function getEntityClass(): string
    {
        return TechnicalErrorReport::class;
    }

    public function save(TechnicalErrorReport $report): TechnicalErrorReport
    {
        $this->saveEntity($report);

        return $report;
    }

    public function delete(TechnicalErrorReport $report): void
    {
        $this->deleteEntity($report);
    }

    public function hardDelete(TechnicalErrorReport $report): void
    {
        $this->hardDeleteEntity($report);
    }

    public function restore(TechnicalErrorReport $report): void
    {
        $this->restoreDeletedEntity($report);
    }

    public function initialize(object $entity): void
    {
        $this->initializeEntity($entity);
    }
}

/** @extends AbstractRepository<TechnicalErrorReportReply> */
final class AbstractManagerTestReplyRepository extends AbstractRepository
{
    public function getEntityClass(): string
    {
        return TechnicalErrorReportReply::class;
    }
}
