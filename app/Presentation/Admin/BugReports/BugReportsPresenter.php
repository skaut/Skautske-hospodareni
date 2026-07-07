<?php

declare(strict_types=1);

namespace App\Presentation\Admin\BugReports;

use App\Components\DataGrid;
use App\Components\Grids\GridFactory;
use App\Model\BugReport\BugReportNotificationService;
use App\Model\BugReport\Entity\TechnicalErrorReport;
use App\Model\BugReport\Manager\TechnicalErrorReportManager;
use App\Model\BugReport\Repository\TechnicalErrorReportRepository;
use Component\Forms\BaseForm;
use Nette\Application\UI\Form;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Throwable;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

final class BugReportsPresenter extends \App\Presentation\Admin\AdminBasePresenter
{
    private ?TechnicalErrorReport $report = null;

    public function __construct(
        private TechnicalErrorReportRepository $repository,
        private TechnicalErrorReportManager $manager,
        private BugReportNotificationService $notificationService,
        private GridFactory $gridFactory,
    ) {
    }

    public function actionDetail(int $id): void
    {
        $report = $this->repository->findUnresolved($id);
        if (! $report instanceof TechnicalErrorReport) {
            $this->flashMessage('Hlášení technické chyby nebylo nalezeno.', 'warning');
            $this->redirect('default');
        }

        $this->report = $report;
    }

    public function renderDefault(): void
    {
        $this->template->setParameters([
            'adminSection' => 'bugReports',
            'unitId' => $this->unitId->toInt(),
        ]);
    }

    public function renderDetail(): void
    {
        $report = $this->report;
        if (! $report instanceof TechnicalErrorReport) {
            return;
        }

        $this->template->setParameters([
            'adminSection' => 'bugReports',
            'unitId' => $this->unitId->toInt(),
            'report' => $report,
            'diagnosticsJson' => Json::encode($report->getDiagnostics(), JSON_PRETTY_PRINT),
        ]);
    }

    public function handleResolve(int $id): void
    {
        $report = $this->repository->findUnresolved($id);
        if (! $report instanceof TechnicalErrorReport) {
            $this->flashMessage('Hlášení technické chyby nebylo nalezeno nebo už je vyřízené.', 'warning');
            $this->redirect('default');
        }

        $this->resolveReport($report, 'Problém byl vyřešen a je upraven v nové verzi aplikace.');
        $this->redirect('default');
    }

    protected function createComponentResolveWithMessageForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addHidden('id');
        $form->addTextArea('message', 'Zpráva autorovi')
            ->setRequired('Napište prosím zprávu autorovi hlášení.')
            ->addRule(Form::MAX_LENGTH, 'Zpráva může mít nejvýše %d znaků.', 10000)
            ->setHtmlAttribute('rows', 7);
        $form->addSubmit('send', 'Odeslat a označit jako vyřízené')
            ->setHtmlAttribute('class', 'btn btn-success');

        $form->onSuccess[] = function (Form $form): void {
            $values = $form->getValues();
            $report = $this->repository->findUnresolved((int) $values->id);
            if (! $report instanceof TechnicalErrorReport) {
                $this->flashMessage('Hlášení technické chyby nebylo nalezeno nebo už je vyřízené.', 'warning');
                $this->redirect('default');
            }

            $this->resolveReport($report, trim((string) $values->message));
            $this->redirect('default');
        };

        return $form;
    }

    private function resolveReport(TechnicalErrorReport $report, string $message): void
    {
        try {
            $this->notificationService->notifyResolution($report, $message);
            $report->markResolutionNotificationSent();
            $this->flashMessage('Hlášení technické chyby bylo označeno jako vyřízené a autor byl informován e-mailem.', 'success');
        } catch (Throwable $e) {
            $report->markResolutionNotificationFailed($e->getMessage());
            $this->flashMessage('Hlášení technické chyby bylo označeno jako vyřízené, ale e-mail autorovi se nepodařilo odeslat.', 'warning');
        }

        $this->manager->resolve($report, $message);
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey('id');
        $grid->setDataSource($this->repository->createGridQueryBuilder());
        $grid->setDefaultSort(['createdAt' => DataGrid::SORT_DESC]);

        $grid->addColumnNumber('id', '#')
            ->setSortable();
        $grid->addColumnDateTime('createdAt', 'Nahlášeno')
            ->setFormat('j. n. Y H:i:s')
            ->setSortable();
        $grid->addColumnText('reporterDisplayName', 'Uživatel')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('reporterEmail', 'E-mail')
            ->setRenderer(static fn (TechnicalErrorReport $report): string => $report->getReporterEmail() ?? '-')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('reporterUserId', 'User ID')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('roleName', 'Role')
            ->setFilterText();
        $grid->addColumnText('reportedUrl', 'URL')
            ->setRenderer(static fn (TechnicalErrorReport $report): string => Strings::truncate($report->getReportedUrl() ?? '-', 45))
            ->setFilterText();
        $grid->addColumnText('description', 'Popis')
            ->setRenderer(static fn (TechnicalErrorReport $report): string => Strings::truncate($report->getDescription(), 90))
            ->setFilterText();
        $grid->addColumnText('notificationSentAt', 'E-mail')
            ->setRenderer(static fn (TechnicalErrorReport $report): string => $report->wasNotificationSent() ? 'Odesláno' : 'Chyba');

        $grid->addAction('detail', '', 'detail', ['id' => 'id'])
            ->setIcon('far fa-eye')
            ->setTitle('Zobrazit detail')
            ->setClass('btn btn-sm btn-light')
            ->setDataAttribute('test', 'admin-bug-report-detail-grid');
        $grid->addAction('resolve', '', 'resolve!', ['id' => 'id'])
            ->setIcon('far fa-circle-check')
            ->setTitle('Označit jako vyřízené')
            ->setClass('btn btn-sm btn-outline-success')
            ->setDataAttribute('test', 'admin-bug-report-resolve-grid')
            ->setConfirmation(
                new StringConfirmation('Opravdu chcete označit hlášení #%s jako vyřízené?', 'id'),
            );

        return $grid;
    }
}
