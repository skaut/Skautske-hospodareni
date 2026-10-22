<?php

declare(strict_types=1);

namespace App\Model\Cashbook;

use Nette\StaticClass;

use function array_search;
use function in_array;
use function mb_strtolower;
use function trim;

/**
 * Maps locally stored and SkautIS categories to the common cashbook catalogue.
 *
 * Camp and education categories have per-record IDs assigned by SkautIS, so the
 * name is the stable common identifier for those categories. Static categories
 * use their local ID as a fallback for old records whose labels predate the
 * common catalogue.
 */
final class CategoryCatalog
{
    use StaticClass;

    public const INCOME_CHILDREN = 'income_children';
    public const INCOME_ADULTS = 'income_adults';
    public const INCOME_OTHER = 'income_other';
    public const INCOME_MUNICIPALITY = 'income_municipality';
    public const INCOME_OWN_FUNDS = 'income_own_funds';
    public const TRANSFER_FROM_UNIT = 'transfer_from_unit';

    public const EXPENSE_TRANSPORT = 'expense_transport';
    public const EXPENSE_SERVICES = 'expense_services';
    public const EXPENSE_RENT = 'expense_rent';
    public const EXPENSE_FOOD = 'expense_food';
    public const EXPENSE_TRAVEL = 'expense_travel';
    public const EXPENSE_MATERIAL = 'expense_material';
    public const EXPENSE_EQUIPMENT = 'expense_equipment';
    public const EXPENSE_OTHER = 'expense_other';
    public const EXPENSE_RESERVE = 'expense_reserve';
    public const TRANSFER_TO_UNIT = 'transfer_to_unit';
    public const REFUND_CHILD = 'refund_child';
    public const REFUND_ADULT = 'refund_adult';

    /** @var array<string, string> */
    private const LABELS = [
        self::TRANSFER_FROM_UNIT => 'Převod z pokladny jednotky',
        self::INCOME_CHILDREN => 'Od dětí a roverů',
        self::INCOME_ADULTS => 'Od dospělých',
        self::INCOME_MUNICIPALITY => 'Příspěvky samosprávy',
        self::INCOME_OTHER => 'Ostatní příjmy',
        self::INCOME_OWN_FUNDS => 'Vlastní finanční prostředky',
        self::TRANSFER_TO_UNIT => 'Převod do pokladny jednotky',
        self::REFUND_CHILD => 'Vratka účastnického poplatku – dítě',
        self::REFUND_ADULT => 'Vratka účastnického poplatku – dospělý',
        self::EXPENSE_TRANSPORT => 'Doprava osob a materiálu',
        self::EXPENSE_SERVICES => 'Ostatní služby',
        self::EXPENSE_RENT => 'Nájem',
        self::EXPENSE_FOOD => 'Potraviny, stravné',
        self::EXPENSE_TRAVEL => 'Cestovné',
        self::EXPENSE_MATERIAL => 'Materiál',
        self::EXPENSE_EQUIPMENT => 'Vybavení',
        self::EXPENSE_OTHER => 'Ostatní výdaje',
        self::EXPENSE_RESERVE => 'Rezerva',
    ];

    /** @var list<string> */
    private const INCOME_FORM_ORDER = [
        self::TRANSFER_FROM_UNIT,
        self::INCOME_CHILDREN,
        self::INCOME_ADULTS,
        self::INCOME_MUNICIPALITY,
        self::INCOME_OTHER,
        self::INCOME_OWN_FUNDS,
    ];

    /** @var list<string> */
    private const EXPENSE_FORM_ORDER = [
        self::TRANSFER_TO_UNIT,
        self::REFUND_CHILD,
        self::REFUND_ADULT,
        self::EXPENSE_TRANSPORT,
        self::EXPENSE_SERVICES,
        self::EXPENSE_RENT,
        self::EXPENSE_FOOD,
        self::EXPENSE_TRAVEL,
        self::EXPENSE_MATERIAL,
        self::EXPENSE_EQUIPMENT,
        self::EXPENSE_OTHER,
    ];

