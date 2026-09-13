<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use DateTimeImmutable;

use function explode;
use function hash;
use function implode;
use function ltrim;
use function number_format;
use function sprintf;
use function str_contains;
use function trim;

final class BankTransactionKeyGenerator
{
    public function fromFio(string $transactionId): string
    {
        return trim($transactionId);
    }

    public function fromGpc(
        string $accountNumber,
        DateTimeImmutable $date,
        float $amount,
        ?string $counterAccount,
        string $name,
        ?int $variableSymbol,
        ?int $constantSymbol,
        ?string $note,
    ): string {
        $canonical = implode('|', [
            'gpc',
            $accountNumber,
            $date->format('Y-m-d'),
            number_format($amount, 2, '.', ''),
            trim((string) $counterAccount),
            trim($name),
            (string) $variableSymbol,
            (string) $constantSymbol,
            trim((string) $note),
        ]);

        return 'gpc:'.hash('sha256', $canonical);
    }

    /**
     * Klíč ve tvaru, jaký generovala implementace nad ifm24/bank-statements.
     *
     * Ta protiúčet nenormalizovala a předávala ho tak, jak ho vysekala ze záznamu `075`:
     * `000019-0000017608/0100`, u nevyplněného protiúčtu `000000-0000000000/0000`. Nový parser
     * vrací `19-17608/0100`, resp. `null`, takže by se pro tutéž transakci klíč lišil.
     *
     * Klíč je přitom identifikátor napříč tabulkami — odkazuje na něj `bank_transaction_pairing`
     * i snapshot spárované transakce v `pa_payment` a `invoice`. Existující data se proto
     * nepřepisují; import místo toho hledá transakci i pod tímto starým klíčem, aby znovunahrání
     * dříve importovaného souboru nevytvořilo duplicity.
     *
     * Až bude jisté, že se žádný soubor importovaný před přechodem na webwingscz/bank-statements
     * znovu nahrávat nebude, může tato metoda i její použití v {@see GpcParser} zmizet.
     */
    public function legacyFromGpc(
        string $accountNumber,
        DateTimeImmutable $date,
        float $amount,
        ?string $counterAccount,
        string $name,
        ?int $variableSymbol,
        ?int $constantSymbol,
        ?string $note,
    ): string {
        return $this->fromGpc(
            $accountNumber,
            $date,
            $amount,
            $this->toLegacyCounterAccount($counterAccount),
            $name,
            $variableSymbol,
            $constantSymbol,
            $note,
        );
    }

    /**
     * Rekonstruuje nenormalizovaný tvar protiúčtu: 6místné předčíslí, 10místné číslo účtu
     * a 4místný kód banky, vše doplněné nulami zleva.
     */
    private function toLegacyCounterAccount(?string $counterAccount): string
    {
        $prefix = '0';
        $number = '0';
        $bankCode = '0';

        if ($counterAccount !== null && $counterAccount !== '') {
            [$accountPart, $bankCode] = str_contains($counterAccount, '/')
                ? explode('/', $counterAccount, 2)
                : [$counterAccount, '0'];

            [$prefix, $number] = str_contains($accountPart, '-')
                ? explode('-', $accountPart, 2)
                : ['0', $accountPart];
        }

        return sprintf(
            '%06d-%010d/%04d',
            (int) ltrim($prefix, '0'),
            (int) ltrim($number, '0'),
            (int) ltrim($bankCode, '0'),
        );
    }
}
