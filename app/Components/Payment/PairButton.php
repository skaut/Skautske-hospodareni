<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;
use App\Model\Bank\BankService;
use App\Model\Bank\Exception\BankTimeLimit;
use App\Model\Bank\Exception\BankTimeout;
use App\Model\Bank\Exception\BankWrongTokenAccount;
use App\Model\Bank\InvoiceBankService;
use App\Model\Google\InvalidOAuth;
use App\Model\Invoice\Repository\InvoiceSequenceRepository;
use App\Model\Payment\PaymentService;
use Component\Forms\BaseForm;

use function array_merge;

class PairButton extends BaseControl
{
    /** @var array<string, string> */
    protected array $css = [];

    private PairButtonScope $scope;

    public function __construct(
        private PaymentService $payments,
        private BankService $bankService,
        private InvoiceBankService $invoiceBankService,
        private InvoiceSequenceRepository $invoiceSequences,
        private PairButtonBankAccountSupport $bankAccountSupport,
    ) {
        $this->scope = new EmptyPairButtonScope();
        $style = 'primary';
        $this->css = [
            'wrap' => 'd-inline-block',
            'btn' => 'btn btn-sm btn-'.$style,
            'toggle' => 'btn btn-sm btn-'.$style.' dropdown-toggle',
            'menu' => 'dropdown-menu pairForm',
            'icon' => 'fi fi-rr-bank',
            'inputGroup' => 'input-group input-group-sm',
            'submit' => 'btn btn-sm btn-primary',
            'submitCol' => 'col-4',
        ];
    }

    /** @param array<string, string> $css */
    public function addCss(array $css): void
    {
        $this->css = array_merge($this->css, $css);
    }

    public function setCss(string $key, string $value): void
    {
        $this->css[$key] = $value;
    }

    public function handlePair(): void
    {
        $this->pair();
    }

    /**
     * Select groups to pair.
     *
     * @param int[] $groupIds
     */
    public function setGroups(array $groupIds): void
    {
        $this->scope = new GroupPairButtonScope(
            $this->payments,
            $this->bankService,
            $this->bankAccountSupport,
            $groupIds,
        );
    }

    /**
     * @param int[] $sequenceIds
     */
    public function setSequences(array $sequenceIds): void
    {
        $this->scope = new InvoiceSequencePairButtonScope(
            $this->invoiceBankService,
            $this->invoiceSequences,
            $this->bankAccountSupport,
            $sequenceIds,
        );
    }

    public function render(): void
    {
        $this->template->setParameters([
            'canPair' => $this->scope->canPair(),
            'itemsCount' => $this->scope->getItemsCount(),
            'scopeLabel' => 'úhrady',
            'disabledReason' => $this->scope->getDisabledReason(),
            'css' => $this->css,
        ]);
        $this->template->setFile(__DIR__.'/templates/PairButton.latte');
        $this->template->render();
    }

    public function renderLight(): void
    {
        $this->template->setParameters(['style' => 'light']);
        $this->render();
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addText('days', 'Počet dní', 2, 2)
            ->setDefaultValue((string) $this->scope->getDaysBackDefault())
            ->setRequired('Musíte vyplnit počet dní')
            ->addRule($form::MIN, 'Musíte zadat alespoň kladný počet dní', 1)
            ->setHtmlType('number');
        $form->addSubmit('pair', 'Párovat');

        $form->onSuccess[] = function ($form, $values): void {
            $this->pair((int) $values->days);
        };
        $this->redrawControl('form');

        return $form;
    }

    private function pair(?int $daysBack = null): void
    {
        try {
            foreach ($this->scope->pair($daysBack) as $message) {
                $this->presenter->flashMessage($message->message, $message->type);
            }
        } catch (BankTimeout) {
            $this->presenter->flashMessage(BankPairingUiMessages::TIMEOUT_MESSAGE, 'danger');
        } catch (BankTimeLimit) {
            $this->presenter->flashMessage(BankPairingUiMessages::TIME_LIMIT_MESSAGE, 'danger');
        } catch (BankWrongTokenAccount $e) {
            $this->presenter->flashMessage(BankPairingUiMessages::wrongTokenAccountMessage($e), 'danger');
        } catch (InvalidOAuth $exc) {
            $this->presenter->flashMessage($exc->getExplainedMessage(), 'danger');
        }

        $this->presenter->redirect('this');
    }

    public static function wrongTokenAccountMessage(BankWrongTokenAccount $exception): string
    {
        return BankPairingUiMessages::wrongTokenAccountMessage($exception);
    }
}
