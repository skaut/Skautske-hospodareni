<?php

declare(strict_types=1);

namespace App\Presentation;

use App\BasePresenter;
use App\BaseSectionPresenter;
use App\Components\Factories\ICashbookControlFactory;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\Category as CashbookCategory;
use App\Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationParticipantCategoryIdQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationParticipantIncomeQuery;
use App\Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\DTO\Cashbook\Category;
use App\Model\Event\Education;
use App\Model\Event\Event;
use App\Model\Event\Functions;
use App\Model\Event\ReadModel\Queries\EventFunctions;
use App\Model\Event\SkautisEducationId;
use App\Model\Event\SkautisEventId;
use App\Model\Utils\MoneyFactory;
use App\Presentation\Education\BasePresenter as EducationBasePresenter;
use App\Presentation\Education\Cashbook\CashbookPresenter as EducationCashbookPresenter;
use App\Presentation\Events\BasePresenter as EventBasePresenter;
use App\Presentation\Events\Cashbook\CashbookPresenter as EventCashbookPresenter;
use App\RouterFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Component\Forms\BaseForm;
use Latte\Engine;
use LogicException;
use Mockery;
use Money\Money;
use Nette\Application\AbortException;
use Nette\Application\PresenterFactory;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\UI\Component;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Utils\FileSystem;
use ReflectionProperty;

use function parse_url;
use function sys_get_temp_dir;

use const PHP_URL_PATH;

/**
 * Import účastnických příjmů (HPD) do pokladní knihy vytváří doklad, který musí mít kladnou částku.
 * Nulový nebo záporný součet plateb dřív propadl až do Amount::fromMoney() a skončil výjimkou.
 */
final class ParticipantIncomeImportTest extends Unit
{
    private const EDUCATION_ID = 3380;
    private const EVENT_ID = 4242;
    private const CATEGORY_ID = 1;
    private const NO_INCOME_MESSAGE = 'Nemáte žádné příjmy od účastníků, které by bylo možné importovat.';

    private ?Session $session = null;

    /** @return array<string, array{string}> */
    public function provideNonPositiveIncome(): array
    {
        return [
            'nulové příjmy' => ['0.00'],
            'záporný součet plateb' => ['-150.00'],
        ];
    }

    /** @dataProvider provideNonPositiveIncome */
    public function testEducationImportWithoutPositiveIncomeWarnsAndAddsNoChit(string $income): void
    {
        $presenter = $this->createEducationPresenter(MoneyFactory::fromDecimal($income), $this->commandBusWithoutChit());

        $this->submitEducationImport($presenter);

        self::assertSame([[self::NO_INCOME_MESSAGE, 'warning']], $this->flashMessages($presenter));
        self::assertSame('/vzdelavacky/'.self::EDUCATION_ID.'/kniha', $this->redirectPath($presenter));
    }

    public function testEducationImportAddsChitWithParticipantIncome(): void
    {
        $presenter = $this->createEducationPresenter(
            MoneyFactory::fromDecimal('150.00'),
            $this->commandBusExpectingChit('150.00', self::CATEGORY_ID, 'úč. příspěvky - hotovost'),
        );

        $this->submitEducationImport($presenter);

        self::assertSame([['HPD byl importován', 'info']], $this->flashMessages($presenter));
        self::assertSame('/vzdelavacky/'.self::EDUCATION_ID.'/kniha', $this->redirectPath($presenter));
    }

    /** @dataProvider provideNonPositiveIncome */
    public function testEventImportWithoutPositiveIncomeWarnsAndAddsNoChit(string $income): void
    {
        $presenter = $this->createEventPresenter(MoneyFactory::fromDecimal($income), $this->commandBusWithoutChit());

        $this->importEventIncome($presenter);

        self::assertSame([[self::NO_INCOME_MESSAGE, 'warning']], $this->flashMessages($presenter));
        self::assertSame('/akce/'.self::EVENT_ID.'/kniha', $this->redirectPath($presenter));
    }

