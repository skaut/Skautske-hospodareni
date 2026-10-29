<?php

declare(strict_types=1);

namespace App\Components\Participants;

use App\BasePresenter;
use App\Components\BaseControl;
use App\Helpers\AccountancyLatteExtension;
use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\DTO\Participant\NonMemberParticipant;
use App\Model\DTO\Participant\Participant;
use App\Model\DTO\Participant\UpdateParticipant;
use App\Model\Participant\NonMemberParticipantService;
use App\Model\Participant\Payment;
use App\Model\Participant\Payment\Event;
use App\Model\Participant\Payment\EventType;
use App\Model\Participant\PaymentId;
use App\Model\Participant\PersonAccessNotAllowed;
use App\Model\Participant\PersonAddressChangeNotAllowed;
use App\Model\Utils\MoneyFactory;
use App\RouterFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Component\Forms\BaseForm;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Latte\Engine;
use Mockery;
use Mockery\MockInterface;
use Nette\Application\PresenterFactory;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Bridges\ApplicationLatte\TemplateFactory;
use Nette\Bridges\ApplicationLatte\UIExtension;
use Nette\Bridges\FormsLatte\FormsExtension;
use Nette\Forms\Controls\BaseControl as FormControl;
use Nette\Forms\Controls\CsrfProtection;
use Nette\Forms\Form;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Utils\FileSystem;
use ReflectionProperty;
use RuntimeException;

use function libxml_use_internal_errors;
use function ob_get_clean;
use function ob_start;
use function preg_replace;
use function sys_get_temp_dir;
use function trim;

use const LIBXML_NOERROR;

/**
 * Nečlen s neúplnými nebo nedostupnými osobními údaji se musí načíst, uživatel se má dozvědět, co chybí,
 * a když osobní údaje nejde uložit, musí jít uložit aspoň účast a platba.
 */
final class NonMemberPersonalDataTest extends Unit
{
    private const AID = 20386;
    private const PARTICIPANT_ID = 363533;
    private const PERSON_ID = 472796;
    private const SAVE_WITHOUT_PERSONAL_DATA = 'Přesto uložit bez osobních údajů';

    private ?Session $session = null;

    /** @var list<array{array<string, int|string>, bool}> změny účasti a příznak uložení bez osobních údajů */
    private array $updates = [];

    public function testParticipantListWarnsAboutIncompleteAndInaccessiblePersonalData(): void
    {
        $repository = Mockery::mock(IParticipantRepository::class);
        $repository->shouldReceive('getNonMemberParticipant')->once()->with(102)->andReturn($this->nonMember(sex: ''));
        $repository->shouldReceive('getNonMemberParticipant')
            ->once()
            ->with(103)
            ->andThrow(new PersonAccessNotAllowed(103, new RuntimeException()));
        $repository->shouldReceive('getNonMemberParticipant')->once()->with(104)->andReturn($this->nonMember(street: '', postcode: ''));
        $repository->shouldReceive('getNonMemberParticipant')->once()->with(105)->andReturn($this->nonMember());

        $list = new ParticipantList(
            self::AID,
            [
                $this->participant(11, 101, isNonMember: false),
                $this->participant(12, 102),
                $this->participant(13, 103),
                $this->participant(14, 104),
                $this->participant(15, 105),
            ],
            true,
            true,
            true,
            true,
            true,
            false,
            true,
            new NonMemberParticipantService($repository),
        );
        $this->attach($list);

        // Presenter osobní údaje načítá před vykreslením stránky, vykreslení se pak na skautIS už neptá.
        $list->loadPersonalDataWarnings();
        $xpath = $this->render($list);

        self::assertSame([], $this->texts($xpath, '//tr[@data-pid="11"]//*[@data-test="participant-personal-data-warning"]'));
        self::assertSame(['Chybí pohlaví'], $this->texts($xpath, '//tr[@data-pid="12"]//*[@data-test="participant-personal-data-warning"]'));
        self::assertSame(
            ['Osobní údaje nejsou dostupné, chybí oprávnění ve skautISu'],
            $this->texts($xpath, '//tr[@data-pid="13"]//*[@data-test="participant-personal-data-warning"]'),
        );
        self::assertSame(['Chybí adresa'], $this->texts($xpath, '//tr[@data-pid="14"]//*[@data-test="participant-personal-data-warning"]'));
        self::assertSame([], $this->texts($xpath, '//tr[@data-pid="15"]//*[@data-test="participant-personal-data-warning"]'));
    }

