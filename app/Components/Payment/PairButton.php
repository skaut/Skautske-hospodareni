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
    /**
     * @method void                    onSuccess()
     * @var    array<callable(): void>
     */
    public array $onSuccess = [];

    /** @var array<string, string> */
    protected array $css = [];

    private PairButtonScope $scope;

    private bool $ajaxEnabled = false;

    public function __construct(
        private PaymentService $payments,
        private BankService $bankService,
        private InvoiceBankService $invoiceBankService,
        private InvoiceSequenceRepository $invoiceSequences,
        private PairButtonBankAccountSupport $bankAccountSupport,
    ) {
        $this->scope = new EmptyPairButtonScope();
        $this->css = array_merge([
            'wrap' => 'd-inline-block',
            'menu' => 'dropdown-menu dropdown-menu-end p-3',
            'icon' => 'fi fi-rr-bank',
            'inputGroup' => 'input-group input-group-sm',
            'submit' => 'btn btn-sm btn-primary',
            'submitCol' => 'col-4',
        ], self::buttonCssForStyle('primary'));
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

    public function enableAjax(): void
    {
        $this->ajaxEnabled = true;
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
            'ajaxEnabled' => $this->ajaxEnabled,
        ]);
        $this->template->setFile(__DIR__.'/templates/PairButton.latte');
        $this->template->render();
    }

    public function renderLight(): void
    {
        $this->addCss(self::buttonCssForStyle('light'));
        $this->render();
    }

    /** @return array<string, string> */
    private static function buttonCssForStyle(string $style): array
    {
        return [
            'btn' => 'btn btn-sm btn-'.$style,
            'toggle' => 'btn btn-sm btn-'.$style.' dropdown-toggle',
        ];
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

        if ($this->ajaxEnabled && $this->presenter->isAjax()) {
            $this->onSuccess();

            return;
        }

        $this->presenter->redirect('this');
    }

    public static function wrongTokenAccountMessage(BankWrongTokenAccount $exception): string
    {
        return BankPairingUiMessages::wrongTokenAccountMessage($exception);
    }
}
