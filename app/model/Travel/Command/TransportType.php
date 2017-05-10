<?php

namespace Model\Travel\Command;

class TransportType
{

    /** @var string */
    private $shortcut;

    /** @var bool */
    private $hasFuel;

    public function __construct(string $shortcut, bool $hasFuel)
    {
        $this->shortcut = $shortcut;
        $this->hasFuel = $hasFuel;
    }

    public function getShortcut(): string
    {
        return $this->shortcut;
    }

    public function hasFuel(): bool
    {
        return $this->hasFuel;
    }

}
