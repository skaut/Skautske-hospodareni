<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Budget;

use App\Model\Budget\Unit\Category;
use App\Model\Cashbook\Operation;
use App\Model\Utils\MoneyFactory;
use Doctrine\ORM\EntityManager;
use IntegrationTest;

class CategoryRepositoryTest extends IntegrationTest
{
    private CategoryRepository $repository;

    public function _before(): void
    {
        parent::_before();

        $this->repository = new CategoryRepository($this->tester->grabService(EntityManager::class));
    }

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [Category::class];
    }

    public function testSaveRootCategory(): void
    {
        $category = new Category(1, 'P1 root', Operation::INCOME(), null, MoneyFactory::zero(), 2019);

        $this->repository->save($category);

        $this->entityManager->clear();
        $category2 = $this->repository->find(1);

        $this->assertSame($category->getLabel(), $category2->getLabel());
        $this->assertSame([], $category2->getChildren());
        $this->assertTrue($category->getValue()->equals($category2->getValue()));
    }

    public function testSaveCategoryTree(): void
    {
        $categoryRoot = new Category(1, 'P1 root', Operation::INCOME(), null, MoneyFactory::zero(), 2019);
        $categoryLeaf = new Category(2, 'P1.2 leaf', Operation::INCOME(), $categoryRoot, MoneyFactory::zero(), 2019);

        $this->repository->save($categoryRoot);
        $this->repository->save($categoryLeaf);

        $this->entityManager->clear();
        $categoryRoot2 = $this->repository->find(1);
        $categoryLeaf2 = $this->repository->find(2);

        $this->assertSame([$categoryLeaf2], $categoryRoot2->getChildren());

        $this->assertSame($categoryLeaf->getLabel(), $categoryLeaf2->getLabel());
        $this->assertSame([], $categoryLeaf2->getChildren());
        $this->assertTrue($categoryLeaf->getValue()->equals($categoryLeaf2->getValue()));
    }
}
