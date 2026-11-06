<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\Dialog;
use App\Model\DTO\Payment\Payment;
use App\Model\Payment\EmailType;
use App\Model\Payment\InvalidVariableSymbol;
use App\Model\Payment\Mailing\PaymentEmailQueue;
use App\Model\Payment\MissingVariableSymbol;
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

    public function __construct(private int $groupId, private PaymentEmailQueue $emailQueue, private PaymentService $paymentService)
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
            $this->getPresenter()->redrawControl('flash');
            $this->redrawControl();

            return;
        } catch (InvalidVariableSymbol $exception) {
            $this->flashMessage('Nelze vygenerovat následující VS: \''.$exception->getInvalidValue().'\'', 'danger');
            $this->getPresenter()->redrawControl('flash');
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

    /** The e-mails wait in the queue (PaymentEmailQueue). */
    private function sendPaymentInfoEmails(): void
    {
        $type = EmailType::get(EmailType::PAYMENT_INFO);
        $paymentIds = array_map(static fn (Payment $payment): int => $payment->getId(), $this->payments());
        $dispatch = $this->emailQueue->send($this->groupId, $type, $paymentIds);

        foreach (PaymentEmailMessages::forDispatch($dispatch, $type) as [$message, $flashType]) {
            $this->flashMessage($message, $flashType);
        }

        $this->onSuccess();
        $this->hide();
        $this->getPresenter()->redirect('this');
    }
}
