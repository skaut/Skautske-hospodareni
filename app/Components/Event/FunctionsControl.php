<?php

declare(strict_types=1);

namespace App\Components\Event;

use App\Components\BaseControl;
use App\Model\Auth\IAuthorizator;
use App\Model\Auth\Resources\Event;
use App\Model\Common\ReadModel\Queries\MemberNamesQuery;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\Event\AssistantNotAdult;
use App\Model\Event\Commands\Event\UpdateFunctions;
use App\Model\Event\Functions;
use App\Model\Event\LeaderNotAdult;
use App\Model\Event\Person;
use App\Model\Event\ReadModel\Queries\EventFunctions;
use App\Model\Event\SkautisEventId;
use Component\Forms\BaseForm;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Skautis\Wsdl\PermissionException;

use function assert;

class FunctionsControl extends BaseControl
{
    /** @persistent */
    public bool $editation = false;

    public function __construct(
        private int $eventId,
        private UnitId $unitId,
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private IAuthorizator $authorizator,
    ) {
    }

    private function canEdit(): bool
    {
        return $this->authorizator->isAllowed(Event::UPDATE_FUNCTION, $this->eventId);
    }

    public function handleEdit(): void
    {
        $this->editation = $this->canEdit();
        $this->reload();
    }

    public function handleCloseEditation(): void
    {
        $this->editation = false;
        $this->reload();
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $personsOlderThan = $this->getPersonsOlderThan([15, 18]);

        $form->addSelect('leader', 'Vedoucí', $personsOlderThan[18])
            ->setPrompt('')
            ->setHtmlAttribute('class', 'combobox');

        $form->addSelect('assistant', 'Zástupce', $personsOlderThan[18])
            ->setPrompt('')
            ->setHtmlAttribute('class', 'combobox');

        $form->addSelect('accountant', 'Hospodář', $personsOlderThan[15])
            ->setPrompt('')
            ->setHtmlAttribute('class', 'combobox');

        $form->addSelect('medic', 'Zdravotník', $personsOlderThan[15])
            ->setPrompt('')
            ->setHtmlAttribute('class', 'combobox');

        $form->addSubmit('save', 'Uložit')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary ajax');

        $this->setDefaultValues($form);

        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values): void {
            $this->formSubmitted($form, $values);
        };

        return $form;
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/FunctionsControl.latte');
        $this->template->setParameters([
            'functions' => $this->getCurrentFunctions(),
            'editation' => $this->editation,
            'canEdit' => $this->canEdit(),
        ]);
        $this->template->render();
    }

    private function formSubmitted(BaseForm $form, ArrayHash $values): void
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
                    $values->medic,
                ),
            );

            $this->handleCloseEditation();
            $this->reload('Funkce uloženy.', 'success');

            return;
        } catch (PermissionException $exc) {
            $form->addError($exc->getMessage());
            $this->reload($exc->getMessage(), 'danger');
        } catch (LeaderNotAdult) {
            $form->addError('Vedoucí akce musí být dosplělá osoba.');
            $this->reload();
        } catch (AssistantNotAdult) {
            $form->addError('Zástupce musí být dosplělá osoba.');
            $this->reload();
        }

        $this->reload('Nepodařilo se upravit funkce', 'danger');
    }

    private function setDefaultValues(Form $form): void
    {
        $selected = $this->getCurrentFunctions();

        $values = [
            'leader' => $this->getIdOrNull($selected->getLeader()),
            'assistant' => $this->getIdOrNull($selected->getAssistant()),
            'accountant' => $this->getIdOrNull($selected->getAccountant()),
            'medic' => $this->getIdOrNull($selected->getMedic()),
        ];

        foreach ($values as $functionName => $personId) {
            $selectbox = $form[$functionName];

            assert($selectbox instanceof SelectBox);

            $selectbox->setDefaultValue(isset($selectbox->getItems()[$personId]) ? $personId : null);
        }
    }

    /**
     * @param int[] $ages
     *
     * @return array<int, array<int, string>> - age => [person id => name, ...]
     */
    private function getPersonsOlderThan(array $ages): array
    {
        $persons = [];
        foreach ($ages as $age) {
            $persons[$age] = $this->queryBus->handle(new MemberNamesQuery($this->unitId, $age));
        }

        return $persons;
    }

    private function getIdOrNull(?Person $person): ?int
    {
        if ($person === null) {
            return null;
        }

        return $person->getId();
    }

    private function getCurrentFunctions(): Functions
    {
        return $this->queryBus->handle(new EventFunctions(new SkautisEventId($this->eventId)));
    }
}
