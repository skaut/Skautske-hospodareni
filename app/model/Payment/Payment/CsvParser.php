<?php

declare(strict_types=1);

namespace Model\Payment\Payment;

use Cake\Chronos\ChronosDate;
use Model\Common\EmailAddress;
use Model\Payment\Commands\Payment\CreatePayment;
use Model\Payment\VariableSymbol;
use Model\Utils\Strings;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Utils\Validators;

use function array_combine;
use function array_filter;
use function array_map;
use function array_pad;
use function array_values;
use function count;
use function explode;
use function floatval;
use function intval;
use function is_string;
use function str_getcsv;
use function trim;

use const PHP_EOL;

class CsvParser
{
    private const CSV_DELIMITER = ',';

    /** @return CreatePayment[] */
    public function parse(int $groupId, string $fileContent): array
    {
        $utfContent = Strings::autoUTF($fileContent);
        $lines      = array_filter(str_getcsv($utfContent, PHP_EOL));
        $payments   = [];

        foreach ($lines as $line) {
            $payments[] = $this->parseLine($groupId, $line);
        }

        return $payments;
    }

    private function parseLine(int $groupId, string $line): mixed
    {
        $splitLine = str_getcsv($line, self::CSV_DELIMITER);

        $keys      = ['name', 'amount', 'date', 'emails', 'variableSymbol', 'constantSymbol', 'note'];
        $splitLine = array_pad($splitLine, count($keys), null);
        $splitLine = array_combine($keys, $splitLine);

        $validValues = $this->validateValues($splitLine);

        return new CreatePayment(
            $groupId,
            $validValues->name,
            $validValues->emails,
            $validValues->amount,
            $validValues->date,
            null,
            $validValues->variableSymbol,
            $validValues->constantSymbol,
            $validValues->note ?? '',
        );
    }

    /** @param array<string, mixed > $splitLine */
    private function validateValues(array $splitLine): mixed
    {
        $schema = Expect::structure([
            'name' => Expect::string()->max(255),
            'emails' => Expect::arrayOf('string')
                ->before(fn (mixed $value) => is_string($value) && trim($value) !== ''
                    ? array_map('trim', explode(',', $value))
                    : [])
                ->assert(function ($emails) {
                    foreach ($emails as $email) {
                        if ($email === '') {
                            return true;
                        }

                        if (! Validators::isEmail($email)) {
                            return false;
                        }
                    }

                    return true;
                }, 'Emailová adresa je neplatná')
                ->nullable()
                ->transform(fn ($emails) => array_values(array_filter(array_map(
                    fn ($email) => $email !== '' ? new EmailAddress($email) : null,
                    $emails,
                )))),
            'amount' => Expect::float()
                ->before(fn (mixed $value) => $value !== null ? floatval($value) : $value),
            'date' => Expect::type(ChronosDate::class)
                ->before(function (mixed $value) {
                    $formats = ['j.n.Y', 'd.m.Y'];
                    if ($value !== null) {
                        foreach ($formats as $format) {
                            $d = ChronosDate::createFromFormat($format, $value);
                            if ($d->format($format) === $value) {
                                return $d;
                            }
                        }
                    }

                    return $value;
                }),
            'variableSymbol' => Expect::int()
                ->before(fn (mixed $value) => empty($value) ? null : intval($value))
                ->nullable()
                ->min(1)
                ->max(9999999999)
                ->transform(fn ($value) => $value !== null ? new VariableSymbol((string) $value) : null),
            'constantSymbol' => Expect::int()
                ->before(fn (mixed $value) => empty($value) ? null : intval($value))
                ->min(100)
                ->max(999)
                ->nullable(),
            'note' => Expect::string()
                ->max(255)
                ->nullable(),
        ]);

        $validator = new Processor();

        return $validator->process($schema, $splitLine);
    }
}
