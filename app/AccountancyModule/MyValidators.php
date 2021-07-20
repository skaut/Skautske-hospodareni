<?php

declare(strict_types=1);

namespace App;

use App\AccountancyModule\Components\FormControls\DateControl;
use DateTimeInterface;
use Nette\Forms\Controls\MultiChoiceControl;
use Nette\StaticClass;
use Nette\Utils\Validators;

use function array_intersect;
use function count;
use function explode;
use function preg_replace;
use function trim;

class MyValidators
{
    use StaticClass;

    public const EMAIL_SEPARATOR = ',';

    /**
     * @param mixed $control
     */
    public static function isValidDate($control): bool
    {
        return $control->value !== null;
    }

    public static function isValidRange(DateControl $end, DateTimeInterface $start): bool
    {
        return $start <= $end->getValue();
    }

    /**
     * @param mixed[] $values
     */
    public static function hasSelectedAny(MultiChoiceControl $control, array $values): bool
    {
        return count(array_intersect($control->getValue(), $values)) !== 0;
    }

    /**
     * @param mixed $control
     */
    public static function isValidEmailList($control): bool
    {
        $value = preg_replace('/\s+/', '', $control->value);
        foreach (explode(self::EMAIL_SEPARATOR, $value) as $email) {
            if (! Validators::isEmail(trim($email))) {
                return false;
            }
        }

        return true;
    }
}
