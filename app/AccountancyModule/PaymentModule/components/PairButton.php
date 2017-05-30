<?php

namespace App\AccountancyModule\PaymentModule\Components;

use App\Forms\BaseForm;
use Dibi\Row;
use Model\BankService;
use Nette\Application\UI\Control;

class PairButton extends Control
{

    /** @var int */
    private $unitId;

    /** @var BankService */
    private $model;

    /** @var Row|NULL */
    private $bankInfo;

    /** @var int[] */
    private $groupIds = [];

    public function __construct(int $unitId, BankService $model)
    {
        parent::__construct();
        $this->unitId = $unitId;
        $this->model = $model;

        $this->bankInfo = $this->model->getInfo($unitId);
    }

    public function handlePair(): void
    {
        $this->pair();
    }

    /**
     * Select groups to pair and related unit
     * @param int[] $groupIds
     * @param int|NULL $unitId
     */
    public function setGroups(array $groupIds, ?int $unitId = NULL): void
    {
        $this->groupIds = $groupIds;
        $this->unitId = $unitId;
        if($unitId !== NULL) {
            $this->bankInfo = $this->model->getInfo($unitId);
        }
    }

    public function render(): void
    {
        $this->template->canPair = ($this->unitId === NULL && !empty($this->groupIds)) || isset($this->bankInfo->token);
        $this->template->setFile(__DIR__."/templates/PairButton.latte");
        $this->template->render();
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm(TRUE);

        $days = $this->bankInfo->daysback ?? 0;

        $form->addText('days', 'Počet dní', 2, 2)
            ->setDefaultValue($days)
            ->setRequired('Musíte vyplnit počet dní')
            ->addRule($form::MIN, 'Musíte zadat alespoň počet dní z nastavení: %d', $days)
            ->setType('number');
        $form->addSubmit('pair', 'Párovat')->setAttribute('class', 'ajax');

        $form->onSuccess[] = function ($form, $values): void {
            $this->pair($values->days);
        };
        $this->redrawControl('form');
        return $form;
    }


    private function pair(?int $daysBack = NULL): void
    {
        $error = NULL;
        try {
            $pairedCount = $this->model->pairAllGroups($this->groupIds, $daysBack);
        } catch (\Model\BankTimeoutException $exc) {
            $error = "Nepodařilo se připojit k bankovnímu serveru. Zkontrolujte svůj API token pro přístup k účtu.";
        } catch (\Model\BankTimeLimitException $exc) {
            $error = "Mezi dotazy na bankovnictví musí být prodleva 1 minuta!";
        }

        if ($error !== NULL) {
            $this->presenter->flashMessage($error, "danger");
        } elseif (isset($pairedCount) && $pairedCount > 0) {
            $this->presenter->flashMessage("Platby byly spárovány ($pairedCount)", "success");
        } else {
            $this->presenter->flashMessage("Žádné platby nebyly spárovány");
        }

        $this->redirect("this");
    }

}
