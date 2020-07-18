<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use Doctrine\ORM\Mapping as ORM;
use Model\Payment\Group;

/**
 * @ORM\Entity()
 * @ORM\Table(name="pa_group_unit")
 */
class Unit
{
    /**
     * @internal for mapping only
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @internal for mapping only
     *
     * @ORM\ManyToOne(targetEntity=Group::class, inversedBy="units")
     */
    private Group $group;

    /** @ORM\Column(type="integer") */
    private int $unitId;

    public function __construct(Group $group, int $unitId)
    {
        $this->group  = $group;
        $this->unitId = $unitId;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }
}
