<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Doctrine\ORM\Mapping as ORM;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Common\Aggregate;
use Model\Event\SkautisCampId;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_camp_cashbooks")
 */
class Camp extends Aggregate
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="skautis_camp_id")
     */
    private SkautisCampId $id;

    /** @ORM\Column(type="cashbook_id") */
    private CashbookId $cashbookId;

    public function __construct(SkautisCampId $id, CashbookId $cashbookId)
    {
        $this->id         = $id;
        $this->cashbookId = $cashbookId;
    }

    public function getSkautisId(): SkautisCampId
    {
        return $this->id;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }
}
