<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Repayment;

use Exception;
use FioApi\Exceptions\UnexpectedPaymentOrderValueException;
use FioApi\Upload\Entity\PaymentOrderCzech;
use FioApi\Upload\Entity\UploadResponse;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Model\Bank\Fio\IUploaderFactory;
use Model\Payment\BankError;
use Model\Payment\Commands\Repayment\CreateRepayments;
use Model\Payment\Repayment;
use Model\Utils\MoneyFactory;
use SimpleXMLElement;

use function implode;
use function sprintf;

final class CreateRepaymentsHandler
{
    public function __construct(private IUploaderFactory $uploaderFactory)
    {
    }

    /**
     * @throws GuzzleException
     * @throws BankError
     */
    public function __invoke(CreateRepayments $command): void
    {
        try {
            $uploader = $this->uploaderFactory->create(
                $command->getToken(),
                $this->formatSourceAccount($command),
            );

            foreach ($command->getRepayments() as $repayment) {
                $uploader->addPaymentOrder($this->createPaymentOrder($command, $repayment));
            }

            $response = $uploader->uploadPaymentOrders();
        } catch (ServerException $e) {
            throw BankError::fromServerException($e);
        } catch (ClientException $e) {
            if ($e->getCode() === 409) {
                throw BankError::fromMessage('Konflikt vstupnich dat. Pozadovana data jsou jiz pravdepodobne v bance odeslana.', $e->getCode());
            }

            throw $e;
        } catch (UnexpectedPaymentOrderValueException $e) {
            throw BankError::fromMessage($e->getMessage(), 0);
        }

        $this->parseResponse($response, $command);
    }

    private function formatSourceAccount(CreateRepayments $command): string
    {
        return ($command->getSourceAccount()->getPrefix() ?? '').$command->getSourceAccount()->getNumber();
    }

    private function createPaymentOrder(CreateRepayments $command, Repayment $repayment): PaymentOrderCzech
    {
        return new PaymentOrderCzech(
            'CZK',
            MoneyFactory::toFloat($repayment->getAmount()),
            $repayment->getTargetAccount()->getNumberWithPrefix(),
            $repayment->getTargetAccount()->getBankCode(),
            $command->getDate()->toNative(),
            null,
            null,
            null,
            $repayment->getMessageForRecipient(),
            null,
            null,
            PaymentOrderCzech::PAYMENT_TYPE_STANDARD,
        );
    }

    /**
     * @throws BankError
     * @throws Exception
     */
    private function parseResponse(UploadResponse $response, CreateRepayments $command): void
    {
        $xml = $response->getXml();

        $errorCode = (int) $xml->result->errorCode;
        if ($errorCode === 0) {
            return;
        }

        $repaymentMessages = [];
        $i = 1;
        foreach ($command->getRepayments() as $repayment) {
            $repaymentMessages[$i] = $repayment->getMessageForRecipient();
            ++$i;
        }

        $errorMessages = [];
        $details = $xml->ordersDetails?->detail;

        if ($details instanceof SimpleXMLElement) {
            foreach ($details as $detail) {
                $detailId = (string) $detail['id'];
                $messages = $detail->messages?->message;

                if (! $messages instanceof SimpleXMLElement) {
                    continue;
                }

                foreach ($messages as $message) {
                    $messageStatus = (string) $message['status'];
                    $messageText = (string) $message;
                    $messageErrorCode = (int) $message['errorCode'];

                    if ($messageStatus !== 'error' && $messageErrorCode === 0) {
                        continue;
                    }

                    $errorMessages[] = sprintf(
                        'Transakce "%s" obsahuje nasledujici chybu: "%s"',
                        $repaymentMessages[$detailId] ?? $detailId,
                        $messageText,
                    );
                }
            }
        }

        if (! empty($errorMessages)) {
            throw BankError::fromMessage('API Error: '.implode(' | ', $errorMessages), $errorCode);
        }

        throw BankError::fromMessage('API Error: '.((string) ($xml->result->message ?? $xml->result->status ?? 'Neznama chyba bankovniho API')), $errorCode);
    }
}
