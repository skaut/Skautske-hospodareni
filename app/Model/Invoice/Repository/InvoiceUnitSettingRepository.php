<?php

declare(strict_types=1);

namespace App\Model\Invoice\Repository;

use App\Model\Infrastructure\Repository\AbstractRepository;
use App\Model\Invoice\Entity\InvoiceUnitSetting;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceUnitSettingRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return InvoiceUnitSetting::class;
    }

    public function findByUnitAndYear(int $unitId, int $year): ?InvoiceUnitSetting
    {
        return $this->findOneBy([
            'unit' => $unitId,
            'year' => $year,
        ]);
    }
}
