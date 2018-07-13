<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use Model\BankService;
use Model\BankTimeLimitException;
use Model\BankTimeoutException;
use Model\DTO\Payment\Group;
use Model\Payment\BankAccountService;
use Model\PaymentService;
use function array_filter;
use function array_map;
use function count;

class PairButton extends BaseControl
{
    public const TIMEOUT_MESSAGE    = 'Nepodařilo se připojit k bankovnímu serveru. Zkontrolujte svůj API token pro přístup k účtu.';
    public const TIME_LIMIT_MESSAGE = 'Mezi dotazy na bankovnictví musí být prodleva 1 minuta!';

    /** @var BankService */
    private $model;

    /** @var PaymentService */
    private $payments;

    /** @var BankAccountService */
    private $bankAccounts;

    /** @var int[] */
    private $groupIds = [];

    public function __construct(PaymentService $payments, BankService $model, BankAccountService $bankAccounts)
    {
        parent::__construct();
        $this->model        = $model;
        $this->payments     = $payments;
        $this->bankAccounts = $bankAccounts;
    }

    public function handlePair() : void
    {
        $this->pair();
    }

    /**
     * Select groups to pair
     *
     * @param int[] $groupIds
     */
    public function setGroups(array $groupIds) : void
    {
        $this->groupIds = $groupIds;
    }

    public function render() : void
    {
        $this->template->canPair     = $this->canPair();
        $this->template->groupsCount = count($this->groupIds);
        $this->template->setFile(__DIR__ . '/templates/PairButton.latte');
        $this->template->render();
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm(true);

        $form->addText('days', 'Počet dní', 2, 2)
            ->setDefaultValue(BankService::DAYS_BACK_DEFAULT)
            ->setRequired('Musíte vyplnit počet dní')
            ->addRule($form::MIN, 'Musíte zadat alespoň kladný počet dní', 1)
            ->setType('number');
        $form->addSubmit('pair', 'Párovat')->setAttribute('class', 'ajax');

        $form->onSuccess[] = function ($form, $values) : void {
            $this->pair($values->days);
        };
        $this->redrawControl('form');
        return $form;
    }


    private function canPair() : bool
    {
        if (empty($this->groupIds)) {
            return false;
        }

        $groups         = $this->payments->findGroupsByIds($this->groupIds);
        $bankAccountIds = array_map(
            function (Group $g) {
                return $g->getBankAccountId();
            },
            $groups
        );
        $bankAccountIds = array_filter($bankAccountIds);

        $bankAccounts = $this->bankAccounts->findByIds($bankAccountIds);

        foreach ($bankAccounts as $account) {
            if ($account->getToken() !== null) {
                return true;
            }
        }

        return false;
    }


    private function pair(?int $daysBack = null) : void
    {
        $error = null;
        try {
            $pairedCount = $this->model->pairAllGroups($this->groupIds, $daysBack);
        } catch (BankTimeoutException $exc) {
            $error = self::TIMEOUT_MESSAGE;
        } catch (BankTimeLimitException $exc) {
            $error = self::TIME_LIMIT_MESSAGE;
        }

        if ($error !== null) {
            $this->presenter->flashMessage($error, 'danger');
        } elseif (isset($pairedCount) && $pairedCount > 0) {
            $this->presenter->flashMessage("Platby byly spárovány ($pairedCount)", 'success');
        } else {
            $this->presenter->flashMessage('Žádné platby nebyly spárovány');
        }

        $this->redirect('this');
    }
}
