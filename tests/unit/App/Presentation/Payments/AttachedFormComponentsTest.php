<?php

declare(strict_types=1);

namespace App\Presentation\Payments;

use App\BasePresenter;
use App\BaseSectionPresenter;
use App\Components\Factories\Payment\IGroupFormFactory;
use App\Components\Factories\Payment\IMassAddFormFactory;
use App\Components\Payment\GroupForm;
use App\Components\Payment\MassAddForm;
use App\Model\Common\Registration;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\DTO\Participant\Participant;
use App\Model\DTO\Payment\Group;
use App\Model\Participant\Payment;
use App\Model\Participant\Payment\Event as PaymentEvent;
use App\Model\Participant\Payment\EventType;
use App\Model\Participant\PaymentId;
use App\Model\Payment\Group\SkautisEntity;
use App\Model\Payment\Group\Type;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\EventParticipantsWithoutPaymentQuery;
use App\Model\Payment\ReadModel\Queries\NextVariableSymbolSequenceQuery;
use App\Model\Payment\ReadModel\Queries\RegistrationWithoutGroupQuery;
use App\Model\Unit\UnitService;
use App\Model\Utils\MoneyFactory;
use App\Presentation\Payments\EventAddParticipants\EventAddParticipantsPresenter;
use App\Presentation\Payments\RegistrationAddMembers\RegistrationAddMembersPresenter;
use App\Presentation\Payments\RegistrationCreateGroup\RegistrationCreateGroupPresenter;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Component\Forms\BaseContainer;
use Component\Forms\BaseForm;
use Component\Forms\DateControl;
use DateTimeImmutable;
use Latte\Engine;
use Mockery;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Forms\Controls\TextInput;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Utils\FileSystem;
use ReflectionProperty;

use function sys_get_temp_dir;

/**
 * Nette 3.3 vytvoří podkomponentu jen u komponenty připojené k presenteru. Presentery proto
 * smějí formulářové komponenty plnit až v akci přes $this['…'], ne v továrně createComponent*.
 */
final class AttachedFormComponentsTest extends Unit
{
    private const UNIT_ID = 27266;
    private const GROUP_ID = 7;
    private const PERSON_ID = 101;

    private ?Session $session = null;

    public function testEventParticipantsAreAddedToAttachedMassAddForm(): void
    {
        $payments = Mockery::mock(PaymentService::class);
        $payments->shouldReceive('getGroup')->with(self::GROUP_ID)->andReturn($this->group());

        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')->andReturnUsing(
            fn (object $query): array => $query instanceof EventParticipantsWithoutPaymentQuery ? [$this->participant()] : [],
        );

        $formFactory = Mockery::mock(IMassAddFormFactory::class);
        $formFactory->shouldReceive('create')
            ->with(self::GROUP_ID)
            ->andReturn(new MassAddForm(self::GROUP_ID, $payments, Mockery::mock(CommandBus::class)));

        $presenter = new EventAddParticipantsPresenter($payments, $formFactory);
        $this->preparePresenter($presenter, $queryBus);

        $presenter->actionDefault(self::GROUP_ID);

