<?php

declare(strict_types=1);

namespace Model\Event;

final class ParticipantStatistics
{
    /** @var int */
    private $realAdult;

    /** @var int */
    private $realChild;

    /** @var int */
    private $realCount;

    /** @var int */
    private $realChildDays;

    /** @var int */
    private $realPersonDays;

    public function __construct(
        int $realAdult,
        int $realChild,
        int $realCount,
        int $realChildDays,
        int $realPersonDays
    ) {
        $this->realAdult      = $realAdult;
        $this->realChild      = $realChild;
        $this->realCount      = $realCount;
        $this->realChildDays  = $realChildDays;
        $this->realPersonDays = $realPersonDays;
    }

    public function getRealAdult() : int
    {
        return $this->realAdult;
    }

    public function getRealChild() : int
    {
        return $this->realChild;
    }

    public function getRealCount() : int
    {
        return $this->realCount;
    }

    /**
     * Returns number of children x days on camp
     */
    public function getRealChildDays() : int
    {
        return $this->realChildDays;
    }

    /**
     * Returns number of persons x days on camp
     */
    public function getRealPersonDays() : int
    {
        return $this->realPersonDays;
    }
}
