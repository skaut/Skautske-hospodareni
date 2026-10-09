<?php

declare(strict_types=1);

namespace App\Model\Cashbook;

use App\Model\Utils\MoneyFactory;
use Codeception\Test\Unit;

final class CategoryCatalogTest extends Unit
{
    public function testSelectablePairsHideUndefinedAndLegacyCategories(): void
    {
        $categories = [
            $this->staticCategory(15, 'Převod z pokladny jednotky', Operation::INCOME()),
            $this->staticCategory(1, 'Od dětí a roverů', Operation::INCOME()),
            $this->staticCategory(ICategory::UNDEFINED_INCOME_ID, 'Neurčeno', Operation::INCOME()),
            $this->staticCategory(999, 'Vlastní historická kategorie', Operation::INCOME()),
        ];

        $this->assertSame([
            15 => 'Převod z pokladny jednotky',
            1 => 'Od dětí a roverů',
        ], CategoryCatalog::selectablePairs($categories, Operation::INCOME()));
    }

    public function testCompatibleCategoryUsesTargetSkautisCategory(): void
    {
        $source = $this->staticCategory(6, 'Materiál', Operation::EXPENSE());
        $targetCategories = [
            $this->staticCategory(6, 'Materiál', Operation::EXPENSE()),
            new CampCategory(501, Operation::EXPENSE(), 'Materiál', MoneyFactory::zero()),
        ];

        $matched = CategoryCatalog::findCompatibleCategory($source, $targetCategories);

        $this->assertInstanceOf(CampCategory::class, $matched);
        $this->assertSame(501, $matched->getId());
    }

    public function testUnknownHistoricalCategoryHasNoCompatibleTargetCategory(): void
    {
        $source = new CampCategory(501, Operation::EXPENSE(), 'Vlastní táborová položka', MoneyFactory::zero());

        $this->assertNull(CategoryCatalog::findCompatibleCategory($source, [
            $this->staticCategory(6, 'Materiál', Operation::EXPENSE()),
        ]));
    }

    public function testLegacyCategoryNamesHaveCanonicalCodes(): void
    {
        $this->assertSame(
            CategoryCatalog::INCOME_CHILDREN,
            CategoryCatalog::codeByDefinition(1, 'Přijmy od účastníků', Operation::INCOME()),
        );
        $this->assertSame(
            CategoryCatalog::EXPENSE_SERVICES,
            CategoryCatalog::codeByDefinition(2, 'Služby', Operation::EXPENSE()),
        );
    }

    private function staticCategory(int $id, string $name, Operation $operation): Category
    {
        return new Category($id, $name, 'test-'.$id, $operation, [], false, 100);
    }
}
