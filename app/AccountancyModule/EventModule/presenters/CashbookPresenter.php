<?php

namespace App\AccountancyModule\EventModule;

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Category;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\ObjectType;

class CashbookPresenter extends BasePresenter
{

    use \CashbookTrait;

    protected function startup(): void
    {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat akci", "danger");
            $this->redirect("Event:");
        }
        $this->entityService = $this->eventService;

        $ev_state = $this->event->ID_EventGeneralState == "draft" ? TRUE : FALSE;
        $this->isEditable = $this->template->isEditable = $ev_state && array_key_exists("EV_ParticipantGeneral_UPDATE_EventGeneral", $this->availableActions);
        $this->template->missingCategories = FALSE;
    }

    public function renderDefault(int $aid, $pid = NULL, $dp = FALSE): void
    {
        if ($pid !== NULL) {
            $this->editChit($pid);
        }

        $this->template->isInMinus = $this->eventService->chits->eventIsInMinus($this->getCurrentUnitId()); // musi byt v before render aby se vyhodnotila az po handleru
        $this->template->list = $this->eventService->chits->getAll($aid);
        $this->template->linkImportHPD = $this->link("importHpd", ["aid" => $aid]);
        $this->fillTemplateVariables();
        if ($this->isAjax()) {
            $this->redrawControl("contentSnip");
        }

        $this->template->cashbookWithCategoriesAllowed = TRUE;
    }

    public function actionExportExcelWithCategories(int $aid): void
    {
        $this->excelService->getCashbookWithCategories(
            $this->entityService,
            $aid,
            ObjectType::get(ObjectType::EVENT)
        );
        $this->terminate();
    }

    public function actionImportHpd($aid): void
    {
        $this->editableOnly();

        // @TODO move logic to specific command handler
        $totalPayment = $this->eventService->participants->getTotalPayment($this->aid);
        $func = $this->eventService->event->getFunctions($this->aid);
        $date = $this->eventService->event->get($aid)->StartDate;
        $accountant = ($func[2]->ID_Person != NULL) ? new Recipient($func[2]->Person): NULL;

        $cashbookId = $this->eventService->chits->getCashbookIdFromSkautisId($this->aid);

        $this->commandBus->handle(
            new AddChitToCashbook(
                $cashbookId,
                NULL,
                new Date($date),
                $accountant,
                new Amount((string) $totalPayment),
                'účastnické příspěvky',
                Category::EVENT_PARTICIPANTS_INCOME_CATEGORY_ID
            )
        );

        $this->flashMessage("Účastníci byli importováni");
        $this->redirect("default", ["aid" => $aid]);
    }

}
