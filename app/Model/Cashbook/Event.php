<?php

declare(strict_types=1);

namespace App\Model\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Common\Aggregate;
use App\Model\Event\SkautisEventId;
use Doctrine\ORM\Mapping as ORM;

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
        $this->id = $id;
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
