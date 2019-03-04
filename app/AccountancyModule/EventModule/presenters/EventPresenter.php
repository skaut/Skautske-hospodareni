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
use Model\Cashbook\Commands\Cashbook\UpdateChitNumberPrefix;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\DTO\Cashbook\Chit;
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
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;

class EventPresenter extends BasePresenter
{
    /** @var ExportService */
    protected $exportService;

    /** @var IFunctionsControlFactory */
    private $functionsFactory;

    /** @var PdfRenderer */
    private $pdf;

    /** @var LoggerService */
    private $loggerService;

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

        $accessEditBase = $this->authorizator->isAllowed(Event::UPDATE, $aid);

        if ($accessEditBase) {
            $form = $this['formEdit'];
            $form->setDefaults(
                [
                'aid' => $aid,
                'name' => $this->event->DisplayName,
                'start' => $this->event->StartDate,
                'end' => $this->event->EndDate,
                'location' => $this->event->Location,
                'type' => $this->event->ID_EventGeneralType,
                'scope' => $this->event->ID_EventGeneralScope,
                'prefix' => $this->event->prefix,
                ]
            );
        }

        $pragueParticipants = $this->eventService->getParticipants()->countPragueParticipants($this->event);

        $this->template->setParameters(
            [
            'statistic' => $this->eventService->getParticipants()->getEventStatistic($this->aid),
            'accessEditBase' => $accessEditBase,
            'accessCloseEvent' => $this->authorizator->isAllowed(Event::CLOSE, $aid),
            'accessOpenEvent' => $this->authorizator->isAllowed(Event::OPEN, $aid),
            'accessDetailEvent' => $this->authorizator->isAllowed(Event::ACCESS_DETAIL, $aid),
            'pragueParticipants' => $pragueParticipants,
            ]
        );

        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    public function renderLogs(int $aid) : void
    {
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

        /** @var Functions $functions */
        $functions = $this->queryBus->handle(new EventFunctions(new SkautisEventId($aid)));

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
        /** @var CashbookId $cashbookId */
        $cashbookId = $this->getCashbookId($aid);
        /** @var Chit[] $chits */
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $cashbookId));

        $event = $this->eventService->getEvent()->get($aid);

        $template  = $this->exportService->getEventReport($aid, $this->eventService) . $this->exportService->getNewPage();
        $template .= $this->exportService->getParticipants($aid, $this->eventService) . $this->exportService->getNewPage();
        $template .= $this->exportService->getCashbook($cashbookId, $event->DisplayName, PaymentMethod::CASH()) . $this->exportService->getNewPage();
        $template .= $this->exportService->getChits($aid, $this->eventService, $chits, $cashbookId);

        $this->pdf->render($template, 'all.pdf');
        $this->terminate();
    }

    public function renderReport(int $aid) : void
    {
        if (! $this->authorizator->isAllowed(Event::ACCESS_DETAIL, $aid)) {
            $this->flashMessage('Nemáte právo přistupovat k akci', 'warning');
            $this->redirect('default', ['aid' => $aid]);
        }
        $template = $this->exportService->getEventReport($aid, $this->eventService);

        $this->pdf->render($template, 'report.pdf');
        $this->terminate();
    }

    protected function createComponentFormEdit() : Form
    {
        $form = new BaseForm();

        $form->addText('name', 'Název akce')
            ->setRequired('Musíte zadat název akce');
        $form->addDatePicker('start', 'Od')
            ->setRequired('Musíte zadat datum začátku akce');
        $form->addDatePicker('end', 'Do')
            ->setRequired('Musíte zadat datum konce akce')
            ->addRule([MyValidators::class, 'isValidRange'], 'Konec akce musí být po začátku akce', $form['start']);
        $form->addText('location', 'Místo');
        $form->addSelect('type', 'Typ (+)', $this->queryBus->handle(new EventTypes()));
        $form->addSelect('scope', 'Rozsah (+)', $this->queryBus->handle(new EventScopes()));
        $form->addText('prefix', 'Prefix')
            ->setMaxLength(6);
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
                Date::instance($values['start']),
                Date::instance($values['end']),
                $values['location'] !== '' ? $values['location'] : null,
                $values['scope'],
                $values['type']
            )
        );

        if (isset($values['prefix'])) {
            $this->commandBus->handle(new UpdateChitNumberPrefix($this->getCashbookId($id), $values['prefix']));
        }

        $this->flashMessage('Základní údaje byly upraveny.');
        $this->redirect('default', ['aid' => $id]);
    }

    protected function createComponentFunctions() : FunctionsControl
    {
        return $this->functionsFactory->create($this->aid);
    }

    private function getCashbookId(int $skautisEventId) : CashbookId
    {
        return $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($skautisEventId)));
    }
}