    /** @var list<string> */
    private const INCOME_REPORT_ORDER = [
        self::INCOME_CHILDREN,
        self::INCOME_ADULTS,
        self::INCOME_OTHER,
        self::INCOME_MUNICIPALITY,
        self::INCOME_OWN_FUNDS,
    ];

    /** @var list<string> */
    private const EXPENSE_REPORT_ORDER = [
        self::EXPENSE_TRANSPORT,
        self::EXPENSE_SERVICES,
        self::EXPENSE_RENT,
        self::EXPENSE_FOOD,
        self::EXPENSE_TRAVEL,
        self::EXPENSE_MATERIAL,
        self::EXPENSE_EQUIPMENT,
        self::EXPENSE_OTHER,
        self::EXPENSE_RESERVE,
    ];

    /** @var array<int, string> */
    private const STATIC_CODES = [
        1 => self::INCOME_CHILDREN,
        2 => self::EXPENSE_SERVICES,
        3 => self::EXPENSE_FOOD,
        4 => self::EXPENSE_TRANSPORT,
        5 => self::EXPENSE_RENT,
        6 => self::EXPENSE_MATERIAL,
        7 => self::TRANSFER_TO_UNIT,
        9 => self::TRANSFER_FROM_UNIT,
        10 => self::EXPENSE_TRAVEL,
        15 => self::TRANSFER_FROM_UNIT,
        16 => self::TRANSFER_TO_UNIT,
        17 => self::INCOME_MUNICIPALITY,
        18 => self::INCOME_OTHER,
        19 => self::EXPENSE_EQUIPMENT,
        21 => self::REFUND_CHILD,
        22 => self::REFUND_ADULT,
        23 => self::INCOME_ADULTS,
        24 => self::EXPENSE_OTHER,
        25 => self::INCOME_OWN_FUNDS,
    ];

    /** @var array<string, string> */
    private const NAME_CODES = [
        'od dětí a roverů' => self::INCOME_CHILDREN,
        'příjmy od dětí a roverů' => self::INCOME_CHILDREN,
        'příjem od dětí' => self::INCOME_CHILDREN,
        'příjmy od účastníků' => self::INCOME_CHILDREN,
        'přijmy od účastníků' => self::INCOME_CHILDREN,
        'od dospělých' => self::INCOME_ADULTS,
        'příjmy od dospělých' => self::INCOME_ADULTS,
        'přijmy od dospělých' => self::INCOME_ADULTS,
        'příjem od dospělých' => self::INCOME_ADULTS,
        'ostatní příjmy' => self::INCOME_OTHER,
        'příspěvky samosprávy' => self::INCOME_MUNICIPALITY,
        'vlastní finanční prostředky' => self::INCOME_OWN_FUNDS,
        'převod z pokladny jednotky' => self::TRANSFER_FROM_UNIT,
        'převod z pokladny střediska' => self::TRANSFER_FROM_UNIT,
        'převod z odd. pokladny' => self::TRANSFER_FROM_UNIT,
        'převod z akce' => self::TRANSFER_FROM_UNIT,
        'doprava osob a materiálu' => self::EXPENSE_TRANSPORT,
        'jízdné' => self::EXPENSE_TRANSPORT,
        'ostatní služby' => self::EXPENSE_SERVICES,
        'služby' => self::EXPENSE_SERVICES,
        'nájem' => self::EXPENSE_RENT,
        'nájemné' => self::EXPENSE_RENT,
        'potraviny, stravné' => self::EXPENSE_FOOD,
        'potraviny' => self::EXPENSE_FOOD,
        'cestovné' => self::EXPENSE_TRAVEL,
        'materiál' => self::EXPENSE_MATERIAL,
        'vybavení' => self::EXPENSE_EQUIPMENT,
        'ostatní výdaje' => self::EXPENSE_OTHER,
        'rezerva' => self::EXPENSE_RESERVE,
        'převod do pokladny jednotky' => self::TRANSFER_TO_UNIT,
        'převod do stř. pokladny' => self::TRANSFER_TO_UNIT,
        'převod do odd. pokladny' => self::TRANSFER_TO_UNIT,
        'převod do akce' => self::TRANSFER_TO_UNIT,
        'vratka účastnického poplatku – dítě' => self::REFUND_CHILD,
        'vratka úč. poplatku - dítě' => self::REFUND_CHILD,
        'vratka úč. poplatku – dítě' => self::REFUND_CHILD,
        'vratka účastnického poplatku – dospělý' => self::REFUND_ADULT,
        'vratka úč. poplatku - dospělý' => self::REFUND_ADULT,
        'vratka úč. poplatku – dospělý' => self::REFUND_ADULT,
    ];

