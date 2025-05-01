<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Repayment;

use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Model\Payment\BankError;
use Model\Payment\Commands\Repayment\CreateRepayments;
use Model\Utils\MoneyFactory;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;

use function implode;
use function sprintf;

final class CreateRepaymentsHandler
{
    public function __construct(private ClientInterface $http)
    {
    }

    /**
     * @throws GuzzleException
     * @throws BankError
     */
    public function __invoke(CreateRepayments $command): void
    {
        try {
            $response = $this->http->request(
                'POST',
                'https://fioapi.fio.cz/v1/rest/import/',
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
                ],
            );
        } catch (ServerException $e) {
            throw BankError::fromServerException($e);
        } catch (ClientException $e) {
            if ($e->getCode() === 409) {
                throw BankError::fromMessage('Konflikt vstupních dat. Požadované data jsou již pravděpodobně v bance odeslané.', $e->getCode());
            }
        }

        if (! isset($response)) {
            throw new BankError('API neodeslalo požadovanou odpověď');
        }

        $this->parseResponse($response, $command);
    }

    private function buildRequestBody(CreateRepayments $command): string
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

    /**
     * @throws BankError
     * @throws Exception
     */
    private function parseResponse(ResponseInterface $response, CreateRepayments $command): void
    {
        $xmlResponse = $response->getBody()->getContents();

        $xml = new SimpleXMLElement($xmlResponse);

        $errorCode = (int) $xml->result->errorCode;
        if ($errorCode === 0) {
            return;
        }

        $repaymentMessages = [];
        $i                 = 1;
        foreach ($command->getRepayments() as $repayment) {
            $repaymentMessages[$i] = $repayment->getMessageForRecipient();
            $i++;
        }

        $errorMessages = [];

        foreach ($xml->ordersDetails->detail as $detail) {
            $detailId = (string) $detail['id'];

            foreach ($detail->messages->message as $message) {
                $messageStatus    = (string) $message['status'];
                $messageText      = (string) $message;
                $messageErrorCode = (int) $message['errorCode'];

                if ($messageStatus !== 'error' && $messageErrorCode === 0) {
                    continue;
                }

                $errorMessages[] = sprintf('Transakce "%s" obsahuje následující chybu : "%s"', $repaymentMessages[$detailId], $messageText);
            }
        }

        if (! empty($errorMessages)) {
            throw  BankError::fromMessage('API Error: ' . implode(' | ', $errorMessages), $errorCode);
        }
    }
}