        $massAddForm = $presenter['massAddForm'];
        self::assertInstanceOf(MassAddForm::class, $massAddForm);
        $form = $massAddForm['form'];
        self::assertInstanceOf(BaseForm::class, $form);
        $persons = $form['persons'];
        self::assertInstanceOf(BaseContainer::class, $persons);
        $person = $persons['person'.self::PERSON_ID];
        self::assertInstanceOf(BaseContainer::class, $person);
        $name = $person['name'];
        self::assertInstanceOf(TextInput::class, $name);
        self::assertSame('Pražák Jan', $name->getValue());
    }

    /** SkautIS vrací částky z PersonRegistrationAll se čtyřmi desetinnými místy, např. "800.0000". */
    public function testRegistrationMembersWithSkautisAmountsAreAddedToAttachedMassAddForm(): void
    {
        $payments = Mockery::mock(PaymentService::class);
        $payments->shouldReceive('getGroup')->with(self::GROUP_ID)->andReturn($this->group());
        $payments->shouldReceive('getPersonsFromRegistrationWithoutPayment')
            ->with([self::UNIT_ID], self::GROUP_ID)
            ->andReturn([
                self::PERSON_ID => $this->registrationPerson(self::PERSON_ID, 'Pražák Jan', '800.0000', '200.0000'),
                self::PERSON_ID + 1 => $this->registrationPerson(self::PERSON_ID + 1, 'Novák Petr', '0.0000', '0.0000'),
            ]);

        $formFactory = Mockery::mock(IMassAddFormFactory::class);
        $formFactory->shouldReceive('create')
            ->with(self::GROUP_ID)
            ->andReturn(new MassAddForm(self::GROUP_ID, $payments, Mockery::mock(CommandBus::class)));

        $presenter = new RegistrationAddMembersPresenter($formFactory, $payments);
        $this->preparePresenter($presenter, Mockery::mock(QueryBus::class));
        (new ReflectionProperty(RegistrationAddMembersPresenter::class, 'readUnits'))->setValue($presenter, [self::UNIT_ID => 'Oddíl']);

        $presenter->actionDefault(self::GROUP_ID);

        $massAddForm = $presenter['form'];
        self::assertInstanceOf(MassAddForm::class, $massAddForm);
        $form = $massAddForm['form'];
        self::assertInstanceOf(BaseForm::class, $form);
        $persons = $form['persons'];
        self::assertInstanceOf(BaseContainer::class, $persons);
        self::assertNull($persons->getComponent('person'.(self::PERSON_ID + 1), false));
        $person = $persons['person'.self::PERSON_ID];
        self::assertInstanceOf(BaseContainer::class, $person);
        $amount = $person['amount'];
        self::assertInstanceOf(TextInput::class, $amount);
        self::assertSame('800.00', $amount->getValue());
        $note = $person['note'];
        self::assertInstanceOf(TextInput::class, $note);
        self::assertSame('1x STS', $note->getValue());
    }

    public function testRegistrationGroupFormIsPrefilledAfterAttaching(): void
    {
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')->andReturnUsing(
            static fn (object $query): mixed => match (true) {
                $query instanceof RegistrationWithoutGroupQuery => new Registration(5, 'Středisko', 2027),
                $query instanceof NextVariableSymbolSequenceQuery => null,
                default => [],
            },
        );

        $formFactory = Mockery::mock(IGroupFormFactory::class);
        $formFactory->shouldReceive('create')->andReturn(new GroupForm(
            new UnitId(self::UNIT_ID),
            new SkautisEntity(5, Type::get(Type::REGISTRATION)),
            null,
            null,
            Mockery::mock(PaymentService::class),
            $queryBus,
        ));

        $unitService = Mockery::mock(UnitService::class);
        $unitService->shouldReceive('getUnitId')->andReturn(self::UNIT_ID);

        $presenter = new RegistrationCreateGroupPresenter($formFactory);
        $this->preparePresenter($presenter, $queryBus);
        (new ReflectionProperty(BasePresenter::class, 'unitService'))->setValue($presenter, $unitService);

        $presenter->actionDefault();

        $groupForm = $presenter['form'];
        self::assertInstanceOf(GroupForm::class, $groupForm);
        $form = $groupForm['form'];
        self::assertInstanceOf(BaseForm::class, $form);
        $name = $form['name'];
        self::assertInstanceOf(TextInput::class, $name);
        self::assertSame('Registrace 2027', $name->getValue());
        $dueDate = $form['dueDate'];
        self::assertInstanceOf(DateControl::class, $dueDate);
        $dueDateValue = $dueDate->getValue();
        self::assertInstanceOf(DateTimeImmutable::class, $dueDateValue);
        self::assertSame('2027-01-15', $dueDateValue->format('Y-m-d'));
    }

    protected function _after(): void
    {
        // Další testy si nastavují vlastní session.save_path, to jde jen u zavřené session.
        $this->session?->close();
        Mockery::close();
    }

    private function preparePresenter(PaymentsBasePresenter $presenter, QueryBus $queryBus): void
    {
        $request = new Request(new UrlScript('http://localhost/'));
        $response = new Response();
        $sessionSavePath = sys_get_temp_dir().'/hskauting-attached-form-components-test-sessions';
        FileSystem::createDir($sessionSavePath);
        $this->session = (new Session($request, $response))->setSavePath($sessionSavePath);
        $presenter->injectPrimary($request, $response, session: $this->session);

        (new ReflectionProperty(BasePresenter::class, 'queryBus'))->setValue($presenter, $queryBus);
        (new ReflectionProperty(BaseSectionPresenter::class, 'unitId'))->setValue($presenter, new UnitId(self::UNIT_ID));
        (new ReflectionProperty(BaseSectionPresenter::class, 'isEditable'))->setValue($presenter, true);
        (new ReflectionProperty(Control::class, 'template'))->setValue($presenter, new DefaultTemplate(new Engine()));
    }

    private function group(): Group
    {
        return new Group(
            self::GROUP_ID,
            null,
            [self::UNIT_ID],
            null,
            'Skupina',
            MoneyFactory::fromDecimal('100.00'),
            new ChronosDate('2026-06-19'),
            308,
            null,
            'open',
            null,
            '',
            null,
        );
    }

    /** @return array{ID_Person: int, Person: string, AmountTotal: string, AmountServices: string, emails: list<never>} */
    private function registrationPerson(int $personId, string $name, string $amountTotal, string $amountServices): array
    {
        return [
            'ID_Person' => $personId,
            'Person' => $name,
            'AmountTotal' => $amountTotal,
            'AmountServices' => $amountServices,
            'emails' => [],
        ];
    }

    private function participant(): Participant
    {
        return new Participant(
            1,
            self::PERSON_ID,
            'Jan',
            'Pražák',
            null,
            10,
            new ChronosDate('2016-01-01'),
            'Ulice',
            'Praha 4',
            14000,
            'accepted',
            'Oddíl',
            '11123.45',
            5,
            true,
            new Payment(
                PaymentId::generate(),
                1,
                new PaymentEvent(11, EventType::GENERAL()),
                MoneyFactory::zero(),
                MoneyFactory::zero(),
                'N',
            ),
            null,
            false,
        );
    }
}
