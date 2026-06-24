<?php

declare(strict_types=1);

namespace App\Presentation\Events\NewEvent;

use App\Model\Auth\Resources\Event as EventResource;
use App\Model\Event\Commands\Event\CreateEvent;
use App\Model\Event\ReadModel\Queries\EventScopes;
use App\Model\Event\ReadModel\Queries\EventTypes;
use App\Model\Event\ReadModel\Queries\NewestEventId;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Model\Unit\Unit;
use App\MyValidators;
use App\Presentation\Events\BasePresenter;
use Cake\Chronos\ChronosDate;
use Component\Forms\BaseForm;
use Nette\Application\UI\Form;

use function array_map;
use function assert;

final class NewEventPresenter extends BasePresenter
{
    protected function startup(): void
    {
        parent::startup();

        if (! $this->authorizator->isAllowed(EventResource::CREATE, null)) {
            $this->setView('accessDenied');
            $this->template->setParameters(['message' => 'Nemáte oprávnění založit novou akci.']);

            return;
        }
    }

    protected function createComponentForm(): BaseForm
    {
        $scopes = $this->queryBus->handle(new EventScopes());
        $types = $this->queryBus->handle(new EventTypes());
        $unitId = $this->unitService->getUnitId();

        $subunits = $this->unitService->getSubunitPairs($unitId);
        $subunits = array_map(
            function (string $name): string {
                return '» '.$name;
            },
            $subunits,
        );

        $unit = $this->queryBus->handle(new UnitQuery($unitId));
        assert($unit instanceof Unit);
        $units = [$unitId => $unit->getSortName()] + $subunits;

        $form = new BaseForm();

        $form->addText('name', 'Název akce')
            ->addRule(Form::FILLED, 'Musíte vyplnit název akce');
        $form->addDate('start', 'Od')
            ->addRule(Form::FILLED, 'Musíte vyplnit začátek akce')
            ->addRule([MyValidators::class, 'isValidDate'], 'Vyplňte platné datum.');
        $form->addDate('end', 'Do')
            ->addRule(Form::FILLED, 'Musíte vyplnit konec akce')
            ->addRule([MyValidators::class, 'isValidDate'], 'Vyplňte platné datum.')
            ->addRule([MyValidators::class, 'isValidRange'], 'Konec akce musí být po začátku akce', $form['start']);
        $form->addText('location', 'Místo');
        $form->addSelect('orgID', 'Pořádající jednotka', $units);
        $form->addSelect('scope', 'Rozsah (+)', $scopes)
            ->setDefaultValue('2');
        $form->addSelect('type', 'Typ (+)', $types)
            ->setDefaultValue('2');
        $form->addSubmit('send', 'Založit novou akci')
            ->setHtmlAttribute('class', 'btn btn-primary btn-lg ui--createEvent');

        $form->onSuccess[] = function (Form $form): void {
            $this->formCreateSubmitted($form);
        };

        return $form;
    }

    private function formCreateSubmitted(Form $form): void
    {
        if (! $this->authorizator->isAllowed(EventResource::CREATE, null)) {
            $this->flashMessage('Nemáte oprávnění pro založení akce', 'danger');
            $this->redirect('this');
        }

        $v = $form->getValues();

        $startDate = $v['start'];
        $endDate = $v['end'];

        $this->commandBus->handle(
            new CreateEvent(
                $v['name'],
                new ChronosDate($startDate),
                new ChronosDate($endDate),
                $v->orgID,
                $v['location'] !== '' ? $v['location'] : null,
                $v['scope'],
                $v['type'],
            ),
        );

        $this->redirect('Event:', ['aid' => $this->queryBus->handle(new NewestEventId())]);
    }
}
