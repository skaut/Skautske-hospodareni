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

    public function __construct(?int $id, Amount $amount, Category $category, string $purpose)
    {
        $this->id       = $id;
        $this->amount   = $amount;
        $this->category = $category;
        $this->purpose  = $purpose;
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

    public function getPurpose() : string
    {
        return $this->purpose;
    }

    public function __clone()
    {
        $this->id = null;
    }
}
