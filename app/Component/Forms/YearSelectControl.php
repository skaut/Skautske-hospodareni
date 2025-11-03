<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\FormControls;

use Nette\Forms\Controls\SelectBox;

use function array_combine;
use function date;
use function range;

class YearSelectControl extends SelectBox
{
    private const YEARS_RANGE = [-5, 2];

    private function getYear(int $yearsDifference): int
    {
        return (int) date('Y') + $yearsDifference;
    }

    /** @return array<int, int> */
    public function getYearsDescending(): array
    {
        return range(self::getYear(self::YEARS_RANGE[1]), self::getYear(self::YEARS_RANGE[0]));
    }

    public function __construct(?string $label = 'Rok', ?callable $filterCallback = null)
    {
        $yearsDescending = $this->getYearsDescending();

        if ($filterCallback !== null) {
            $yearsDescending = $filterCallback($yearsDescending);
        }

        $items = array_combine($yearsDescending, $yearsDescending);

        parent::__construct($label, $items);

        $this->setRequired('Musíte vybrat rok');
    }

    public function setDefaultValue($value): YearSelectControl
    {
        if ($value === 'now') {
            $value = (int) date('Y');
        }

        return parent::setDefaultValue($value);
    }
}
