<?php

declare(strict_types=1);

namespace App\Model\Participant\Payment;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class Event
{
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * @var EventType
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    #[ORM\Column(type: 'participant_event_type', length: 9)]
    private $type;

    public function __construct(int $id, EventType $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): EventType
    {
        return $this->type;
    }
}
