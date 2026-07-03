<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Budget;

use App\Model\Budget\CategoryNotFound;
use App\Model\Budget\Repositories\ICategoryRepository;
use App\Model\Budget\Unit\Category;
use App\Model\Cashbook\Operation;
use Doctrine\ORM\EntityManager;

use function sprintf;

final class CategoryRepository implements ICategoryRepository
{
    public function __construct(private EntityManager $em)
    {
    }

    public function find(int $id): Category
    {
        $category = $this->em->find(Category::class, $id);
        if ($category === null) {
            throw new CategoryNotFound(sprintf('Budget category %d was not found.', $id));
        }

        return $category;
    }

    /** @return Category[] */
    public function findCategories(int $unitId, Operation $operationType): array
    {
        return $this->em->getRepository(Category::class)->findBy([
            'type' => $operationType->getValue(),
            'parent' => null,
            'unitId' => $unitId,
        ]);
    }

    public function save(Category $category): void
    {
        $this->em->persist($category);
        $this->em->flush();
    }
}
