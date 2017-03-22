<?php

namespace App\AccountancyModule\UnitAccountModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class CashbookPresenter extends BasePresenter
{

    use \CashbookTrait;

    protected function startup() : void
    {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat jednotku", "danger");
            $this->redirect("Default:");
        }
        $this->entityService = $this->context->getService("unitAccountService");

        /**
         * $object potřebuje šablona cashbook/table.latte
         */
        $this->template->object = $this->entityService->event->get($this->aid);
        if (!$this->isReadable) {
            $this->flashMessage("Nemáš oprávnění číst data jednotky", "danger");
            $this->redirect("Default:");
        }

        $this->event = \Nette\Utils\ArrayHash::from(["ID" => $this->aid, "prefix" => "", "DisplayName" => "jednotka"]);

        $this->template->unitPairs = $this->unitService->getReadUnits($this->user);
    }

    public function actionDefault($aid, $pid = NULL, $dp = FALSE) : void
    {
        $items = $this['cashbookForm']['category']->getItems();
        unset($items[7]);//remove prevod do strediskove pokladny
        $this['cashbookForm']['category']->setItems($items);
    }

    public function renderDefault($aid, $pid = NULL, $dp = FALSE) : void
    {
        if ($pid !== NULL) {
            $this->isChitEditable($pid, $this->entityService);
            $form = $this['cashbookForm'];
            $chit = $this->entityService->chits->get($pid);
            $form['category']->setItems($this->entityService->chits->getCategoriesPairs($chit->ctype, $this->aid));
            $form->setDefaults([
                "pid" => $pid,
                "date" => $chit->date->format("j. n. Y"),
                "num" => $chit->num,
                "recipient" => $chit->recipient,
                "purpose" => $chit->purpose,
                "price" => $chit->priceText,
                "type" => $chit->ctype,
                "category" => $chit->category,
            ]);
        }

        $this->template->isInMinus = FALSE; //$this->context->unitAccountService->chits->eventIsInMinus($this->aid); // musi byt v before render aby se vyhodnotila az po handleru

        $this->render();
        $this->template->list = $this->entityService->chits->getAll($aid);
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

}
