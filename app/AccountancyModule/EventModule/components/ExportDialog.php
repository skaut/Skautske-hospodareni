<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule\Components;

use App\AccountancyModule\Components\Dialog;
use App\AccountancyModule\ExcelResponse;
use App\Forms\BaseForm;
use Cake\Chronos\Date;
use eGen\MessageBus\Bus\QueryBus;
use Model\DTO\Event\EventListItem;
use Model\Event\ReadModel\Queries\Excel\ExportEvents;
use Model\Services\Language;
use Nette\Utils\ArrayHash;
use function sprintf;
use function uasort;

final class ExportDialog extends Dialog
{
    /** @var EventListItem[] */
    private array $events;

    private QueryBus $queryBus;

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
        $this->show();
    }

    protected function beforeRender() : void
    {
        $this->template->setFile(__DIR__ . '/templates/ExportDialog.latte');
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();

        $form->useBootstrap4();

        $events = [];

        foreach ($this->events as $event) {
            $events[$event->getId()] = $event->getName();
        }

        uasort($events, [Language::class, 'compare']);

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
