<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Doctrine\ORM\Mapping as ORM;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Common\Aggregate;
use Model\Event\SkautisEventId;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_event_cashbooks")
 */
class Event extends Aggregate
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="skautis_event_id")
     */
    private SkautisEventId $id;

    /** @ORM\Column(type="cashbook_id") */
    private CashbookId $cashbookId;

    public function __construct(SkautisEventId $id, CashbookId $cashbookId)
    {
        $this->id         = $id;
        $this->cashbookId = $cashbookId;
    }

    public function getSkautisId(): SkautisEventId
    {
        return $this->id;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }
}
