<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Doctrine\ORM\Mapping as ORM;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Common\Aggregate;
use Model\Event\SkautisEducationId;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_education_cashbooks")
 */
class Education extends Aggregate
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="skautis_education_id")
     */
    private SkautisEducationId $id;

    /**
     * @ORM\Id()
     * @ORM\Column(type="int")
     */
    private int $year;

    /** @ORM\Column(type="cashbook_id") */
    private CashbookId $cashbookId;

    public function __construct(SkautisEducationId $id, int $year, CashbookId $cashbookId)
    {
        $this->id         = $id;
        $this->year       = $year;
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
