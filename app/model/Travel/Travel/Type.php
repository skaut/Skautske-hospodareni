<?php

declare(strict_types=1);

namespace Model\Travel\Travel;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="tc_travelTypes")
 */
class Type
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="string", length=5, name="type")
     *
     * @var string
     */
    private $shortcut;

    /**
     * @ORM\Column(type="string", length=64)
     *
     * @var string
     */
    private $label;


    /**
     * @ORM\Column(type="boolean", name="hasFuel", options={"default":false})
     *
     * @var bool
     */
    private $hasFuel;

    /**
     * @ORM\Column(type="smallint", options={"default":10})
     *
     * @var int
     */
    private $order;

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

    public function __toString() : string
    {
        return $this->getShortcut();
    }
}
