<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule\Components;

use App\AccountancyModule\Components\Dialog;
use App\Forms\BaseForm;
use Assert\Assertion;
use Cake\Chronos\ChronosDate;
use Model\Travel\Travel\TransportType;
use Model\TravelService;
use Model\Utils\MoneyFactory;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

use function assert;

final class EditTravelDialog extends Dialog
{
    /** @var int|null @persistent */
    public int|null $travelId = null;

    public function __construct(private int $commandId, private TravelService $model)
    {
    }

    public function open(int $travelId): void
    {
        $this->travelId = $travelId;

        $this->show();
    }

    protected function beforeRender(): void
    {
        $this->template->setFile(__DIR__ . '/templates/EditTravelDialog.latte');
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $command = $this->model->getCommandDetail($this->commandId);

        assert($command !== null);

        $form->addSelect('type', 'Prostředek', $command->getTransportTypePairs());

        $form->addDate('date', 'Datum cesty')
            ->setHtmlAttribute('class', 'date')
            ->addRule(Form::FILLED, 'Musíte vyplnit datum cesty.');

        $form->addText('startPlace', 'Z')
            ->addRule(Form::FILLED, 'Musíte vyplnit místo počátku cesty.');

        $form->addText('endPlace', 'Do')
            ->addRule(Form::FILLED, 'Musíte vyplnit místo konce cesty.');

        $form->addText('distanceOrPrice', 'km nebo cena')
            ->setRequired('Musíte vyplnit vzdálenost.')
            ->addRule(Form::FLOAT, 'Vzdálenost musí být číslo.')
            ->addRule(Form::MIN, 'Vzdálenost musí být větší než 0.', 0.01);

        $form->addSubmit('send', 'Upravit')
            ->setHtmlAttribute('class', 'btn btn-primary ajax');

        $travelId = $this->travelId;
        Assertion::notNull($travelId);

        $travelId = (int) $travelId; // Persistent parameters aren't auto casted

        $travel = $this->model->getTravel($this->commandId, $travelId);
        Assertion::notNull($travel);

        $form->setDefaults([
            'type' => $travel->getDetails()->getTransportType()->toString(),
            'date' => $travel->getDetails()->getDate()->toNative(),
            'startPlace' => $travel->getDetails()->getStartPlace(),
            'endPlace' => $travel->getDetails()->getEndPlace(),
            'distanceOrPrice' => $travel->getDistance() ?? MoneyFactory::toFloat($travel->getPrice()),
        ]);

        $form->onSuccess[] = function (Form $form) use ($travelId): void {
            $this->formSucceeded($travelId, $form->getValues());
        };

        return $form;
    }

    private function formSucceeded(int $travelId, ArrayHash $values): void
    {
        $this->model->updateTravel(
            $this->commandId,
            $travelId,
            (float) $values->distanceOrPrice,
            new ChronosDate($values->date),
            TransportType::get($values->type),
            $values->startPlace,
            $values->endPlace,
        );
        $this->getPresenter()->redrawControl('travelsTable');
        $this->flashMessage('Cesta byla upravena.');
        $this->hide();
    }
}
