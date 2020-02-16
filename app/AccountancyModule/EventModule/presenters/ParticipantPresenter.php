<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\Components\Participants\ParticipantList;
use App\AccountancyModule\Components\Participants\PersonPicker;
use App\AccountancyModule\ExcelResponse;
use App\AccountancyModule\Factories\Participants\IParticipantListFactory;
use App\AccountancyModule\Factories\Participants\IPersonPickerFactory;
use Assert\Assertion;
use Model\Auth\Resources\Event;
use Model\Cashbook\Commands\Cashbook\AddEventParticipant;
use Model\Cashbook\Commands\Cashbook\CreateEventParticipant;
use Model\Cashbook\Commands\Cashbook\RemoveEventParticipant;
use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\DTO\Participant\NonMemberParticipant;
use Model\DTO\Participant\Participant;
use Model\DTO\Participant\UpdateParticipant;
use Model\EventEntity;
use Model\ExcelService;
use Model\ExportService;
use Model\Participant\Payment\EventType;
use Model\Services\PdfRenderer;
use Nette\Utils\Strings;
use Skautis\Wsdl\PermissionException;
use function date;
use function in_array;
use function printf;

class ParticipantPresenter extends BasePresenter
{
    /** @var bool */
    private $canAddParticipants;

    /** @var ExportService */
    private $exportService;

    /** @var ExcelService */
    private $excelService;

    /** @var PdfRenderer     */
    private $pdf;

    /** @var IPersonPickerFactory */
    private $personPickerFactory;

    /** @var IParticipantListFactory */
    private $participantListFactory;

    /** @var bool */
    private $isAllowParticipantUpdate;

    /** @var bool */
    private $isAllowParticipantDelete;

    /** @var EventEntity */
    private $eventService;

    public function __construct(
        ExportService $export,
        ExcelService $excel,
        PdfRenderer $pdf,
        IPersonPickerFactory $personPickerFactory,
        IParticipantListFactory $participantListFactory
    ) {
        parent::__construct();
        $this->exportService          = $export;
        $this->excelService           = $excel;
        $this->pdf                    = $pdf;
        $this->personPickerFactory    = $personPickerFactory;
        $this->participantListFactory = $participantListFactory;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->eventService = $this->context->getService('eventService');

        $isDraft      = $this->event->getState() === 'draft';
        $authorizator = $this->authorizator;

        $this->isAllowParticipantDelete = $isDraft && $authorizator->isAllowed(Event::REMOVE_PARTICIPANT, $this->aid);
        $this->canAddParticipants       = $isDraft && $authorizator->isAllowed(Event::UPDATE_PARTICIPANT, $this->aid);
        $this->isAllowParticipantUpdate = $this->canAddParticipants;

        $this->template->setParameters([
            'canAddParticipants' => $this->canAddParticipants,
        ]);
    }

    public function renderDefault(int $aid) : void
    {
        if (! $this->authorizator->isAllowed(Event::ACCESS_PARTICIPANTS, $this->aid)) {
            $this->flashMessage('Nemáte právo prohlížeč účastníky akce', 'danger');
            $this->redirect('Event:');
        }

        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    public function actionExportExcel(int $aid) : void
    {
        try {
            $participantsDTO = $this->eventParticipants();
            $spreadsheet     = $this->excelService->getGeneralParticipants($participantsDTO, $this->event->getStartDate());

            $this->sendResponse(new ExcelResponse(Strings::webalize($this->event->getDisplayName()) . '-' . date('Y_n_j'), $spreadsheet));
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! (' . $ex->getMessage() . ')', 'danger');
            $this->redirect('default', ['aid' => $aid]);
        }
    }

    protected function createComponentPersonPicker() : PersonPicker
    {
        Assertion::true($this->canAddParticipants);

        $picker = $this->personPickerFactory->create($this->getCurrentUnitId(), $this->eventParticipants());

        $picker->onSelect[] = function (array $personIds) : void {
            foreach ($personIds as $personId) {
                $this->commandBus->handle(new AddEventParticipant($this->event->getId(), $personId));
            }
        };

        $picker->onNonMemberAdd[] = function (NonMemberParticipant $participant) : void {
            $this->commandBus->handle(new CreateEventParticipant($this->event->getId(), $participant));
        };

        return $picker;
    }

    protected function createComponentParticipantList() : ParticipantList
    {
        $control = $this->participantListFactory->create(
            $this->aid,
            $this->eventService,
            $this->eventParticipants(),
            false,
            false,
            $this->isAllowParticipantUpdate,
            $this->isAllowParticipantDelete
        );

        $control->onUpdate[] = function (array $updates) : void {
            /** @var UpdateParticipant $u */
            foreach ($updates as $u) {
                if (! in_array($u->getField(), UpdateParticipant::getEventFields())) {
                    $this->flashMessage(printf('Nelze upravit pole: %s', $u->getField()), 'warning');
                    $this->redirect('this');
                }
                $this->eventService->getParticipants()->update($u);
            }
        };

        $control->onRemove[] = function (array $participantIds) : void {
            foreach ($participantIds as $participantId) {
                $this->commandBus->handle(new RemoveEventParticipant($participantId));
            }
        };

        return $control;
    }

    public function actionExport(int $aid) : void
    {
        try {
            $template = $this->exportService->getParticipants($aid, EventType::GENERAL);
            $this->pdf->render($template, 'seznam-ucastniku.pdf', false);
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! (' . $ex->getMessage() . ')', 'danger');
            $this->redirect('default', ['aid' => $this->aid]);
        }
        $this->terminate();
    }

    /**
     * @return Participant[]
     */
    private function eventParticipants() : array
    {
        return $this->queryBus->handle(new EventParticipantListQuery($this->event->getId()));
    }
}
