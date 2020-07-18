<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\EventModule\Components\FunctionsControl;
use App\AccountancyModule\EventModule\Factories\IFunctionsControlFactory;
use App\Forms\BaseForm;
use App\MyValidators;
use Cake\Chronos\Date;
use Model\Auth\Resources\Event;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\EventPragueParticipantsQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\Cashbook\ReadModel\Queries\Pdf\ExportChits;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\Commands\Event\ActivateStatistics;
use Model\Event\Commands\Event\CloseEvent;
use Model\Event\Commands\Event\OpenEvent;
use Model\Event\Commands\Event\UpdateEvent;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\ReadModel\Queries\EventScopes;
use Model\Event\ReadModel\Queries\EventTypes;
use Model\Event\SkautisEventId;
use Model\ExportService;
use Model\Logger\Log\Type;
use Model\LoggerService;
use Model\Services\PdfRenderer;
use Model\Skautis\ReadModel\Queries\EventStatisticsQuery;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use function assert;

class EventPresenter extends BasePresenter
{
    protected ExportService $exportService;

    private IFunctionsControlFactory $functionsFactory;

    private PdfRenderer $pdf;

    private LoggerService $loggerService;

    public function __construct(
        ExportService $exportService,
        IFunctionsControlFactory $functionsFactory,
        PdfRenderer $pdf,
        LoggerService $loggerService
    ) {
        parent::__construct();
        $this->exportService    = $exportService;
        $this->functionsFactory = $functionsFactory;
        $this->pdf              = $pdf;
        $this->loggerService    = $loggerService;
    }

