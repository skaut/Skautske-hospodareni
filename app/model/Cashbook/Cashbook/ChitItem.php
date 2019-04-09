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
     * @var int|NULL
     * @ORM\Id()
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue()
     */
    private $id;

    /**
     * @var Chit
     * @ORM\ManyToOne(targetEntity=Chit::class, inversedBy="items")
     * @ORM\JoinColumn(name="chit_id", referencedColumnName="id")
     */
    private $chit;

    /**
     * @var Amount
     * @ORM\Embedded(class=Amount::class, columnPrefix=false)
     */
    private $amount;

    /**
     * @var Category
     * @ORM\Embedded(class=Category::class, columnPrefix=false)
     */
    private $category;

    public function __construct(Chit $chit, Amount $amount, Category $category)
    {
        $this->chit     = $chit;
        $this->amount   = $amount;
        $this->category = $category;
    }

    public function getId() : ?int
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

    public function setCategory(Category $category) : void
    {
        $this->category = $category;
    }
}
