<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Cashbook;

use App\Model\Cashbook\Operation;
use Consistence\Doctrine\Enum\EnumAnnotation;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Embeddable() */
class Category
{
    /** @ORM\Column(type="integer", name="category") */
    private int $id;

    /**
     * @ORM\Column(type="string_enum", name="category_operation_type", length=255, nullable=true)
     *
     * @var Operation
     * @EnumAnnotation(class=Operation::class)
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    private $operationType;

    public function __construct(int $id, Operation $operationType)
    {
        $this->id = $id;
        $this->operationType = $operationType;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOperationType(): Operation
    {
        return $this->operationType;
    }
}
