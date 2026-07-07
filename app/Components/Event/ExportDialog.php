<?php

declare(strict_types=1);

namespace App\Components\Event;

use App\Components\Dialog;
use App\Http\ExcelResponse;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Event\EventListItem;
use App\Model\Event\ReadModel\Queries\Excel\ExportEvents;
use App\Utils\CzechStringComparator;
use Cake\Chronos\ChronosDate;
use Component\Forms\BaseForm;
use Nette\Utils\ArrayHash;

use function sprintf;
use function uasort;

final class ExportDialog extends Dialog
{
    /** @param EventListItem[] $events */
    public function __construct(private array $events, private QueryBus $queryBus)
    {
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->setFile(__DIR__.'/templates/ExportDialog.latte');
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $events = [];

        foreach ($this->events as $event) {
            $events[$event->getId()] = $event->getName();
        }

        uasort($events, [CzechStringComparator::class, 'compare']);

        $form->addCheckboxList('eventIds', 'Akce', $events)
            ->setRequired('Musíte vybrat alespoň jednu akci');

        $form->addSubmit('download', 'Stáhnout export');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->formSucceeded($form->getValues());
        };

        return $form;
    }

    private function formSucceeded(ArrayHash $values): void
    {
        $this->presenter->sendResponse(
            new ExcelResponse(
                sprintf('Souhrn-akci-%s', ChronosDate::today()->format('Y_n_j')),
                $this->queryBus->handle(new ExportEvents($values->eventIds)),
            ),
        );
    }
}
