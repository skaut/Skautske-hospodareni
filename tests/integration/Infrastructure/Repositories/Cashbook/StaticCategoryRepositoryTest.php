<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Doctrine\ORM\EntityManager;
use Hskauting\Tests\NullEventBus;
use IntegrationTest;
use Model\Cashbook\Category;
use Model\Cashbook\ObjectType;

use function array_map;

class StaticCategoryRepositoryTest extends IntegrationTest
{
    private const TABLE_NAME   = 'ac_chitsCategory';
    private const OBJECT_TABLE = 'ac_chitsCategory_object';

    private StaticCategoryRepository $repository;

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);
        parent::_before();
        $this->repository = new StaticCategoryRepository(
            $this->tester->grabService(EntityManager::class),
            new NullEventBus(),
        );
    }

    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots(): array
    {
        return [Category::class];
    }

    public function testFindByObjectType(): void
    {
        $this->tester->haveInDatabase(self::TABLE_NAME, [
            'name' => 'Category 1',
            'shortcut' => 'c1',
            'operation_type' => 'in',
            'virtual' => false,
            'priority' => 300,
            'deleted' => 0,
        ]);
        $this->tester->haveInDatabase(self::TABLE_NAME, [
            'name' => 'Category 2',
            'shortcut' => 'c2',
            'operation_type' => 'out',
            'virtual' => false,
            'priority' => 400,
            'deleted' => 0,
        ]);
        $this->tester->haveInDatabase(self::TABLE_NAME, [
            'name' => 'Category 3',
            'shortcut' => 'c3',
            'operation_type' => 'out',
            'virtual' => false,
            'priority' => 400,
            'deleted' => 0,
        ]);

        $this->tester->haveInDatabase(self::OBJECT_TABLE, [
            'category_id' => 1,
            'type' => ObjectType::EVENT,
        ]);
        $this->tester->haveInDatabase(self::OBJECT_TABLE, [
            'category_id' => 3,
            'type' => ObjectType::EVENT,
        ]);

        $categories = $this->repository->findByObjectType(ObjectType::get(ObjectType::EVENT));

        $expectedNames = [
            'Category 3',
            'Category 1',
        ];

        $actualNames = array_map(static function (Category $category) {
            return $category->getName();
        }, $categories);

        $this->assertSame($expectedNames, $actualNames);
    }
}