    public function testChangingOnlyPaymentDoesNotSendPersonDataToSkautis(): void
    {
        $repository = $this->repository($this->nonMember());
        $repository->shouldNotReceive('updateNonMemberParticipant');

        $dialog = $this->dialog($repository);
        $this->collectUpdates($dialog);

        $this->submit($dialog, 'save', ['payment' => '150']);

        self::assertSame([[[UpdateParticipant::FIELD_PAYMENT => '150.00'], false]], $this->updates);
    }

    public function testCompletePersonalDataDoesNotOfferSavingWithoutThem(): void
    {
        $xpath = $this->render($this->dialog($this->repository($this->nonMember())));

        self::assertSame([], $this->texts($xpath, '//*[contains(@class, "alert")]'));
        self::assertSame(0, $this->countNodes($xpath, '//input[@value="'.self::SAVE_WITHOUT_PERSONAL_DATA.'"]'));
    }

    public function testIncompletePersonalDataWarnsAndOffersSavingWithoutThem(): void
    {
        $xpath = $this->render($this->dialog($this->repository($this->nonMember(sex: '', city: ''))));

        self::assertStringContainsString(
            'Osobní údaje nejsou úplné: chybí pohlaví a adresa.',
            $this->texts($xpath, '//*[@data-test="participant-personal-data-incomplete"]')[0] ?? '',
        );
        self::assertSame(1, $this->countNodes($xpath, '//input[@value="'.self::SAVE_WITHOUT_PERSONAL_DATA.'"]'));
    }

    /** Uživatel chybějící údaj nezná – účast a platba se uloží bez validace a bez odeslání osobních údajů. */
    public function testSavingWithoutPersonalDataStoresOnlyParticipation(): void
    {
        $repository = $this->repository($this->nonMember(sex: ''));
        $repository->shouldNotReceive('updateNonMemberParticipant');

        $dialog = $this->dialog($repository);
        $this->collectUpdates($dialog);

        $this->submit($dialog, 'saveWithoutPersonalData', ['payment' => '150', 'sex' => null]);

        self::assertSame([[[UpdateParticipant::FIELD_PAYMENT => '150.00'], true]], $this->updates);
    }

    public function testSavingWithoutPersonalDataStillChecksCsrfToken(): void
    {
        $dialog = $this->dialog($this->repository($this->nonMember(sex: '')));
        $this->collectUpdates($dialog);

        $this->submit($dialog, 'saveWithoutPersonalData', ['payment' => '150', Form::ProtectorId => 'neplatný']);

        self::assertSame([], $this->updates);
    }

    public function testRejectedAddressChangeOffersSavingWithoutPersonalData(): void
    {
        $repository = $this->repository($this->nonMember());
        $repository->shouldReceive('updateNonMemberParticipant')
            ->once()
            ->with(self::PERSON_ID, Mockery::on(static fn (NonMemberParticipant $p): bool => $p->getStreet() === 'Nová 1'))
            ->andThrow(new PersonAddressChangeNotAllowed(self::PERSON_ID, new RuntimeException()));

        $dialog = $this->dialog($repository);
        $this->collectUpdates($dialog);

        $this->submit($dialog, 'save', ['street' => 'Nová 1', 'payment' => '150']);
        $xpath = $this->render($dialog);

        self::assertSame([], $this->updates);
        self::assertTrue($dialog->opened);
        $alert = $this->texts($xpath, '//*[@data-test="participant-personal-data-save-error"]')[0] ?? '';
        self::assertStringContainsString('Osobní údaje se nepodařilo uložit.', $alert);
        self::assertStringContainsString(EditParticipantDialog::ADDRESS_CHANGE_NOT_ALLOWED_MESSAGE, $alert);
        self::assertSame(1, $this->countNodes($xpath, '//input[@value="'.self::SAVE_WITHOUT_PERSONAL_DATA.'"]'));
    }

