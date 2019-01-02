<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Auth\IAuthorizator;
use Model\Auth\Resources\Event;
use Model\Event\AssistantNotAdult;
use Model\Event\Commands\Event\UpdateFunctions;
use Model\Event\Functions;
use Model\Event\LeaderNotAdult;
use Model\Event\Person;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\SkautisEventId;
use Model\MemberService;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Skautis\Wsdl\PermissionException;

class FunctionsControl extends BaseControl
{
    /** @var int */
    private $eventId;

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    /** @var MemberService */
    private $members;

    /** @var IAuthorizator */
    private $authorizator;

    /**
     * @persistent
     * @var bool
     */
    public $editation = false;

    public function __construct(
        int $eventId,
        CommandBus $commandBus,
        QueryBus $queryBus,
        MemberService $members,
        IAuthorizator $authorizator
    ) {
        parent::__construct();
        $this->eventId      = $eventId;
        $this->commandBus   = $commandBus;
        $this->queryBus     = $queryBus;
        $this->members      = $members;
        $this->authorizator = $authorizator;
    }

    private function reload(?string $message = null, ?string $type = null) : void
    {
        if ($message !== null) {
            $this->presenter->flashMessage($message, $type);
        }
        if ($this->presenter->isAjax()) {
            $this->redrawControl();
        } else {
            $this->redirect('this');
        }
    }

    private function canEdit() : bool
    {
        return $this->authorizator->isAllowed(Event::UPDATE_FUNCTION, $this->eventId);
    }

    public function handleEdit() : void
    {
        $this->editation = $this->canEdit();
        $this->reload();
    }

    public function handleCloseEditation() : void
    {
        $this->editation = false;
        $this->reload();
    }

    protected function createComponentForm() : BaseForm
    {
        $form             = new BaseForm();
        $personsOlderThan = $this->getPersonsOlderThan([15, 18]);

        $form->addSelect('leader', 'Vedoucí', $personsOlderThan[18])
            ->setPrompt('')
            ->setAttribute('class', 'combobox')
            ->setAttribute('data-autocomplete');

        $form->addSelect('assistant', 'Zástupce', $personsOlderThan[18])
            ->setPrompt('')
            ->setAttribute('class', 'combobox')
            ->setAttribute('data-autocomplete');

        $form->addSelect('accountant', 'Hospodář', $personsOlderThan[15])
            ->setPrompt('')
            ->setAttribute('class', 'combobox')
            ->setAttribute('data-autocomplete');

        $form->addSelect('medic', 'Zdravotník', $personsOlderThan[15])
            ->setPrompt('')
            ->setAttribute('class', 'combobox')
            ->setAttribute('data-autocomplete');

        $form->addSubmit('save', 'Uložit')
            ->setAttribute('class', 'btn btn-sm btn-primary ajax');

        $this->setDefaultValues($form);

        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values) : void {
            $this->formSubmitted($form, $values);
        };
        return $form;
    }

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/FunctionsControl.latte');
        $this->template->setParameters([
            'functions' => $this->getCurrentFunctions(),
            'editation' => $this->editation,
            'canEdit'   => $this->canEdit(),
        ]);
        $this->template->render();
    }

    private function formSubmitted(BaseForm $form, ArrayHash $values) : void
    {
        if (! $this->canEdit()) {
            $this->reload('Nemáte oprávnění upravit vedení akce', 'danger');
        }
        try {
            $this->commandBus->handle(
                new UpdateFunctions(
                    $this->eventId,
                    $values->leader,
                    $values->assistant,
                    $values->accountant,
                    $values->medic
                )
            );

            $this->handleCloseEditation();
            $this->reload('Funkce uloženy.', 'success');
            return;
        } catch (PermissionException $exc) {
            $form->addError($exc->getMessage());
            $this->reload($exc->getMessage(), 'danger');
        } catch (LeaderNotAdult $e) {
            $form->addError('Vedoucí akce musí být dosplělá osoba.');
            $this->reload();
        } catch (AssistantNotAdult $e) {
            $form->addError('Zástupce musí být dosplělá osoba.');
            $this->reload();
        }
        $this->reload('Nepodařilo se upravit funkce', 'danger');
    }

    private function setDefaultValues(Form $form) : void
    {
        $selected = $this->getCurrentFunctions();

        $values = [
            'leader' => $this->getIdOrNull($selected->getLeader()),
            'assistant' => $this->getIdOrNull($selected->getAssistant()),
            'accountant' => $this->getIdOrNull($selected->getAccountant()),
            'medic' => $this->getIdOrNull($selected->getMedic()),
        ];

        foreach ($values as $functionName => $personId) {
            /** @var SelectBox $selectbox */
            $selectbox = $form[$functionName];
            $selectbox->setDefaultValue(isset($selectbox->getItems()[$personId]) ? $personId : null);
        }
    }

    /**
     * @param int[] $ages
     * @return mixed[] - [age => [person id => name], ...]
     */
    private function getPersonsOlderThan(array $ages) : array
    {
        $persons = [];
        foreach ($ages as $age) {
            $persons[$age] = $this->members->getCombobox(false, $age);
        }

        return $persons;
    }

    private function getIdOrNull(?Person $person) : ?int
    {
        if ($person === null) {
            return null;
        }

        return $person->getId();
    }

    private function getCurrentFunctions() : Functions
    {
        return $this->queryBus->handle(new EventFunctions(new SkautisEventId($this->eventId)));
    }
}