    public function testEventImportAddsChitWithParticipantIncome(): void
    {
        $presenter = $this->createEventPresenter(
            MoneyFactory::fromDecimal('150.00'),
            $this->commandBusExpectingChit('150.00', CashbookCategory::EVENT_PARTICIPANTS_INCOME_CATEGORY_ID, 'účastnické příspěvky'),
        );

        $this->importEventIncome($presenter);

        self::assertSame([['Účastníci byli importováni', 'info']], $this->flashMessages($presenter));
        self::assertSame('/akce/'.self::EVENT_ID.'/kniha', $this->redirectPath($presenter));
    }

    protected function _after(): void
    {
        // Další testy si nastavují vlastní session.save_path, to jde jen u zavřené session.
        $this->session?->close();
        Mockery::close();
    }

    private function createEducationPresenter(Money $participantIncome, CommandBus $commandBus): EducationCashbookPresenter
    {
        $cashbookId = CashbookId::generate();
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')->andReturnUsing(
            fn (object $query): mixed => match (true) {
                $query instanceof EducationParticipantIncomeQuery && $query->getEducationId()->toInt() === self::EDUCATION_ID => $participantIncome,
                $query instanceof EducationParticipantCategoryIdQuery => self::CATEGORY_ID,
                $query instanceof EducationCashbookIdQuery => $cashbookId,
                $query instanceof CategoryListQuery => $this->categories(),
                default => throw new LogicException('Neočekávaný dotaz '.$query::class),
            },
        );

        $presenter = new EducationCashbookPresenter(Mockery::mock(ICashbookControlFactory::class));
        $this->preparePresenter($presenter, 'Education:Cashbook', '/vzdelavacky/'.self::EDUCATION_ID.'/kniha', self::EDUCATION_ID, $queryBus, $commandBus);
        (new ReflectionProperty(EducationBasePresenter::class, 'event'))->setValue($presenter, new Education(
            new SkautisEducationId(self::EDUCATION_ID),
            'Čekatelský kurz',
            new UnitId(27266),
            'Středisko',
            '411.12',
            new ChronosDate('2026-10-02'),
            new ChronosDate('2026-10-04'),
            'Praha',
            'draft',
            null,
        ));

