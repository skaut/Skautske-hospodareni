<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\AccountancyModule\ExcelResponse;
use App\Forms\BaseForm;
use Cake\Chronos\Date;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\Pdf\ExportEvents;
use Model\DTO\Event\EventListItem;
use Nette\Utils\ArrayHash;
use function sprintf;

final class ExportDialog extends BaseControl
{
    /** @var bool @persistent */
    public $opened = false;

    /** @var EventListItem[] */
    private $events;

    /** @var QueryBus */
    private $queryBus;

    /**
     * @param EventListItem[] $events
     */
    public function __construct(array $events, QueryBus $queryBus)
    {
        parent::__construct();
        $this->events   = $events;
        $this->queryBus = $queryBus;
    }

    public function handleOpen() : void
    {
        $this->opened = true;
        $this->redrawControl();
    }

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/ExportDialog.latte');
        $this->template->setParameters(['renderModal' => $this->opened]);

        $this->template->render();
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();

        $form->useBootstrap4();

        $events = [];

        foreach ($this->events as $event) {
            $events[$event->getId()] = $event->getName();
        }

        $form->addCheckboxList('eventIds', 'Akce', $events)
            ->setRequired('Musíte vybrat alespoň jednu akci');

        $form->addSubmit('download', 'Stáhnout export');

        $form->onSuccess[] = function (BaseForm $form) : void {
            $this->formSucceeded($form->getValues());
        };

        return $form;
    }

    private function formSucceeded(ArrayHash $values) : void
    {
        $this->presenter->sendResponse(
            new ExcelResponse(
                sprintf('Souhrn-akci-%s', Date::today()->format('Y_n_j')),
                $this->queryBus->handle(new ExportEvents($values->eventIds))
            )
        );
    }
}
