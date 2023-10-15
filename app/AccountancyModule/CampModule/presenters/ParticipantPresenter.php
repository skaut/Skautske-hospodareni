<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use App\AccountancyModule\Components\Participants\ParticipantList;
use App\AccountancyModule\Components\Participants\PersonPicker;
use App\AccountancyModule\ExcelResponse;
use App\AccountancyModule\Factories\Participants\IParticipantListFactory;
use App\AccountancyModule\Factories\Participants\IPersonPickerFactory;
use Assert\Assertion;
use Model\Auth\Resources\Camp;
use Model\Cashbook\Commands\Cashbook\AddCampParticipant;
use Model\Cashbook\Commands\Cashbook\CreateCampParticipant;
use Model\Cashbook\Commands\Cashbook\RemoveCampParticipant;
use Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use Model\DTO\Participant\NonMemberParticipant;
use Model\DTO\Participant\Participant;
use Model\DTO\Participant\UpdateParticipant;
use Model\Event\Commands\Camp\ActivateAutocomputedParticipants;
use Model\Event\SkautisCampId;
use Model\ExcelService;
use Model\ExportService;
use Model\Participant\Payment\EventType;
use Model\ParticipantService;
use Model\Services\PdfRenderer;
use Nette\Utils\Strings;
use Skautis\Wsdl\PermissionException;

use function assert;
use function date;
use function in_array;
use function sprintf;

class ParticipantPresenter extends BasePresenter
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
        $this->excelService  = $excel;
    }

    protected function startup(): void
    {
        parent::startup();

        $this->canAddParticipants       = $this->authorizator->isAllowed(Camp::ADD_PARTICIPANT, $this->aid);
        $this->isAllowParticipantDelete = $this->authorizator->isAllowed(Camp::REMOVE_PARTICIPANT, $this->aid);
        $this->isAllowParticipantUpdate = $this->authorizator->isAllowed(Camp::UPDATE_PARTICIPANT, $this->aid);

        $this->template->setParameters([
            'canAddParticipants' => $this->canAddParticipants,
        ]);
    }

    public function renderDefault(int $aid, int|null $uid = null, bool $dp = false, string|null $sort = null, bool $regNums = false): void
    {
        $authorizator = $this->authorizator;

        if (! $authorizator->isAllowed(Camp::ACCESS_PARTICIPANTS, $aid)) {
            $this->flashMessage('Nemáte právo prohlížet účastníky', 'danger');
            $this->redirect('Default:');
        }

        $this->template->setParameters([
            'isAllowParticipantUpdateLocal' => $this->isAllowParticipantDelete,
            'missingAvailableAutoComputed' => ! $this->event->isRealAutoComputed() && $authorizator->isAllowed(Camp::SET_AUTOMATIC_PARTICIPANTS_CALCULATION, $aid),
        ]);

        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    public function handleActivateAutocomputedParticipants(int $aid): void
    {
        $this->commandBus->handle(new ActivateAutocomputedParticipants(new SkautisCampId($aid)));
        $this->flashMessage('Byl aktivován automatický výpočet seznamu osobodnů.');
        $this->redirect('this');
    }

    public function actionExportExcel(int $aid): void
    {
        try {
            $participantsDTO = $this->campParticipants();
            $spreadsheet     = $this->excelService->getCampParticipants($participantsDTO);
            $this->sendResponse(new ExcelResponse(Strings::webalize($this->event->getDisplayName()) . '-' . date('Y_n_j'), $spreadsheet));
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! (' . $ex->getMessage() . ')', 'danger');
            $this->redirect('default', ['aid' => $aid]);
        }
    }

    protected function createComponentPersonPicker(): PersonPicker
    {
        Assertion::true($this->canAddParticipants);

        $picker = $this->personPickerFactory->create($this->getCurrentUnitId(), $this->campParticipants());

        $picker->onSelect[] = function (array $personIds): void {
            foreach ($personIds as $personId) {
                $this->commandBus->handle(new AddCampParticipant($this->event->getId(), $personId));
            }
        };

        $picker->onNonMemberAdd[] = function (NonMemberParticipant $participant): void {
            $this->commandBus->handle(new CreateCampParticipant($this->event->getId(), $participant));
        };

        return $picker;
    }

    protected function createComponentParticipantList(): ParticipantList
    {
        $control = $this->participantListFactory->create(
            $this->aid,
            $this->campParticipants(),
            true,
            true,
            true,
            $this->isAllowParticipantUpdate,
            $this->isAllowParticipantDelete,
            $this->event->isOnlineLogin(),
        );

        $control->onUpdate[] = function (array $updates): void {
            foreach ($updates as $u) {
                assert($u instanceof UpdateParticipant);
                if (! in_array($u->getField(), UpdateParticipant::getCampFields())) {
                    $this->flashMessage(sprintf('Nelze upravit pole: %s', $u->getField()), 'warning');
                    $this->redirect('this');
                }

                $this->participants->update(EventType::CAMP(), $u);
            }
        };

        $control->onRemove[] = function (array $participantIds): void {
            foreach ($participantIds as $participantId) {
                $this->commandBus->handle(new RemoveCampParticipant($participantId));
            }
        };

        return $control;
    }

    public function actionExport(int $aid): void
    {
        try {
            $template = $this->exportService->getParticipants($aid, EventType::CAMP);
            $this->pdf->render($template, 'seznam-ucastniku.pdf', true);
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! (' . $ex->getMessage() . ')', 'danger');
            $this->redirect('default', ['aid' => $this->aid]);
        }

        $this->terminate();
    }

    /** @return Participant[] */
    private function campParticipants(): array
    {
        return $this->queryBus->handle(new CampParticipantListQuery($this->event->getId()));
    }
}
