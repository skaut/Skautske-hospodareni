<?php

namespace Model\Cashbook;

use Doctrine\Common\Collections\ArrayCollection;

class Category
{

    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $shortcut;

    /** @var Operation */
    private $operationType;

    /** @var Category\ObjectType[]|ArrayCollection */
    private $types;

    /** @var int */
    private $priority;

    /** @var bool */
    private $deleted = FALSE;

    public function __construct()
    {
        $this->types = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

}
