<?php

declare(strict_types=1);

namespace App\Presentation\Admin\BugReports;

use App\Components\DataGrid;
use App\Components\Grids\GridFactory;
use App\Model\BugReport\Entity\TechnicalErrorReport;
use App\Model\BugReport\Manager\TechnicalErrorReportManager;
use App\Model\BugReport\Repository\TechnicalErrorReportRepository;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

final class BugReportsPresenter extends \App\Presentation\Admin\AdminBasePresenter
{
    private ?TechnicalErrorReport $report = null;

    public function __construct(
        private TechnicalErrorReportRepository $repository,
        private TechnicalErrorReportManager $manager,
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

        $this->manager->resolve($report);
        $this->flashMessage('Hlášení technické chyby bylo označeno jako vyřízené.', 'success');
        $this->redirect('default');
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
