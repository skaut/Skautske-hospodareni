<?php

namespace Model\Payment\QR;

use Model\Payment\Mailing\Payment;
use Model\Payment\InvalidBankAccountException;

class QRGenerator implements IQRGenerator
{

    public function generate(string $bankAccount, Payment $payment): string
    {
        $pattern = '#((?P<prefix>[0-9]+)-)?(?P<number>[0-9]+)/(?P<code>[0-9]{4})#';

        if(preg_match($pattern, $bankAccount, $account) !== 1) {
            throw new InvalidBankAccountException();
        }

        $params = [
            "accountNumber" => $account['number'],
            "bankCode" => $account['code'],
            "amount" => $payment->getAmount(),
            "currency" => "CZK",
            "size" => "200",
        ];
        if (array_key_exists('prefix', $account) && $account['prefix'] != '') {
            $params['accountPrefix'] = $account['prefix'];
        }
        if ($payment->getVariableSymbol() != '') {
            $params['vs'] = $payment->getVariableSymbol();
        }
        if ($payment->getConstantSymbol() != '') {
            $params['ks'] = $payment->getConstantSymbol();
        }
        if ($payment->getName() != '') {
            $params['message'] = $payment->getName();
        }

        return 'http://api.paylibo.com/paylibo/generator/czech/image?' . http_build_query($params);
    }

}