    public function testRejectedPersonUpdateExplainsMissingPermission(): void
    {
        $repository = $this->repository($this->nonMember());
        $repository->shouldReceive('updateNonMemberParticipant')
            ->once()
            ->andThrow(new PersonAccessNotAllowed(self::PERSON_ID, new RuntimeException()));

        $dialog = $this->dialog($repository);
        $this->submit($dialog, 'save', ['firstName' => 'Justýnka']);
        $xpath = $this->render($dialog);

        self::assertStringContainsString(
            EditParticipantDialog::PERSON_UPDATE_NOT_ALLOWED_MESSAGE,
            $this->texts($xpath, '//*[@data-test="participant-personal-data-save-error"]')[0] ?? '',
        );
    }

    public function testInaccessiblePersonalDataLeavesOnlyParticipationEditable(): void
    {
        $repository = Mockery::mock(IParticipantRepository::class);
        $repository->shouldReceive('getNonMemberParticipant')
            ->with(self::PERSON_ID)
            ->andThrow(new PersonAccessNotAllowed(self::PERSON_ID, new RuntimeException()));
        $repository->shouldNotReceive('updateNonMemberParticipant');

        $dialog = $this->dialog($repository);
        $xpath = $this->render($dialog);

        self::assertStringContainsString(
            'K osobním údajům tohoto účastníka nemáte ve skautISu oprávnění.',
            $this->texts($xpath, '//*[@data-test="participant-personal-data-not-accessible"]')[0] ?? '',
        );
        self::assertSame(0, $this->countNodes($xpath, '//input[@name="street"]'));
        self::assertSame(0, $this->countNodes($xpath, '//input[@value="'.self::SAVE_WITHOUT_PERSONAL_DATA.'"]'));

        $this->collectUpdates($dialog);
        $this->submit($dialog, 'save', ['payment' => '150']);

        self::assertSame([[[UpdateParticipant::FIELD_PAYMENT => '150.00'], false]], $this->updates);
    }

    protected function _after(): void
    {
        // Další testy si nastavují vlastní session.save_path, to jde jen u zavřené session.
        $this->session?->close();
        Mockery::close();
    }

    private function repository(NonMemberParticipant $nonMember): IParticipantRepository&MockInterface
    {
        $repository = Mockery::mock(IParticipantRepository::class);
        $repository->shouldReceive('getNonMemberParticipant')->with(self::PERSON_ID)->andReturn($nonMember);

        return $repository;
    }

    private function dialog(IParticipantRepository $repository): EditParticipantDialog
    {
        $dialog = new EditParticipantDialog(
            [self::PARTICIPANT_ID => $this->participant(self::PARTICIPANT_ID, self::PERSON_ID)],
            false,
            false,
            false,
            false,
            true,
            new NonMemberParticipantService($repository),
        );
        $dialog->participantId = self::PARTICIPANT_ID;
        $dialog->opened = true;
        $this->attach($dialog);

        return $dialog;
    }

    private function collectUpdates(EditParticipantDialog $dialog): void
    {
        $dialog->onUpdate[] = function (int $participantId, array $changes, bool $isAccepted, bool $withoutPersonalData): void {
            $this->updates[] = [$changes, $withoutPersonalData];
        };
    }

