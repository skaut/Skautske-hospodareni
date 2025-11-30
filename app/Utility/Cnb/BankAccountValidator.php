<?php

declare(strict_types=1);

namespace Utility\Cnb;

use BankAccountValidator\Czech;
use BankAccountValidator\MissingBankCodesFileException;

class BankAccountValidator extends Czech
{
    protected array $fullBankInfo;

    public function __construct($cnfFile = __DIR__.'/../../../vendor/heureka/bank-account-validator/cnf/czech-bank-codes.csv')
    {
        parent::__construct($cnfFile);
        $this->fullBankInfo = $this->parse($cnfFile);
    }

    /**
     * @return array<string, BankInfoDTO>
     * @throws MissingBankCodesFileException
     */
    private function parse($codesFile): array
    {
        if (! is_file($codesFile)) {
            throw new MissingBankCodesFileException('Czech bank codes CSV file is not valid. '.$codesFile);
        }
        $data = str_getcsv(file_get_contents($codesFile), "\n", '"', '');
        array_shift($data);
        $validBank = [];
        foreach ($data as &$row) {
            $row = str_getcsv($row, ';', '"', '');
            if ($row[0] && $row[1]) {
                $validBank[(string) $row[0]] = new BankInfoDTO($row[0], $row[1], $row[2] ?? null, $row[3] ?? null);
            }
        }

        return $validBank;
    }

    public function getBankBics(): array
    {
        return array_map(function ($bankInfo) {
            return $bankInfo->getBic();
        }, $this->fullBankInfo);
    }

    /**
     * @throws BankNotFoundException
     */
    public function getBankInfo(string $bankCode): BankInfoDTO
    {
        if (isset($this->fullBankInfo[$bankCode])) {
            return $this->fullBankInfo[$bankCode];
        } else {
            throw new BankNotFoundException('Bank code not found');
        }
    }
}
