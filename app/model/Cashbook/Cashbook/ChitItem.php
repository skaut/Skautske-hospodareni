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
     * @internal only for mapping
     *
     * @ORM\Id()
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue()
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Chit::class, inversedBy="items")
     * @ORM\JoinColumn(name="chit_id", referencedColumnName="id", nullable=false)
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

    /**
     * @ORM\Column(type="string", length=120)
     *
     * @var string
     */
    private $purpose;

    public function __construct(Chit $chit, Amount $amount, Category $category, string $purpose)
    {
        $this->chit     = $chit;
        $this->amount   = $amount;
        $this->category = $category;
        $this->purpose  = $purpose;
    }

    public function getAmount() : Amount
    {
        return $this->amount;
    }

    public function getCategory() : Category
    {
        return $this->category;
    }

    public function getPurpose() : string
    {
        return $this->purpose;
    }
}
