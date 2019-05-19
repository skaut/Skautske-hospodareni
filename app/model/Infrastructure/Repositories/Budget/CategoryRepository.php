<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Budget;

use Doctrine\ORM\EntityManager;
use Model\Budget\CategoryNotFound;
use Model\Budget\Repositories\ICategoryRepository;
use Model\Budget\Unit\Category;
use Model\Cashbook\Operation;
use function sprintf;

final class CategoryRepository implements ICategoryRepository
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function find(int $id) : Category
    {
        $category = $this->em->find(Category::class, $id);
        if ($category === null) {
            throw new CategoryNotFound(sprintf('Budget category %d was not found.', $id));
        }

        return $category;
    }

    /**
     * @return Category[]
     */
    public function findCategories(int $unitId, Operation $operationType) : array
    {
        return $this->em->getRepository(Category::class)->findBy([
            'type' => $operationType->getValue(),
            'parent' => null,
            'unitId' => $unitId,
        ]);
    }

    public function save(Category $category) : void
    {
        $this->em->persist($category);
        $this->em->flush();
    }
}
