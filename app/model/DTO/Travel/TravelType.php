<?php

declare(strict_types=1);

namespace Model\DTO\Travel;

use Nette\SmartObject;

/**
 * @property-read string $label
 */
class TravelType
{
    use SmartObject;

    /** @var string */
    private $shortcut;

    /** @var string */
    private $label;

    /** @var bool */
    private $hasFuel;

    public function __construct(string $type, string $label, bool $hasFuel)
    {
        $this->shortcut = $type;
        $this->label    = $label;
        $this->hasFuel  = $hasFuel;
    }

    public function getShortcut() : string
    {
        return $this->shortcut;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    public function hasFuel() : bool
    {
        return $this->hasFuel;
    }
}
