<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\AccountancyModule\ExcelResponse;
use App\Forms\BaseForm;
use Cake\Chronos\Date;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\Pdf\ExportCamps;
use Model\DTO\Camp\CampListItem;
use Model\Services\Language;
use Nette\Utils\ArrayHash;
use function sprintf;
use function uasort;

final class ExportDialog extends BaseControl
{
    /** @var bool @persistent */
    public $opened = false;

    /** @var CampListItem[] */
    private $camps;

    /** @var QueryBus */
    private $queryBus;

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

        foreach ($this->camps as $camp) {
            $events[$camp->getId()] = $camp->getName();
        }

        uasort($events, [Language::class, 'compare']);

        $form->addCheckboxList('campIds', 'Tábory', $events)
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
