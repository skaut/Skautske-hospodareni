<?php

declare(strict_types=1);

namespace App\Presentation\Events\Participant;

use App\Components\Factories\Participants\IParticipantListFactory;
use App\Components\Factories\Participants\IPersonPickerFactory;
use App\Components\Participants\ParticipantList;
use App\Components\Participants\PersonPicker;
use App\Http\ExcelResponse;
use App\Model\Auth\Resources\Event;
use App\Model\Cashbook\Commands\Cashbook\AddEventParticipant;
use App\Model\Cashbook\Commands\Cashbook\CreateEventParticipant;
use App\Model\Cashbook\Commands\Cashbook\RemoveEventParticipant;
use App\Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use App\Model\DTO\Participant\NonMemberParticipant;
use App\Model\DTO\Participant\Participant;
use App\Model\DTO\Participant\UpdateParticipant;
use App\Model\Excel\ExcelService;
use App\Model\Export\ExportService;
use App\Model\Participant\ParticipantService;
use App\Model\Participant\Payment\EventType;
use App\Model\Services\PdfRenderer;
use App\Presentation\Events\BasePresenter;
use Assert\Assertion;
use LogicException;
use Nette\Utils\Strings;
use Skautis\Wsdl\PermissionException;

use function date;
use function in_array;
use function sprintf;

final class ParticipantPresenter extends BasePresenter
{
    private bool $canAddParticipants;

    private ExportService $exportService;

    private ExcelService $excelService;

    private bool $isAllowParticipantUpdate;

    private bool $isAllowParticipantDelete;

    public function __construct(
        ExportService $export,
        ExcelService $excel,
        private PdfRenderer $pdf,
        private IPersonPickerFactory $personPickerFactory,
        private IParticipantListFactory $participantListFactory,
        private ParticipantService $participants,
    ) {
        parent::__construct();

        $this->exportService = $export;
        $this->excelService = $excel;
    }

    protected function startup(): void
    {
        parent::startup();

        $isDraft = $this->event->getState() === 'draft';
        $authorizator = $this->authorizator;

        $this->isAllowParticipantDelete = $isDraft && $authorizator->isAllowed(Event::REMOVE_PARTICIPANT, $this->aid);
        $this->canAddParticipants = $isDraft && $authorizator->isAllowed(Event::UPDATE_PARTICIPANT, $this->aid);
        $this->isAllowParticipantUpdate = $this->canAddParticipants;

        $this->template->setParameters([
            'canAddParticipants' => $this->canAddParticipants,
        ]);
    }

    public function renderDefault(int $aid): void
    {
        if (! $this->authorizator->isAllowed(Event::ACCESS_PARTICIPANTS, $this->aid)) {
            $this->flashMessage('Nemáte právo prohlížet účastníky akce', 'danger');
            $this->redirect('Event:');
        }

        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    public function actionExportExcel(int $aid): void
    {
        try {
            $participantsDTO = $this->eventParticipants();
            $spreadsheet = $this->excelService->getGeneralParticipants($participantsDTO, $this->event->getStartDate());

            $this->sendResponse(new ExcelResponse(Strings::webalize($this->event->getDisplayName()).'-'.date('Y_n_j'), $spreadsheet));
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! ('.$ex->getMessage().')', 'danger');
            $this->redirect('default', ['aid' => $aid]);
        }
    }

    protected function createComponentPersonPicker(): PersonPicker
    {
        Assertion::true($this->canAddParticipants);

        $picker = $this->personPickerFactory->create($this->getCurrentUnitId(), $this->eventParticipants());

        $picker->onSelect[] = function (array $personIds): void {
            foreach ($personIds as $personId) {
                $this->commandBus->handle(new AddEventParticipant($this->event->getId(), $personId));
            }
        };

        $picker->onNonMemberAdd[] = function (NonMemberParticipant $participant): void {
            $this->commandBus->handle(new CreateEventParticipant($this->event->getId(), $participant));
        };

        return $picker;
    }

    protected function createComponentParticipantList(): ParticipantList
    {
        $control = $this->participantListFactory->create(
            $this->aid,
            $this->eventParticipants(),
            true,
            false,
            false,
            $this->isAllowParticipantUpdate,
            $this->isAllowParticipantDelete,
            false,
        );

        $control->onUpdate[] = function (array $updates): void {
            foreach ($updates as $u) {
                if (! $u instanceof UpdateParticipant) {
                    throw new LogicException('Assertion failed.');
                }
                if (! in_array($u->getField(), UpdateParticipant::getEventFields())) {
                    $this->flashMessage(sprintf('Nelze upravit pole: %s', $u->getField()), 'warning');
                    $this->redirect('this');
                }

                $this->participants->update(EventType::GENERAL(), $u);
            }
        };

        $control->onRemove[] = function (array $participantIds): void {
            foreach ($participantIds as $participantId) {
                $this->commandBus->handle(new RemoveEventParticipant($participantId));
            }
        };

        return $control;
    }

    public function actionExport(int $aid): void
    {
        try {
            $template = $this->exportService->getParticipants($aid, EventType::GENERAL);
            $this->pdf->render($template, 'seznam-ucastniku.pdf', false);
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! ('.$ex->getMessage().')', 'danger');
            $this->redirect('default', ['aid' => $this->aid]);
        }

        $this->terminate();
    }

    /** @return Participant[] */
    private function eventParticipants(): array
    {
        return $this->queryBus->handle(new EventParticipantListQuery($this->event->getId()));
    }
}
