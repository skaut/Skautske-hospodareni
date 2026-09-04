<?php

declare(strict_types=1);

namespace App\Model\PageView\Repository;

use App\Model\Infrastructure\Repository\AbstractRepository;
use App\Model\PageView\Entity\PageViewDaily;
use DateTimeImmutable;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

use function array_map;
use function is_string;

class PageViewDailyRepository extends AbstractRepository
{
    private ?bool $storageAvailable = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return PageViewDaily::class;
    }

    /**
     * Lets counting stay dormant between a deploy and the migration instead of
     * breaking every page in that window.
     */
    public function isStorageAvailable(): bool
    {
        return $this->storageAvailable ??= $this->entityManager
            ->getConnection()
            ->createSchemaManager()
            ->tablesExist(['page_view_daily']);
    }

    /**
     * Adds one view to the counter of a page for a day.
     *
     * A single upsert on purpose: two people opening the same page in the same
     * moment must not fight over a read-modify-write, and the unique index makes
     * the database decide which of them creates the row.
     */
    public function increment(string $pageKey, DateTimeImmutable $day): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO page_view_daily (page_key, day, views) VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE views = views + 1',
            [$pageKey, $day->format('Y-m-d')],
            [ParameterType::STRING, ParameterType::STRING],
        );
    }

    /**
     * Views per page over a closed range of days, most used first.
     *
     * @return array<string, int> page key => views
     */
    public function sumByPage(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        if (! $this->isStorageAvailable()) {
            return [];
        }

        $rows = $this->entityManager->getConnection()->executeQuery(
            'SELECT page_key, SUM(views) AS views
                FROM page_view_daily
                WHERE day BETWEEN ? AND ?
                GROUP BY page_key
                ORDER BY views DESC, page_key ASC',
            [$from->format('Y-m-d'), $to->format('Y-m-d')],
            [ParameterType::STRING, ParameterType::STRING],
        )->fetchAllKeyValue();

        return array_map('intval', $rows);
    }

    /** The first day ever counted, so a report can say how far back it can see. */
    public function findFirstDay(): ?DateTimeImmutable
    {
        if (! $this->isStorageAvailable()) {
            return null;
        }

        $day = $this->entityManager->getConnection()
            ->executeQuery('SELECT MIN(day) FROM page_view_daily')
            ->fetchOne();

        if (! is_string($day)) {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $day.' 00:00:00');

        return $parsed === false ? null : $parsed;
    }
}
