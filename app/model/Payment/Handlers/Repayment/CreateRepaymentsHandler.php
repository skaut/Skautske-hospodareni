<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Repayment;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ServerException;
use Model\Payment\BankError;
use Model\Payment\Commands\Repayment\CreateRepayments;
use Model\Utils\MoneyFactory;

final class CreateRepaymentsHandler
{
    /** @var ClientInterface */
    private $http;

    public function __construct(ClientInterface $http)
    {
        $this->http = $http;
    }

    public function __invoke(CreateRepayments $command) : void
    {
        try {
            $this->http->request(
                'POST',
                'https://www.fio.cz/ib_api/rest/import/',
                [
                    'multipart' => [
                        [
                            'name' => 'token',
                            'contents' => $command->getToken(),
                        ],
                        ['name' => 'type', 'contents' => 'xml'],
                        [
                            'name' => 'file',
                            'contents' => $this->buildRequestBody($command),
                            'filename' => 'request.xml',
                        ],
                        [
                            'name' => 'lng',
                            'contents' => 'cs',
                        ],
                    ],
                    'timeout' => 60,
                ]
            );
        } catch (ServerException $e) {
            throw BankError::fromServerException($e);
        }
    }

    private function buildRequestBody(CreateRepayments $command) : string
    {
        $ret = '<?xml version="1.0" encoding="UTF-8"?><Import xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.fio.cz/schema/importIB.xsd"> <Orders>';

        foreach ($command->getRepayments() as $r) {
            $ret .= '<DomesticTransaction>';
            $ret .= '<accountFrom>' . $command->getSourceAccount()->getNumberWithPrefix() . '</accountFrom>';
            $ret .= '<currency>CZK</currency>';
            $ret .= '<amount>' . MoneyFactory::toFloat($r->getAmount()) . '</amount>';
            $ret .= '<accountTo>' . $r->getTargetAccount()->getNumberWithPrefix() . '</accountTo>';
            $ret .= '<bankCode>' . $r->getTargetAccount()->getBankCode() . '</bankCode>';
            $ret .= '<date>' . $command->getDate()->format('Y-m-d') . '</date>';
            $ret .= '<messageForRecipient>' . $r->getMessageForRecipient() . '</messageForRecipient>';
            $ret .= '<comment></comment>';
            $ret .= '<paymentType>431001</paymentType>';
            $ret .= '</DomesticTransaction>';
        }

        $ret .= '</Orders></Import>';

        return $ret;
    }
}
