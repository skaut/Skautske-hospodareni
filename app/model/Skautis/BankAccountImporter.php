<?php

namespace Model\Skautis;

use BankAccountValidator\Czech;
use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\BankAccount\IAccountNumberValidator;
use Model\Payment\BankAccount\IBankAccountImporter;
use Model\Payment\InvalidBankAccountNumberException;
use Skautis\Skautis;


class BankAccountImporter implements IBankAccountImporter
{

    /** @var Skautis */
    private $skautis;

    /** @var IAccountNumberValidator */
    private $numberValidator;

    /** @var Czech */
    private $parser;


    public function __construct(Skautis $skautis, IAccountNumberValidator $numberValidator, Czech $parser)
    {
        $this->skautis = $skautis;
        $this->numberValidator = $numberValidator;
        $this->parser = $parser;
    }


    /**
     * {@inheritDoc}
     */
    public function import(int $unitId): array
    {
        $accounts = $this->skautis->org->AccountAll([
            'ID_Unit' => $unitId,
            'IsValid' => TRUE,
        ]);

        $result = [];
        foreach ($accounts as $account) {
            try {
                $number = $this->parser->parseNumber($account->DisplayName);
                $result[] = new AccountNumber($number[0], $number[1], $number[2], $this->numberValidator);
            } catch (InvalidBankAccountNumberException $e) {
                // Skip invalid bank accounts
            }
        }
        return $result;
    }

}
