<?php

declare(strict_types=1);

namespace App\Components\Camps;

use App\Components\Dialog;
use App\Http\ExcelResponse;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Camp\CampListItem;
use App\Model\Event\ReadModel\Queries\Excel\ExportCamps;
use App\Utils\CzechStringComparator;
use Cake\Chronos\ChronosDate;
use Component\Forms\BaseForm;
use Nette\Utils\ArrayHash;

use function sprintf;
use function uasort;

final class ExportDialog extends Dialog
{
    /** @param CampListItem[] $camps */
    public function __construct(private array $camps, private QueryBus $queryBus)
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

        $items = [];

        foreach ($this->camps as $camp) {
            $items[$camp->getId()] = $camp->getName();
        }

        uasort($items, [CzechStringComparator::class, 'compare']);

        $form->addCheckboxList('campIds', 'Tábory', $items)
            ->setRequired('Musíte vybrat alespoň jednen tábor');

        $form->addSubmit('download', 'Stáhnout export');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->formSucceeded($form->getValues(ArrayHash::class));
        };

        return $form;
    }

    private function formSucceeded(ArrayHash $values): void
    {
        $this->presenter->sendResponse(
            new ExcelResponse(
                sprintf('Souhrn-táborů-%s', ChronosDate::today()->format('Y_n_j')),
                $this->queryBus->handle(new ExportCamps($values->campIds)),
            ),
        );
    }
}