    public static function label(string $code): string
    {
        return self::LABELS[$code];
    }

    /** @return list<string> */
    public static function formOrder(Operation $operation): array
    {
        return $operation->equals(Operation::INCOME()) ? self::INCOME_FORM_ORDER : self::EXPENSE_FORM_ORDER;
    }

    /** @return list<string> */
    public static function reportOrder(Operation $operation): array
    {
        return $operation->equals(Operation::INCOME()) ? self::INCOME_REPORT_ORDER : self::EXPENSE_REPORT_ORDER;
    }

    public static function budgetPosition(string $name, Operation $operation): ?int
    {
        $code = self::codeByDefinition(0, $name, $operation);
        if ($code === null) {
            return null;
        }

        $position = array_search($code, self::reportOrder($operation), true);

        return $position === false ? null : $position;
    }

    /**
     * @param  ICategory[]        $categories
     * @return array<int, string>
     */
    public static function selectablePairs(array $categories, Operation $operation): array
    {
        $pairs = [];

        foreach (self::formOrder($operation) as $code) {
            $category = self::findCategoryByCode($categories, $code, $operation);
            if ($category !== null) {
                $pairs[$category->getId()] = self::label($code);
            }
        }

        return $pairs;
    }

    /** @param ICategory[] $categories */
    public static function findCompatibleCategory(ICategory $sourceCategory, array $categories): ?ICategory
    {
        $code = self::code($sourceCategory);
        if ($code === null) {
            return null;
        }

        return self::findCategoryByCode($categories, $code, $sourceCategory->getOperationType());
    }

    public static function code(ICategory $category): ?string
    {
        return self::codeByDefinition($category->getId(), $category->getName(), $category->getOperationType(), $category instanceof Category);
    }

    public static function codeByDefinition(int $id, string $name, Operation $operation, bool $isStaticCategory = false): ?string
    {
        $code = self::NAME_CODES[self::normaliseName($name)] ?? null;
        if ($code !== null && self::hasOperation($code, $operation)) {
            return $code;
        }

        $code = $isStaticCategory ? (self::STATIC_CODES[$id] ?? null) : null;

        return $code !== null && self::hasOperation($code, $operation) ? $code : null;
    }

    public static function isUndefinedId(int $id): bool
    {
        return $id === ICategory::UNDEFINED_INCOME_ID || $id === ICategory::UNDEFINED_EXPENSE_ID;
    }

    /** @param ICategory[] $categories */
    private static function findCategoryByCode(array $categories, string $code, Operation $operation): ?ICategory
    {
        $bestCategory = null;
        $bestScore = null;

        foreach ($categories as $category) {
            if (! $category->getOperationType()->equals($operation) || self::code($category) !== $code) {
                continue;
            }

            $score = self::categoryScore($category, $code);
            if ($bestScore === null || $score < $bestScore) {
                $bestCategory = $category;
                $bestScore = $score;
            }
        }

        return $bestCategory;
    }

    private static function categoryScore(ICategory $category, string $code): int
    {
        if (! $category instanceof Category) {
            return 0;
        }

        if ($code === self::TRANSFER_FROM_UNIT && $category->getId() === 15) {
            return 1;
        }

        if ($code === self::TRANSFER_TO_UNIT && $category->getId() === 16) {
            return 1;
        }

        return 2;
    }

    private static function hasOperation(string $code, Operation $operation): bool
    {
        if ($operation->equals(Operation::INCOME())) {
            return in_array($code, self::INCOME_FORM_ORDER, true);
        }

        return in_array($code, self::EXPENSE_FORM_ORDER, true) || $code === self::EXPENSE_RESERVE;
    }

    private static function normaliseName(string $name): string
    {
        return trim(mb_strtolower($name));
    }
}
