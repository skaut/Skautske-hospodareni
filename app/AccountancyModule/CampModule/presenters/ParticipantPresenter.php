<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use App\AccountancyModule\ExcelResponse;
use App\AccountancyModule\ParticipantTrait;
use Model\Auth\Resources\Camp;
use Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use Model\Event\Commands\Camp\ActivateAutocomputedParticipants;
use Model\Event\SkautisCampId;
use Model\ExcelService;
use Model\ExportService;
use Model\Services\PdfRenderer;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Utils\Strings;
use Skautis\Wsdl\PermissionException;
use function date;
use function in_array;

class ParticipantPresenter extends BasePresenter
{
    use ParticipantTrait;

    public function __construct(ExportService $export, ExcelService $excel, PdfRenderer $pdf)
    {
        parent::__construct();
        $this->exportService = $export;
        $this->excelService  = $excel;
        $this->pdf           = $pdf;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->traitStartup();
        $this->eventService     = $this->context->getService('campService');
        $this->isAllowRepayment = true;
        $this->isAllowIsAccount = true;

        $this->isAllowParticipantInsert = $this->authorizator->isAllowed(Camp::ADD_PARTICIPANT, $this->aid);
        $this->isAllowParticipantDelete = $this->authorizator->isAllowed(Camp::REMOVE_PARTICIPANT, $this->aid);
        $this->isAllowParticipantUpdate = $this->authorizator->isAllowed(Camp::UPDATE_PARTICIPANT, $this->aid);

        $this->template->setParameters([
            'isAllowParticipantInsert' => $this->isAllowParticipantInsert,
            'isAllowParticipantDelete' => $this->isAllowParticipantDelete,
            'isAllowParticipantUpdate' => $this->isAllowParticipantUpdate,
            'isAllowRepayment' => $this->isAllowRepayment,
            'isAllowIsAccount' => $this->isAllowIsAccount,
        ]);
    }

    public function renderDefault(int $aid, ?int $uid = null, bool $dp = false, ?string $sort = null, bool $regNums = false) : void
    {
        $authorizator = $this->authorizator;

        if (! $authorizator->isAllowed(Camp::ACCESS_PARTICIPANTS, $aid)) {
            $this->flashMessage('Nemáte právo prohlížeč účastníky', 'danger');
            $this->redirect('Default:');
        }

        $this->traitDefault($dp, $sort, $regNums);

        $this->template->setParameters([
            'isAllowParticipantDetail' => $authorizator->isAllowed(Camp::ACCESS_PARTICIPANT_DETAIL, $aid),
            'isAllowParticipantUpdateLocal' => $this->isAllowParticipantDelete,
            'missingAvailableAutoComputed' => ! $this->event->isRealAutoComputed() && $authorizator->isAllowed(Camp::SET_AUTOMATIC_PARTICIPANTS_CALCULATION, $aid),
        ]);

        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    /**
     * @param int|float|string $value
     *
     * @throws AbortException
     * @throws BadRequestException
     */
    public function actionEditField(?int $aid = null, ?int $id = null, ?string $field = null, $value = null) : void
    {
        if ($aid === null || $id === null || $field === null || $value === null) {
            throw new BadRequestException();
        }

        if (! $this->isAllowParticipantUpdate) {
            $this->flashMessage('Nemáte oprávnění měnit účastníkův jejich údaje.', 'danger');
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect('Default:');
            }
        }

        //@todo: add privileges check to eventId

        if (! in_array($field, ['days', 'payment', 'repayment', 'isAccount'])) {
            $this->payload->message = 'Error';
            $this->sendPayload();
        }
        $this->eventService->getParticipants()->update($id, $aid, [$field => $value]);
        $this->payload->message = 'Success';
        $this->sendPayload();
    }

    public function handleActivateAutocomputedParticipants(int $aid) : void
    {
        $this->commandBus->handle(new ActivateAutocomputedParticipants(new SkautisCampId($aid)));
        $this->flashMessage('Byl aktivován automatický výpočet seznamu osobodnů.');
        $this->redirect('this');
    }

    public function actionExportExcel(int $aid) : void
    {
        try {
            $participantsDTO = $this->queryBus->handle(new CampParticipantListQuery($this->event->getId()));
            $spreadsheet     = $this->excelService->getCampParticipants($participantsDTO);
            $this->sendResponse(new ExcelResponse(Strings::webalize($this->event->getDisplayName()) . '-' . date('Y_n_j'), $spreadsheet));
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! (' . $ex->getMessage() . ')', 'danger');
            $this->redirect('default', ['aid' => $aid]);
        }
    }
}