        return $presenter;
    }

    private function createEventPresenter(Money $participantIncome, CommandBus $commandBus): EventCashbookPresenter
    {
        $cashbookId = CashbookId::generate();
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')->andReturnUsing(
            fn (object $query): mixed => match (true) {
                $query instanceof EventParticipantIncomeQuery && $query->getEventId()->toInt() === self::EVENT_ID => $participantIncome,
                $query instanceof EventFunctions => new Functions(),
                $query instanceof EventCashbookIdQuery => $cashbookId,
                $query instanceof CategoryListQuery => $this->categories(),
                default => throw new LogicException('Neočekávaný dotaz '.$query::class),
            },
        );

        $presenter = new EventCashbookPresenter(Mockery::mock(ICashbookControlFactory::class));
        $this->preparePresenter($presenter, 'Events:Cashbook', '/akce/'.self::EVENT_ID.'/kniha', self::EVENT_ID, $queryBus, $commandBus);
        $presenter->changeAction('default');
        (new ReflectionProperty(EventBasePresenter::class, 'event'))->setValue($presenter, new Event(
            new SkautisEventId(self::EVENT_ID),
            'Výprava',
            new UnitId(27266),
            'Středisko',
            'draft',
            new ChronosDate('2026-10-02'),
            new ChronosDate('2026-10-04'),
            3,
            'Praha',
            '411.12',
            null,
            1,
            1,
            null,
        ));

        return $presenter;
    }

    private function preparePresenter(
        BaseSectionPresenter $presenter,
        string $name,
        string $path,
        int $aid,
        QueryBus $queryBus,
        CommandBus $commandBus,
    ): void {
        $presenter->setParent(null, $name);

        $request = new Request(new UrlScript('http://localhost'.$path, '/'), method: 'POST');
        $response = new Response();
        $sessionSavePath = sys_get_temp_dir().'/hskauting-participant-income-import-test-sessions';
        FileSystem::createDir($sessionSavePath);
        $this->session = (new Session($request, $response))->setSavePath($sessionSavePath);
        $presenter->injectPrimary($request, $response, new PresenterFactory(), (new RouterFactory())->createRouter(), $this->session);

        (new ReflectionProperty(BasePresenter::class, 'queryBus'))->setValue($presenter, $queryBus);
        (new ReflectionProperty(BasePresenter::class, 'commandBus'))->setValue($presenter, $commandBus);
        (new ReflectionProperty(BaseSectionPresenter::class, 'aid'))->setValue($presenter, $aid);
        (new ReflectionProperty(BaseSectionPresenter::class, 'isEditable'))->setValue($presenter, true);
        (new ReflectionProperty(Control::class, 'template'))->setValue($presenter, new DefaultTemplate(new Engine()));
        // Parametry požadavku, ze kterých redirect('this') skládá odkaz zpět na pokladní knihu.
        (new ReflectionProperty(Component::class, 'params'))->setValue($presenter, ['aid' => $aid]);
    }

    private function commandBusWithoutChit(): CommandBus
    {
        $commandBus = Mockery::mock(CommandBus::class);
        $commandBus->shouldNotReceive('handle');

        return $commandBus;
    }

    private function commandBusExpectingChit(string $amount, int $categoryId, string $purpose): CommandBus
    {
        $commandBus = Mockery::mock(CommandBus::class);
        $commandBus->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(static function (object $command) use ($amount, $categoryId, $purpose): bool {
                if (! $command instanceof AddChitToCashbook || ! $command->getPaymentMethod()->equals(PaymentMethod::CASH())) {
                    return false;
                }

                $items = $command->getItems();

                return $items[0]->getAmount()->getExpression() === $amount
                    && $items[0]->getCategory()->getId() === $categoryId
                    && $items[0]->getPurpose() === $purpose;
            }));

        return $commandBus;
    }

    /** @return array<int, Category> */
    private function categories(): array
    {
        // Vzdělávačka importuje do kategorie z dotazu, výprava do pevné kategorie účastnických příjmů.
        return [
            self::CATEGORY_ID => new Category(self::CATEGORY_ID, 'Účastnické poplatky', 'hpd', Operation::INCOME(), false),
            CashbookCategory::EVENT_PARTICIPANTS_INCOME_CATEGORY_ID => new Category(CashbookCategory::EVENT_PARTICIPANTS_INCOME_CATEGORY_ID, 'Účastnické poplatky', 'hpd', Operation::INCOME(), false),
        ];
    }

    private function submitEducationImport(EducationCashbookPresenter $presenter): void
    {
        $form = $presenter['formImportHpd'];
        self::assertInstanceOf(BaseForm::class, $form);

        try {
            foreach ($form->onSuccess as $handler) {
                $handler($form, $form->getValues());
            }
        } catch (AbortException) {
            return;
        }

        self::fail('Import HPD má vždy skončit přesměrováním.');
    }

    private function importEventIncome(EventCashbookPresenter $presenter): void
    {
        try {
            $presenter->handleImportHpd(self::EVENT_ID);
        } catch (AbortException) {
            return;
        }

        self::fail('Import příjmů má vždy skončit přesměrováním.');
    }

    /** @return list<array{string, string}> */
    private function flashMessages(Presenter $presenter): array
    {
        $template = $presenter->getTemplate();
        self::assertInstanceOf(DefaultTemplate::class, $template);

        $messages = [];
        foreach ($template->flashes as $flash) {
            $messages[] = [$flash->message, $flash->type];
        }

        return $messages;
    }

    private function redirectPath(Presenter $presenter): string
    {
        $response = (new ReflectionProperty(Presenter::class, 'response'))->getValue($presenter);
        self::assertInstanceOf(RedirectResponse::class, $response);

        $path = parse_url($response->getUrl(), PHP_URL_PATH);
        self::assertIsString($path);

        return $path;
    }
}
