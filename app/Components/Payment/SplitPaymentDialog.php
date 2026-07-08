<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\Dialog;
use App\Model\Common\Services\CommandBus;
use App\Model\DTO\Payment\Payment;
use App\Model\Payment\Commands\Payment\SplitPayment;
use App\Model\Payment\Commands\Payment\SplitPaymentPart;
use App\Model\Payment\InvalidPaymentSplit;
use App\Model\Payment\PaymentClosed;
use App\Model\Payment\PaymentNotFound;
use App\Model\Payment\PaymentService;
use App\Model\Payment\VariableSymbol;
use App\Model\Payment\VariableSymbolCollision;
use Component\Forms\BaseForm;
use Component\Forms\VariableSymbolControl;
use Kdyby\Replicator\Container as ReplicatorContainer;
use LogicException;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;

use function array_map;
use function array_sum;
use function array_values;
use function count;
use function iterator_to_array;
use function round;

/** @method void onSuccess() */
final class SplitPaymentDialog extends Dialog
{
    /** @var callable[] */
    public array $onSuccess = [];

    /** @persistent */
    public int $paymentId = -1;

    public function __construct(
        private int $groupId,
        private CommandBus $commandBus,
        private PaymentService $paymentService,
    ) {
    }

    public function handleOpen(int $paymentId = -1): void
    {
        $this->paymentId = $paymentId;
        $this->show();
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->setFile(__DIR__.'/templates/SplitPaymentDialog.latte');
        $this->template->setParameters([
            'payment' => $this->payment(),
            'customClasses' => 'modal-lg',
        ]);
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $sourcePayment = $this->payment();

        $splits = $form->addDynamic('splits', function (Container $container) use ($sourcePayment): void {
            $variableSymbol = new VariableSymbolControl('Variabilní symbol');
            $variableSymbol->setRequired('Musíte vyplnit variabilní symbol');
            if ($sourcePayment?->getVariableSymbol() !== null) {
                $variableSymbol->setDefaultValue((string) $sourcePayment->getVariableSymbol());
            }

            $container['variableSymbol'] = $variableSymbol;

            $container->addText('amount', 'Částka')
                ->setNullable()
                ->setRequired('Musíte vyplnit částku')
                ->addRule(Form::FLOAT, 'Částka musí být zadaná jako číslo')
                ->addRule(Form::MIN, 'Částka musí být větší než 0', 0.01);

            $container->addText('note', 'Poznámka')
                ->setNullable()
                ->setMaxLength(64)
                ->setRequired(false);

            $container->addSubmit('remove', 'Odebrat')
                ->setValidationScope([])
                ->setHtmlAttribute('class', 'btn btn-outline-danger btn-sm ajax')
                ->onClick[] = function (SubmitButton $button): void {
                    $this->removeSplit($button);
                };
        }, 1, true);

        $splits->addSubmit('addSplit', '+')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-outline-secondary ajax')
            ->setHtmlAttribute('title', 'Přidat další variabilní symbol a částku')
            ->setHtmlAttribute('aria-label', 'Přidat další variabilní symbol a částku')
            ->setHtmlAttribute('data-test', 'payment-split-add')
            ->onClick[] = function () use ($splits): void {
                $splits->createOne();
                $this->redrawControl();
            };

        $form->addSubmit('send', 'Rozdělit platbu');

        $form->onSubmit[] = function (): void {
            $this->redrawControl();
        };
        $form->onValidate[] = function (BaseForm $form, ArrayHash $values): void {
            if ($form->isSubmitted() !== $form['send']) {
                return;
            }

            $this->validateSplit($form, $values);
        };
        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values): void {
            if ($form->isSubmitted() !== $form['send']) {
                return;
            }

            $this->splitPayment($form, $values);
        };

        return $form;
    }

    private function removeSplit(SubmitButton $button): void
    {
        $container = $button->getParent();
        $replicator = $container->getParent();

        if (! $container instanceof Container || ! $replicator instanceof ReplicatorContainer) {
            throw new LogicException('Nepodařilo se odebrat část rozdělení platby.');
        }

        $replicator->remove($container, true);
        $this->redrawControl();
    }

    private function validateSplit(BaseForm $form, ArrayHash $values): void
    {
        $payment = $this->payment();

        if ($payment === null) {
            $form->addError('Zadaná platba neexistuje.');

            return;
        }

        if ($payment->isClosed()) {
            $form->addError('Uzavřenou platbu nelze rozdělit.');

            return;
        }

        $parts = iterator_to_array($values->splits);
        if ($parts === []) {
            $form->addError('Zadejte alespoň jednu část platby.');

            return;
        }

        $splitAmountInCents = array_sum(array_map(
            fn (ArrayHash $part): int => $this->toCents((float) $part->amount),
            $parts,
        ));

        if ($splitAmountInCents > $this->toCents($payment->getAmount())) {
            $form->addError('Součet dělených částek nesmí být větší než původní částka.');
        }

        $sourceVariableSymbol = $payment->getVariableSymbol();
        $remainingSourceAmountInCents = $this->toCents($payment->getAmount()) - $splitAmountInCents;
        $variableSymbols = [];

        foreach ($parts as $part) {
            $variableSymbol = $part->variableSymbol;
            if (! $variableSymbol instanceof VariableSymbol) {
                continue;
            }

            $partAmountInCents = $this->toCents((float) $part->amount);

            if (
                $sourceVariableSymbol !== null
                && $sourceVariableSymbol->toInt() === $variableSymbol->toInt()
                && $remainingSourceAmountInCents === $partAmountInCents
            ) {
                $form->addError('Stejný variabilní symbol lze při rozdělení použít jen u rozdílných částek.');
            }

            $variableSymbolValue = (string) $variableSymbol;
            if (isset($variableSymbols[$variableSymbolValue])) {
                $form->addError('Každá nová platba musí mít jiný variabilní symbol.');
            }

            $variableSymbols[$variableSymbolValue] = true;
        }
    }

    private function splitPayment(BaseForm $form, ArrayHash $values): void
    {
        $parts = array_values(array_map(
            fn (ArrayHash $part): SplitPaymentPart => new SplitPaymentPart(
                $part->variableSymbol,
                (float) $part->amount,
                $part->note,
            ),
            iterator_to_array($values->splits),
        ));

        try {
            $this->commandBus->handle(new SplitPayment($this->paymentId, $parts));
        } catch (PaymentNotFound) {
            $form->addError('Zadaná platba neexistuje.');

            return;
        } catch (InvalidPaymentSplit|PaymentClosed|VariableSymbolCollision $exception) {
            $form->addError($exception->getMessage());

            return;
        }

        $this->flashMessage(count($parts) === 1 ? 'Platba byla rozdělena.' : 'Platba byla rozdělena na více plateb.', 'success');
        $this->onSuccess();
        $this->hide();
    }

    private function payment(): ?Payment
    {
        if ($this->paymentId === -1) {
            return null;
        }

        $payment = $this->paymentService->findPayment($this->paymentId);

        if ($payment === null || $payment->getGroupId() !== $this->groupId) {
            return null;
        }

        return $payment;
    }

    private function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
