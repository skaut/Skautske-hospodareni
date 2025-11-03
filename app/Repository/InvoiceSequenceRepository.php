<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityManagerInterface;
use Entity\InvoiceSequence;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\UnitNotFound;

class InvoiceSequenceRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager, protected IUnitRepository $unitRepository)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return InvoiceSequence::class;
    }

    public function getGrid(): array
    {
        /** @var InvoiceSequence[] $invoiceSequence */
        $invoiceSequence = $this->findAll();
        $data            = [];
        foreach ($invoiceSequence as $key => $value) {
            $data[$key] = $value->toArray();
            if ($value->getUnit() !== 0) {
                try {
                    $data[$key]['unitDisplayName'] = $this->unitRepository->find($value['unit'])->getDisplayName();
                } catch (UnitNotFound) {
                    $data[$key]['unitDisplayName'] = '';
                }
            } else {
                $data[$key]['unitDisplayName'] = '';
            }
        }

        return $data;
    }
}
