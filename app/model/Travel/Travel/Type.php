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
     * @var string
     * @ORM\Id()
     * @ORM\Column(type="string", length=5)
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $label;


    /**
     * @var bool
     * @ORM\Column(type="boolean", name="hasFuel", options={"default":false})
     */
    private $hasFuel;

    /**
     * @var int
     * @ORM\Column(type="smallint", options={"default":10})
     */
    private $order;

    public function getType() : string
    {
        return $this->type;
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
