<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\EventModule\Components\ExportDialog;
use App\AccountancyModule\EventModule\Factories\IExportDialogFactory;
use App\AccountancyModule\UISorter;
use App\Forms\BaseForm;
use Cake\Chronos\Date;
use Model\Auth\Resources\Event as EventResource;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Event\EventListItem;
use Model\Event\Commands\CancelEvent;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\EventListQuery;
use Model\Event\ReadModel\Queries\EventStates;
use Model\Event\SkautisEventId;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Http\IResponse;
use Nette\Utils\Strings;
use Skautis\Exception;
use function array_filter;
use function array_map;
use function array_merge;
use function array_reverse;
use function assert;
use function date;
use function get_class;
use function in_array;
use function range;
use function Safe\array_combine;
use function sprintf;
use function usort;

class DefaultPresenter extends BasePresenter
{
    public const DEFAULT_STATE = 'draft'; //filtrovani zobrazených položek

    private const SORT_ASCENDING  = 'asc';
    private const SORT_DESCENDING = 'desc';

    private const SORTABLE_BY = ['name', 'startDate', 'endDate', 'prefix', 'state'];

    private const YEAR_ALL  = 'all';
    private const STATE_ALL = 'all';

    /**
     * @var string
     * @persistent
     */
    public $name = '';

    /**
     * @var string|null
     * @persistent
     */
    public $year;

    /**
     * @var string
     * @persistent
     */
    public $state = self::DEFAULT_STATE;

    /**
     * @var string|null
     * @persistent
     */
    public $sortBy = null;

    /**
     * @var string
     * @persistent
     */
    public $sortType = self::SORT_ASCENDING;

    /** @var IExportDialogFactory */
    private $exportDialogFactory;

    public function __construct(IExportDialogFactory $exportDialogFactory)
    {
        parent::__construct();
        $this->exportDialogFactory = $exportDialogFactory;
    }

    protected function startup() : void
    {
        if ($this->year === null) {
            $this->year = (string) Date::today()->year;
        }

        parent::startup();

        $this->redrawControl('events');
        $this->setLayout('layout.new');
    }

    public function renderDefault() : void
    {
        $this->template->setParameters([
            'accessCreate' => $this->authorizator->isAllowed(EventResource::CREATE, null),
            'events' => $this->loadEvents(),
            'sortBy' => $this->sortBy,
            'sortType' => $this->sortType,
            'canCancel' => function (EventListItem $event) : bool {
                return $this->authorizator->isAllowed(EventResource::DELETE, $event->getId()->toInt());
            },
        ]);
    }

    public function handleCancel(int $aid) : void
    {
        if (! $this->authorizator->isAllowed(EventResource::DELETE, $aid)) {
            $this->flashMessage('Nemáte právo na zrušení akce.', 'danger');
            $this->redirect('this');
        }

        try {
            $this->commandBus->handle(new CancelEvent(new SkautisEventId($aid)));
            $this->flashMessage('Akce byla zrušena', 'success');
        } catch (Exception $e) {
            $this->flashMessage('Akci se nepodařilo zrušit', 'danger');
            $this->logger->error(
                sprintf('Event #%d couldn\'t be canceled. Reason: %s', $aid, $e->getMessage()),
                ['exception' => get_class($e)]
            );
        }

        $this->redirect('this');
    }

    protected function createComponentFormFilter() : Form
    {
        $states = array_merge([self::STATE_ALL => 'Nezrušené'], $this->queryBus->handle(new EventStates()));

        $years = $this->getYearOptions();

        if (! isset($years[$this->year], $states[$this->state])) {
            throw new BadRequestException('Invalid filters', IResponse::S400_BAD_REQUEST);
        }

        $form = new BaseForm();

        $form->addText('name')
            ->setDefaultValue($this->name);

        $form->addSelect('state', null, $states)
            ->setDefaultValue($this->state);

        $form->addSelect('year', null, $years)
            ->setDefaultValue($this->year);

        $form->addSubmit('send', 'Hledat')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form) : void {
            $this->formFilterSubmitted($form);
        };

        return $form;
    }

    protected function createComponentExportDialog() : ExportDialog
    {
        return $this->exportDialogFactory->create($this->loadEvents());
    }

    private function formFilterSubmitted(Form $form) : void
    {
        $v = $form->getValues();

        $this->name  = $v['name'];
        $this->year  = $v['year'];
        $this->state = $v['state'];

        if ($this->isAjax()) {
            $this->redrawControl('events');
            $this->payload->url     = $this->link('this', [
                'name' => $this->name,
                'year' => $this->year,
                'state' => $this->state,
            ]);
            $this->payload->postGet = true;
        } else {
            $this->redirect('default', ['aid' => $this->aid]);
        }
    }

    /**
     * @return EventListItem[]
     */
    private function loadEvents() : array
    {
        $state = $this->state;
        $year  = $this->year;

        $events = $this->queryBus->handle(
            new EventListQuery(
                $year === self::YEAR_ALL ? null : (int) $year,
                $state === self::STATE_ALL ? null : $state,
            )
        );

        if ($this->name !== '') {
            $events = array_filter(
                $events,
                function (Event $event) : bool {
                    return Strings::contains($event->getDisplayName(), $this->name);
                }
            );
        }

        $items = array_map(
            function (Event $event) : EventListItem {
                return new EventListItem(
                    $event->getId(),
                    $event->getDisplayName(),
                    $event->getStartDate(),
                    $event->getEndDate(),
                    $this->chitNumberPrefix($event),
                    $event->getState(),
                );
            },
            $events
        );

        if ($this->sortBy !== null) {
            if (! in_array($this->sortType, [self::SORT_ASCENDING, self::SORT_DESCENDING], true) ||
                ! in_array($this->sortBy, self::SORTABLE_BY)) {
                    throw new BadRequestException('Invalid sorting', IResponse::S400_BAD_REQUEST);
            }

            $comparator = new UISorter($this->sortBy);

            usort($items, new UISorter($this->sortBy));

            if ($this->sortType === self::SORT_DESCENDING) {
                $items = array_reverse($items);
            }
        }

        return $items;
    }

    private function chitNumberPrefix(Event $event) : ?string
    {
        $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($event->getId()->toInt())));

        assert($cashbookId instanceof CashbookId);

        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

        assert($cashbook instanceof Cashbook);

        return $cashbook->getChitNumberPrefix();
    }

    /**
     * @return array<string, string>
     */
    private function getYearOptions() : array
    {
        $years = array_map(
            function (int $year) : string {
                return (string) $year;
            },
            array_reverse(range(2012, (int) date('Y')))
        );

        return [self::YEAR_ALL => 'Všechny'] + array_combine($years, $years);
    }
}
