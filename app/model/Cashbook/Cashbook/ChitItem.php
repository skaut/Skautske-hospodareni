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
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Embedded(class=Amount::class, columnPrefix=false)
     */
    private Amount $amount;

    /**
     * @ORM\Embedded(class=Category::class, columnPrefix=false)
     */
    private Category $category;

    /**
     * @ORM\Column(type="string", length=120)
     */
    private string $purpose;

    public function __construct(Amount $amount, Category $category, string $purpose)
    {
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

    public function __clone()
    {
        $this->id = null;
    }

    public function withCategory(Category $category) : self
    {
        return new self($this->amount, $category, $this->purpose);
    }
}