    /**
     * Odešle formulář stejně jako Nette při signálu: načte data do prvků, rozhodne o tlačítku a spustí validaci i události.
     *
     * @param array<string, string|null> $changedValues
     */
    private function submit(EditParticipantDialog $dialog, string $button, array $changedValues): void
    {
        $form = $dialog['form'];
        self::assertInstanceOf(BaseForm::class, $form);
        $protection = $form[Form::ProtectorId];
        self::assertInstanceOf(CsrfProtection::class, $protection);

        $data = $changedValues + [
            'firstName' => 'Justýna',
            'lastName' => 'Navrátilová',
            'street' => 'Bukovinka 105',
            'city' => 'Bukovinka',
            'postcode' => '679 05',
            'nick' => '',
            'sex' => 'female',
            'birthday' => '14.10.2019',
            'payment' => '100',
            Form::ProtectorId => (string) $protection->getControl()->getAttribute('value'),
            $button => 'odeslat',
        ];

        (new ReflectionProperty(Form::class, 'httpData'))->setValue($form, $data);
        foreach ($form->getControls() as $control) {
            if ($control instanceof FormControl && ! $control->isDisabled()) {
                $control->loadHttpData();
            }
        }

        $form->fireEvents();
    }

    private function attach(BaseControl $control): void
    {
        $presenter = new class extends BasePresenter {
        };
        $presenter->setParent(null, 'Camps:Participant');

        $request = new Request(new UrlScript('http://localhost/tabory/'.self::AID.'/ucastnici', '/'), method: 'POST');
        $response = new Response();
        $sessionSavePath = sys_get_temp_dir().'/hskauting-non-member-personal-data-test-sessions';
        FileSystem::createDir($sessionSavePath);
        $this->session = (new Session($request, $response))->setSavePath($sessionSavePath);

        $latteFactory = new class implements LatteFactory {
            public function create(?Control $control = null): Engine
            {
                $latte = new Engine();
                $latte->addExtension(new UIExtension($control));
                $latte->addExtension(new FormsExtension());
                $latte->addExtension(new AccountancyLatteExtension());

                return $latte;
            }
        };

        $presenter->injectPrimary(
            $request,
            $response,
            new PresenterFactory(),
            (new RouterFactory())->createRouter(),
            $this->session,
            templateFactory: new TemplateFactory($latteFactory),
        );
        $presenter->addComponent($control, 'component');
    }

    private function render(BaseControl $control): DOMXPath
    {
        ob_start();
        try {
            $control->render();
        } finally {
            $html = (string) ob_get_clean();
        }

        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>', LIBXML_NOERROR);

        return new DOMXPath($document);
    }

    /** @return list<string> */
    private function texts(DOMXPath $xpath, string $query): array
    {
        $nodes = $xpath->query($query);
        self::assertNotFalse($nodes, $query);

        $texts = [];
        foreach ($nodes as $node) {
            self::assertInstanceOf(DOMElement::class, $node);
            $texts[] = trim((string) preg_replace('/\s+/u', ' ', $node->textContent));
        }

        return $texts;
    }

    private function countNodes(DOMXPath $xpath, string $query): int
    {
        $nodes = $xpath->query($query);
        self::assertNotFalse($nodes, $query);

        return $nodes->length;
    }

    private function participant(int $id, int $personId, bool $isNonMember = true): Participant
    {
        return new Participant(
            $id,
            $personId,
            'Justýna',
            'Navrátilová '.$id,
            null,
            7,
            new ChronosDate('2019-10-14'),
            'Bukovinka 105',
            'Bukovinka',
            67905,
            'Jihomoravský',
            '',
            '',
            3,
            true,
            new Payment(
                PaymentId::generate(),
                $id,
                new Event(28388, EventType::CAMP()),
                MoneyFactory::fromDecimal('100'),
                MoneyFactory::zero(),
            ),
            null,
            $isNonMember,
        );
    }

    private function nonMember(string $sex = 'female', string $street = 'Bukovinka 105', string $city = 'Bukovinka', string $postcode = '679 05'): NonMemberParticipant
    {
        return new NonMemberParticipant('Justýna', 'Navrátilová', null, $sex, new ChronosDate('2019-10-14'), $street, $city, $postcode);
    }
}
