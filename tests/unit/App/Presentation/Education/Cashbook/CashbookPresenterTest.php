<?php

declare(strict_types=1);

namespace App\Presentation\Education\Cashbook;

use App\BasePresenter;
use App\BaseSectionPresenter;
use App\Components\Factories\ICashbookControlFactory;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationParticipantCategoryIdQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationParticipantIncomeQuery;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\DTO\Cashbook\Category;
use App\Model\Event\Education;
use App\Model\Event\SkautisEducationId;
use App\Model\Utils\MoneyFactory;
use App\Presentation\Education\BasePresenter as EducationBasePresenter;
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

final class CashbookPresenterTest extends Unit
{
    private const EDUCATION_ID = 3380;
    private const CATEGORY_ID = 1;

    private ?Session $session = null;

    /** Součet příjmů je Money, ne float – nulové příjmy dřív propadly až do Amount::fromMoney(), který nulu odmítne. */
    public function testHpdImportWithZeroParticipantIncomeWarnsAndAddsNoChit(): void
    {
        $commandBus = Mockery::mock(CommandBus::class);
        $commandBus->shouldNotReceive('handle');

        $presenter = $this->createPresenter(MoneyFactory::zero(), $commandBus);

        $this->submitHpdImport($presenter);

        self::assertSame(
            [['Nemáte žádné příjmy od účastníků, které by bylo možné importovat.', 'warning']],
            $this->flashMessages($presenter),
        );
        self::assertSame('/vzdelavacky/'.self::EDUCATION_ID.'/kniha', $this->redirectPath($presenter));
    }

    public function testHpdImportAddsChitWithParticipantIncome(): void
    {
        $commandBus = Mockery::mock(CommandBus::class);
        $commandBus->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(static function (object $command): bool {
                if (! $command instanceof AddChitToCashbook || ! $command->getPaymentMethod()->equals(PaymentMethod::CASH())) {
                    return false;
                }

                $items = $command->getItems();

                return $items[0]->getAmount()->getExpression() === '150.00'
                    && $items[0]->getCategory()->getId() === self::CATEGORY_ID
                    && $items[0]->getPurpose() === 'úč. příspěvky - hotovost';
            }));

        $presenter = $this->createPresenter(MoneyFactory::fromDecimal('150.00'), $commandBus);

        $this->submitHpdImport($presenter);

        self::assertSame([['HPD byl importován', 'info']], $this->flashMessages($presenter));
        self::assertSame('/vzdelavacky/'.self::EDUCATION_ID.'/kniha', $this->redirectPath($presenter));
    }

    protected function _after(): void
    {
        // Další testy si nastavují vlastní session.save_path, to jde jen u zavřené session.
        $this->session?->close();
        Mockery::close();
    }

    private function createPresenter(Money $participantIncome, CommandBus $commandBus): CashbookPresenter
    {
        $cashbookId = CashbookId::generate();
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')->andReturnUsing(
            static fn (object $query): mixed => match (true) {
                $query instanceof EducationParticipantIncomeQuery && $query->getEducationId()->toInt() === self::EDUCATION_ID => $participantIncome,
                $query instanceof EducationParticipantCategoryIdQuery => self::CATEGORY_ID,
                $query instanceof EducationCashbookIdQuery => $cashbookId,
                $query instanceof CategoryListQuery => [
                    self::CATEGORY_ID => new Category(self::CATEGORY_ID, 'Účastnické poplatky', 'hpd', Operation::INCOME(), false),
                ],
                default => throw new LogicException('Neočekávaný dotaz '.$query::class),
            },
        );

        $presenter = new CashbookPresenter(Mockery::mock(ICashbookControlFactory::class));
        $presenter->setParent(null, 'Education:Cashbook');

        $request = new Request(new UrlScript('http://localhost/vzdelavacky/'.self::EDUCATION_ID.'/kniha', '/'), method: 'POST');
        $response = new Response();
        $sessionSavePath = sys_get_temp_dir().'/hskauting-education-cashbook-presenter-test-sessions';
        FileSystem::createDir($sessionSavePath);
        $this->session = (new Session($request, $response))->setSavePath($sessionSavePath);
        $presenter->injectPrimary($request, $response, new PresenterFactory(), (new RouterFactory())->createRouter(), $this->session);

        (new ReflectionProperty(BasePresenter::class, 'queryBus'))->setValue($presenter, $queryBus);
        (new ReflectionProperty(BasePresenter::class, 'commandBus'))->setValue($presenter, $commandBus);
        (new ReflectionProperty(BaseSectionPresenter::class, 'aid'))->setValue($presenter, self::EDUCATION_ID);
        (new ReflectionProperty(BaseSectionPresenter::class, 'isEditable'))->setValue($presenter, true);
        (new ReflectionProperty(EducationBasePresenter::class, 'event'))->setValue($presenter, $this->education());
        (new ReflectionProperty(Control::class, 'template'))->setValue($presenter, new DefaultTemplate(new Engine()));

        return $presenter;
    }

    private function submitHpdImport(CashbookPresenter $presenter): void
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

    /** @return list<array{string, string}> */
    private function flashMessages(CashbookPresenter $presenter): array
    {
        $template = $presenter->getTemplate();
        self::assertInstanceOf(DefaultTemplate::class, $template);

        $messages = [];
        foreach ($template->flashes as $flash) {
            $messages[] = [$flash->message, $flash->type];
        }

        return $messages;
    }

    private function redirectPath(CashbookPresenter $presenter): string
    {
        $response = (new ReflectionProperty(Presenter::class, 'response'))->getValue($presenter);
        self::assertInstanceOf(RedirectResponse::class, $response);

        $path = parse_url($response->getUrl(), PHP_URL_PATH);
        self::assertIsString($path);

        return $path;
    }

    private function education(): Education
    {
        return new Education(
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
        );
    }
}
