<?php

declare(strict_types=1);

namespace App\Model\Help\Repository;

use App\Model\Help\Entity\PageHelp;
use App\Model\Infrastructure\Repository\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/** @extends AbstractRepository<PageHelp> */
class PageHelpRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return PageHelp::class;
    }

    public function findByPageKey(string $pageKey): ?PageHelp
    {
        return $this->findOneBy(['pageKey' => $pageKey]);
    }

    public function createGridQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('help');
    }

    /** @return PageHelp[] */
    public function findAllOrderedByPageKey(): array
    {
        return $this->findBy([], ['pageKey' => 'ASC']);
    }
}
