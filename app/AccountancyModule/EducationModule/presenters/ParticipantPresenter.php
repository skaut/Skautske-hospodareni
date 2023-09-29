<?php

declare(strict_types=1);

namespace App\AccountancyModule\EducationModule;

use App\AccountancyModule\Components\Participants\ParticipantList;
use App\AccountancyModule\ExcelResponse;
use App\AccountancyModule\Factories\Participants\IParticipantListFactory;
use Model\Auth\Resources\Education;
use Model\Cashbook\ReadModel\Queries\EducationInstructorListQuery;
use Model\Cashbook\ReadModel\Queries\EducationParticipantListQuery;
use Model\DTO\Instructor\Instructor;
use Model\DTO\Participant\Participant;
use Model\DTO\Participant\ParticipatingPerson;
use Model\DTO\Participant\UpdateParticipant;
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

    public function __construct(
        ExportService $export,
        ExcelService $excel,
        private PdfRenderer $pdf,
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

        $isDraft      = $this->event->getState() === 'draft';
        $authorizator = $this->authorizator;

        $this->canAddParticipants       = $isDraft && $authorizator->isAllowed(Education::UPDATE_PARTICIPANT, $this->aid);
        $this->isAllowParticipantUpdate = $this->canAddParticipants;

        $this->template->setParameters([
            'canAddParticipants' => $this->canAddParticipants,
        ]);
    }

    public function renderDefault(int $aid): void
    {
        if (! $this->authorizator->isAllowed(Education::ACCESS_PARTICIPANTS, $this->aid)) {
            $this->flashMessage('Nemáte právo prohlížet účastníky akce', 'danger');
            $this->redirect('Education:');
        }

        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    public function actionExportExcel(int $aid, string $exportType): void
    {
        if ($this->event->getStartDate() === null) {
            $this->flashMessage('Bez vyplněného počátku akce nelze exportovat seznam účastníků, protože nelze dopočítat věk v době akce.', 'danger');
            $this->redirect('default', ['aid' => $aid]);
        }

        try {
            $participantsDTO = $this->eventParticipants();
            $spreadsheet     = $this->excelService->getGeneralParticipants($participantsDTO, $this->event->getStartDate());

            $this->sendResponse(new ExcelResponse(Strings::webalize($this->event->getDisplayName()) . '-' . date('Y_n_j'), $spreadsheet));
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! (' . $ex->getMessage() . ')', 'danger');
            $this->redirect('default', ['aid' => $aid]);
        }
    }

    protected function createComponentInstructorList(): ParticipantList
    {
        $control = $this->participantListFactory->create(
            $this->aid,
            $this->eventInstructors(),
            false,
            true,
            true,
            true,
            false,
            false,
            'Seznam instruktorů',
            ParticipatingPerson::INSTRUCTOR,
        );

        $control->onUpdate[] = function (array $updates): void {
            foreach ($updates as $u) {
                assert($u instanceof UpdateParticipant);
                if (! in_array($u->getField(), UpdateParticipant::getEducationFields())) {
                    $this->flashMessage(sprintf('Nelze upravit pole: %s', $u->getField()), 'warning');
                    $this->redirect('this');
                }

                $this->participants->update(EventType::EDUCATION(), $u);
            }
        };

        return $control;
    }

    protected function createComponentParticipantList(): ParticipantList
    {
        $control = $this->participantListFactory->create(
            $this->aid,
            $this->eventParticipants(),
            false,
            true,
            true,
            $this->isAllowParticipantUpdate,
            false,
            false,
        );

        $control->onUpdate[] = function (array $updates): void {
            foreach ($updates as $u) {
                assert($u instanceof UpdateParticipant);
                if (! in_array($u->getField(), UpdateParticipant::getEducationFields())) {
                    $this->flashMessage(sprintf('Nelze upravit pole: %s', $u->getField()), 'warning');
                    $this->redirect('this');
                }

                $this->participants->update(EventType::EDUCATION(), $u);
            }
        };

        return $control;
    }

    public function actionExport(int $aid, string $exportType): void
    {
        try {
            $template = $this->exportService->getParticipants($aid, EventType::EDUCATION, $exportType);
            $this->pdf->render($template, 'seznam-ucastniku.pdf', true);
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! (' . $ex->getMessage() . ')', 'danger');
            $this->redirect('default', ['aid' => $this->aid]);
        }

        $this->terminate();
    }

    /** @return Instructor[] */
    private function eventInstructors(): array
    {
        return $this->queryBus->handle(new EducationInstructorListQuery($this->event->getId()));
    }

    /** @return Participant[] */
    private function eventParticipants(): array
    {
        return $this->queryBus->handle(new EducationParticipantListQuery($this->event->getId()));
    }
}
