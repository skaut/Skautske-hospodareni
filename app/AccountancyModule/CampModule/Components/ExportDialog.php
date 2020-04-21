<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule\Components;

use App\AccountancyModule\Components\Dialog;
use App\AccountancyModule\ExcelResponse;
use App\Forms\BaseForm;
use Cake\Chronos\Date;
use eGen\MessageBus\Bus\QueryBus;
use Model\DTO\Camp\CampListItem;
use Model\Event\ReadModel\Queries\Excel\ExportCamps;
use Model\Services\Language;
use Nette\Utils\ArrayHash;
use function sprintf;
use function uasort;

final class ExportDialog extends Dialog
{
    /** @var CampListItem[] */
    private array $camps;

    private QueryBus $queryBus;

    /**
     * @param CampListItem[] $camps
     */
    public function __construct(array $camps, QueryBus $queryBus)
    {
        parent::__construct();
        $this->camps    = $camps;
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

        $items = [];

        foreach ($this->camps as $camp) {
            $items[$camp->getId()] = $camp->getName();
        }

        uasort($items, [Language::class, 'compare']);

        $form->addCheckboxList('campIds', 'Tábory', $items)
            ->setRequired('Musíte vybrat alespoň jednen tábor');

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
                sprintf('Souhrn-táborů-%s', Date::today()->format('Y_n_j')),
                $this->queryBus->handle(new ExportCamps($values->campIds))
            )
        );
    }
}
