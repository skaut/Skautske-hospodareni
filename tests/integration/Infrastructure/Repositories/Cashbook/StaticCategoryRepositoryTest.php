<?php

namespace Model\Infrastructure\Repositories\Cashbook;

use Doctrine\ORM\EntityManager;
use eGen\MessageBus\Bus\EventBus;
use Model\Cashbook\Category;
use Model\Cashbook\ObjectType;

class StaticCategoryRepositoryTest extends \IntegrationTest
{

    private const TABLE_NAME = 'ac_chitsCategory';
    private const OBJECT_TABLE = 'ac_chitsCategory_object';

    /** @var StaticCategoryRepository */
    private $repository;

    protected function _before()
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);
        parent::_before();
        $this->repository = new StaticCategoryRepository($this->tester->grabService(EntityManager::class), new EventBus());
    }

    protected function getTestedEntites(): array
    {
        return [
            Category::class,
            Category\ObjectType::class,
        ];
    }

    public function testFindByObjectType()
    {
        $this->tester->haveInDatabase(self::TABLE_NAME, [
            'label' => 'Category 1',
            'short' => 'c1',
            'type' => 'in',
            'orderby' => 300,
            'deleted' => 0,
        ]);
        $this->tester->haveInDatabase(self::TABLE_NAME, [
            'label' => 'Category 2',
            'short' => 'c2',
            'type' => 'out',
            'orderby' => 400,
            'deleted' => 0,
        ]);
        $this->tester->haveInDatabase(self::TABLE_NAME, [
            'label' => 'Category 3',
            'short' => 'c3',
            'type' => 'out',
            'orderby' => 400,
            'deleted' => 0,
        ]);

        $this->tester->haveInDatabase(self::OBJECT_TABLE, [
            'categoryId' => 1,
            'objectTypeId' => ObjectType::EVENT,
        ]);
        $this->tester->haveInDatabase(self::OBJECT_TABLE, [
            'categoryId' => 3,
            'objectTypeId' => ObjectType::EVENT,
        ]);

        $categories = $this->repository->findByObjectType(ObjectType::get(ObjectType::EVENT));

        $expectedNames = [
            'Category 3',
            'Category 1',
        ];

        $actualNames = array_map(function(Category $category) {
            return $category->getName();
        }, $categories);

        $this->assertSame($expectedNames, $actualNames);
    }

}