    public function renderDefault(?int $aid) : void
    {
        if ($aid === null) {
            $this->redirect('Default:');
        }

        $this->setLayout('layout.new');

        $accessEditBase = $this->authorizator->isAllowed(Event::UPDATE, $aid);
        $this->template->setParameters([
            'highlightCloseButton' => $this->event->getEndDate()->diffInDays(Date::now(), false) > 14,
        ]);

        if ($accessEditBase) {
            $form = $this['formEdit'];
            $form->setDefaults([
                'aid' => $aid,
                'name' => $this->event->getDisplayName(),
                'start' => $this->event->getStartDate(),
                'end' => $this->event->getEndDate(),
                'location' => $this->event->getLocation(),
                'type' => $this->event->getTypeId(),
                'scope' => $this->event->getScopeId(),
            ]);
        }

        $pragueParticipants = $this->queryBus->handle(new EventPragueParticipantsQuery(
            $this->event->getId(),
            $this->event->getRegistrationNumber(),
            $this->event->getStartDate()
        ));

        $cashbook = $this->queryBus->handle(new CashbookQuery($this->getCashbookId($aid)));
        assert($cashbook instanceof Cashbook);

        $this->template->setParameters([
            'statistic' => $this->queryBus->handle(new EventStatisticsQuery(new SkautisEventId($this->aid))),
            'finalRealBalance' => $this->queryBus->handle(new FinalRealBalanceQuery($this->getCashbookId($this->aid))),
            'accessEditBase' => $accessEditBase,
            'accessCloseEvent' => $this->authorizator->isAllowed(Event::CLOSE, $aid),
            'accessOpenEvent' => $this->authorizator->isAllowed(Event::OPEN, $aid),
            'accessDetailEvent' => $this->authorizator->isAllowed(Event::ACCESS_DETAIL, $aid),
            'pragueParticipants' => $pragueParticipants,
            'eventScopes' => $this->queryBus->handle(new EventScopes()),
            'eventTypes' => $this->queryBus->handle(new EventTypes()),
            'prefix' => $cashbook->getChitNumberPrefix(PaymentMethod::CASH()),
        ]);

        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    public function renderLogs(int $aid) : void
    {
        $this->setLayout('layout.new');
        $this->template->setParameters([
            'logs' => $this->loggerService->findAllByTypeId(Type::get(Type::OBJECT), $aid),
        ]);
    }

    public function handleOpen(int $aid) : void
    {
        if (! $this->authorizator->isAllowed(Event::OPEN, $aid)) {
            $this->flashMessage('Nemáte právo otevřít akci', 'warning');
            $this->redirect('this');
        }

        $this->commandBus->handle(new OpenEvent(new SkautisEventId($aid)));

        $this->flashMessage('Akce byla znovu otevřena.');
        $this->redirect('this');
    }

    public function handleClose(int $aid) : void
    {
        if (! $this->authorizator->isAllowed(Event::CLOSE, $aid)) {
            $this->flashMessage('Nemáte právo akci uzavřít', 'warning');
            $this->redirect('this');
        }

        $functions = $this->queryBus->handle(new EventFunctions(new SkautisEventId($aid)));

        assert($functions instanceof Functions);

        if ($functions->getLeader() !== null) {
            $this->commandBus->handle(new CloseEvent(new SkautisEventId($aid)));
            $this->flashMessage('Akce byla uzavřena.');
        } else {
            $this->flashMessage('Před uzavřením akce musí být vyplněn vedoucí akce', 'danger');
        }

        $this->redirect('this');
    }

    public function handleActivateStatistic() : void
    {
        $this->commandBus->handle(new ActivateStatistics($this->aid));
        //flash message?
        $this->redirect('this', ['aid' => $this->aid]);
    }

    public function actionPrintAll(int $aid) : void
    {
        $cashbookId = $this->getCashbookId($aid);

        $template  = $this->exportService->getEventReport($aid) . $this->exportService->getNewPage();
        $template .= $this->exportService->getParticipants($aid) . $this->exportService->getNewPage();
        $template .= $this->exportService->getCashbook($cashbookId, PaymentMethod::CASH()) . $this->exportService->getNewPage();
        $template .= $this->queryBus->handle(ExportChits::all($cashbookId));

        $this->pdf->render($template, 'all.pdf');
        $this->terminate();
    }

    public function renderReport(int $aid) : void
    {
        if (! $this->authorizator->isAllowed(Event::ACCESS_DETAIL, $aid)) {
            $this->flashMessage('Nemáte právo přistupovat k akci', 'warning');
            $this->redirect('default', ['aid' => $aid]);
        }

        $template = $this->exportService->getEventReport($aid);

        $this->pdf->render($template, 'report.pdf');
        $this->terminate();
    }

    protected function createComponentFormEdit() : Form
    {
        $form = new BaseForm();

        $form->addText('name', 'Název akce')
            ->setRequired('Musíte zadat název akce');
        $form->addDate('start', 'Od')
            ->setRequired('Musíte zadat datum začátku akce');
        $form->addDate('end', 'Do')
            ->setRequired('Musíte zadat datum konce akce')
            ->addRule([MyValidators::class, 'isValidRange'], 'Konec akce musí být po začátku akce', $form['start']);
        $form->addText('location', 'Místo');
        $form->addSelect('type', 'Typ (+)', $this->queryBus->handle(new EventTypes()));
        $form->addSelect('scope', 'Rozsah (+)', $this->queryBus->handle(new EventScopes()));
        $form->addHidden('aid');
        $form->addSubmit('send', 'Upravit')
            ->setAttribute('class', 'btn btn-primary')
            ->onClick[] = function (SubmitButton $button) : void {
                $this->formEditSubmitted($button);
            };

        return $form;
    }

    private function formEditSubmitted(SubmitButton $button) : void
    {
        if (! $this->authorizator->isAllowed(Event::UPDATE, $this->aid)) {
            $this->flashMessage('Nemáte oprávnění pro úpravu akce', 'danger');
            $this->redirect('this');
        }

        $id     = (int) $this->aid;
        $values = $button->getForm()->getValues(true);

        $this->commandBus->handle(
            new UpdateEvent(
                new SkautisEventId($id),
                $values['name'],
                $values['start'],
                $values['end'],
                $values['location'] !== '' ? $values['location'] : null,
                $values['scope'],
                $values['type']
            )
        );

        $this->flashMessage('Základní údaje byly upraveny.');
        $this->redirect('default', ['aid' => $id]);
    }

    protected function createComponentFunctions() : FunctionsControl
    {
        return $this->functionsFactory->create($this->aid, $this->getCurrentUnitId());
    }

    private function getCashbookId(int $skautisEventId) : CashbookId
    {
        return $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($skautisEventId)));
    }
}
