<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use Assert\Assertion;
use Model\Travel\Travel\Type;
use Model\TravelService;
use Model\Utils\MoneyFactory;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use function assert;

final class EditTravelDialog extends BaseControl
{
    /** @var int|null @persistent */
    public $travelId;

    /** @var bool @persistent */
    public $opened = false;

    /** @var int */
    private $commandId;

    /** @var TravelService */
    private $model;

    public function __construct(int $commandId, TravelService $model)
    {
        parent::__construct();
        $this->commandId = $commandId;
        $this->model     = $model;
    }

    public function open(int $travelId) : void
    {
        $this->travelId = $travelId;
        $this->opened   = true;
        $this->redrawControl();
    }

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/EditTravelDialog.latte');
        $this->template->setParameters([
            'renderModal' => $this->opened,
        ]);

        $this->template->render();
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();
        $form->useBootstrap4();

        $command = $this->model->getCommandDetail($this->commandId);

        assert($command !== null);

        $form->addSelect('type', 'Prostředek', $command->getTransportTypePairs());

        $form->addDate('date', 'Datum cesty')
            ->setAttribute('class', 'date')
            ->addRule(Form::FILLED, 'Musíte vyplnit datum cesty.');

        $form->addText('startPlace', 'Z*')
            ->addRule(Form::FILLED, 'Musíte vyplnit místo počátku cesty.');

        $form->addText('endPlace', 'Do*')
            ->addRule(Form::FILLED, 'Musíte vyplnit místo konce cesty.');

        $form->addText('distanceOrPrice', 'Vzdálenost*')
            ->setRequired('Musíte vyplnit vzdálenost.')
            ->addRule(Form::FLOAT, 'Vzdálenost musí být číslo.')
            ->addRule(Form::MIN, 'Vzdálenost musí být větší než 0.', 0.01);

        $form->addSubmit('send', 'Upravit')
            ->setAttribute('class', 'btn btn-primary');

        $travelId = $this->travelId;
        Assertion::notNull($travelId);

        $travelId = (int) $travelId; // Persistent parameters aren't auto casted

        $travel = $this->model->getTravel($this->commandId, $travelId);
        Assertion::notNull($travel);

        $form->setDefaults([
            'type' => $travel->getDetails()->getTransportType()->getLabel(),
            'date' => $travel->getDetails()->getDate(),
            'startPlace' => $travel->getDetails()->getStartPlace(),
            'endPlace' => $travel->getDetails()->getEndPlace(),
            'distanceOrPrice' => $travel->getDistance() ?? MoneyFactory::toFloat($travel->getPrice()),
        ]);

        $form->onSuccess[] = function (Form $form) use ($travelId) : void {
            $this->formSucceeded($travelId, $form->getValues());
        };

        return $form;
    }

    private function formSucceeded(int $travelId, ArrayHash $values) : void
    {
        $this->model->updateTravel(
            $this->commandId,
            $travelId,
            (float) $values->distanceOrPrice,
            $values->date,
            Type::get($values->type),
            $values->startPlace,
            $values->endPlace
        );

        $this->flashMessage('Cesta byla upravena.');
        $this->redirect('this');
    }
}
