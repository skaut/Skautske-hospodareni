<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_chits_item")
 * USE AS FINAL METHOD
 */
class ChitItem
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue()
     *
     * @var int|NULL
     */
    private $_id;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Chit::class, inversedBy="items")
     * @ORM\JoinColumn(name="chit_id", referencedColumnName="id")
     *
     * @var Chit
     */
    private $chit;

    /**
     * @ORM\Embedded(class=Amount::class, columnPrefix=false)
     *
     * @var Amount
     */
    private $amount;

    /**
     * @ORM\Embedded(class=Category::class, columnPrefix=false)
     *
     * @var Category
     */
    private $category;

    public function __construct(int $id, Chit $chit, Amount $amount, Category $category)
    {
        $this->id       = $id;
        $this->chit     = $chit;
        $this->amount   = $amount;
        $this->category = $category;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getAmount() : Amount
    {
        return $this->amount;
    }

    public function getCategory() : Category
    {
        return $this->category;
    }
}
