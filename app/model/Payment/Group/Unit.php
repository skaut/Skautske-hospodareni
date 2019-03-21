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
     * @var int
     * @internal for mapping only
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Group
     * @internal for mapping only
     * @ORM\ManyToOne(targetEntity=Group::class, inversedBy="units")
     */
    private $group;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $unitId;

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
