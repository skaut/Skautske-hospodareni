<?php

declare(strict_types=1);

namespace App\AccountancyModule\EducationModule;

use App\AccountancyModule\Components\Participants\ParticipantList;
use App\AccountancyModule\ExcelResponse;
use App\AccountancyModule\Factories\Participants\IParticipantListFactory;
use Model\Auth\Resources\Education;
use Model\Cashbook\ReadModel\Queries\EducationParticipantListQuery;
use Model\DTO\Participant\Participant;
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
    private ExportService $exportService;

    private ExcelService $excelService;

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

    public function renderDefault(int $aid): void
    {
        if (! $this->authorizator->isAllowed(Education::ACCESS_PARTICIPANTS, $this->aid)) {
            $this->flashMessage('Nemáte právo prohlížet účastníky akce', 'danger');
            $this->redirect('Education:', ['aid' => $aid]);
        }

        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    public function actionExportExcel(int $aid): void
    {
        if ($this->event->getStartDate() === null) {
            $this->flashMessage('Bez vyplněného počátku akce nelze exportovat seznam účastníků, protože nelze dopočítat věk v době akce.', 'danger');
            $this->redirect('default', ['aid' => $aid]);
        }

        try {
            $participantsDTO = $this->eventParticipants();
            $spreadsheet     = $this->excelService->getEducationParticipants($participantsDTO);

            $this->sendResponse(new ExcelResponse(Strings::webalize($this->event->getDisplayName()) . '-' . date('Y_n_j'), $spreadsheet));
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! (' . $ex->getMessage() . ')', 'danger');
            $this->redirect('default', ['aid' => $aid]);
        }
    }

    protected function createComponentParticipantList(): ParticipantList
    {
        $control = $this->participantListFactory->create(
            $this->aid,
            $this->eventParticipants(),
            false,
            true,
            true,
            false,
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

    public function actionExport(int $aid): void
    {
        try {
            $template = $this->exportService->getParticipants($aid, EventType::EDUCATION);
            $this->pdf->render($template, 'seznam-ucastniku.pdf', true);
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! (' . $ex->getMessage() . ')', 'danger');
            $this->redirect('default', ['aid' => $this->aid]);
        }

        $this->terminate();
    }

    /** @return Participant[] */
    private function eventParticipants(): array
    {
        return $this->queryBus->handle(new EducationParticipantListQuery($this->event->getId()));
    }
}
