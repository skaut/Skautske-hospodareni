<?php

declare(strict_types=1);

namespace App\Model\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Common\Aggregate;
use App\Model\Event\SkautisEducationId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ac_education_cashbooks')]
class Education extends Aggregate
{
    #[ORM\Id]
    #[ORM\Column(type: 'skautis_education_id')]
    private SkautisEducationId $id;

    /**
     * For education events spanning multiple years, a separate cashbook is needed for each year.
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $year;

    #[ORM\Column(type: 'cashbook_id')]
    private CashbookId $cashbookId;

    public function __construct(SkautisEducationId $id, int $year, CashbookId $cashbookId)
    {
        $this->id = $id;
        $this->year = $year;
        $this->cashbookId = $cashbookId;
    }

    public function getSkautisId(): SkautisEducationId
    {
        return $this->id;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }
}
