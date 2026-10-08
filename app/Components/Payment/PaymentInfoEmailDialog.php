<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\Dialog;
use App\Model\Common\Services\CommandBus;
use App\Model\DTO\Payment\Payment;
use App\Model\Google\Exception\OAuthNotSet;
use App\Model\Google\InvalidOAuth;
use App\Model\Payment\Commands\Mailing\SendPaymentInfo;
use App\Model\Payment\InvalidBankAccount;
use App\Model\Payment\InvalidVariableSymbol;
use App\Model\Payment\MissingVariableSymbol;
use App\Model\Payment\PaymentClosed;
use App\Model\Payment\PaymentHasNoEmails;
use App\Model\Payment\PaymentService;
use Nette\Application\Attributes\Persistent;

use function array_filter;
use function array_map;
use function array_unique;
use function count;
use function explode;
use function implode;

/** @method void onSuccess() */
final class PaymentInfoEmailDialog extends Dialog
{
    /** @var callable[] */
    public array $onSuccess = [];

    /** Comma-separated payment IDs because persistent parameters do not support arrays. */
    #[Persistent]
    public string $paymentIds = '';

    public function __construct(private int $groupId, private CommandBus $commandBus, private PaymentService $paymentService)
    {
    }

    /** @param int[] $paymentIds */
    public function send(array $paymentIds): void
    {
        $this->paymentIds = implode(',', array_unique(array_map('intval', $paymentIds)));

        if ($this->paymentsWithoutVariableSymbol() === []) {
            $this->sendPaymentInfoEmails();

            return;
        }

        $this->show();
    }

    public function handleSendWithoutVariableSymbol(): void
    {
        $this->sendPaymentInfoEmails();
    }

    public function handleGenerateVariableSymbolsAndSend(): void
    {
        try {
            if ($this->paymentService->getNextVS($this->groupId) === null) {
                throw new MissingVariableSymbol();
            }

            $this->paymentService->generateVs($this->groupId);
        } catch (MissingVariableSymbol) {
            $this->flashMessage('VS nelze dogenerovat. Vyplňte nejdříve VS alespoň jedné platbě.', 'warning');
            $this->presenter->redrawControl('flash');
            $this->redrawControl();

            return;
        } catch (InvalidVariableSymbol $exception) {
            $this->flashMessage('Nelze vygenerovat následující VS: \''.$exception->getInvalidValue().'\'', 'danger');
            $this->presenter->redrawControl('flash');
            $this->redrawControl();

            return;
        }

        $this->sendPaymentInfoEmails();
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->setFile(__DIR__.'/templates/PaymentInfoEmailDialog.latte');
        $this->template->setParameters([
            'paymentsWithoutVariableSymbolCount' => count($this->paymentsWithoutVariableSymbol()),
        ]);
    }

    /** @return Payment[] */
    private function paymentsWithoutVariableSymbol(): array
    {
        return array_filter(
            $this->payments(),
            static fn (Payment $payment): bool => $payment->getVariableSymbol() === null,
        );
    }

    /** @return Payment[] */
    private function payments(): array
    {
        $payments = [];

        foreach ($this->paymentIds() as $paymentId) {
            $payment = $this->paymentService->findPayment($paymentId);
            if ($payment !== null && $payment->getGroupId() === $this->groupId) {
                $payments[] = $payment;
            }
        }

        return $payments;
    }

    /** @return int[] */
    private function paymentIds(): array
    {
        return array_map(
            'intval',
            array_filter(explode(',', $this->paymentIds), 'is_numeric'),
        );
    }

    private function sendPaymentInfoEmails(): void
    {
        $sentCount = 0;

        foreach ($this->payments() as $payment) {
            try {
                $this->commandBus->handle(new SendPaymentInfo($payment->getId()));
                ++$sentCount;
            } catch (OAuthNotSet) {
                $this->flashMessage(EmailButton::NO_MAILER_MESSAGE, 'warning');
            } catch (InvalidBankAccount) {
                $this->flashMessage(EmailButton::NO_BANK_ACCOUNT_MESSAGE, 'warning');
            } catch (InvalidOAuth $exception) {
                $this->flashMessage($exception->getExplainedMessage(), 'danger');
            } catch (PaymentClosed|PaymentHasNoEmails $exception) {
                $this->flashMessage($exception->getMessage(), 'warning');
            }
        }

        if ($sentCount === 1) {
            $this->flashMessage('1 informační e-mail odeslán', 'success');
        } elseif ($sentCount > 1) {
            $this->flashMessage($sentCount.' informačních e-mailů odesláno', 'success');
        }

        $this->onSuccess();
        $this->hide();
        $this->presenter->redirect('this');
    }
}
