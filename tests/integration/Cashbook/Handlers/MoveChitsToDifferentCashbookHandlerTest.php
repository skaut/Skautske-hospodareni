<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers;

use Cake\Chronos\ChronosDate;
use CommandHandlerTest;
use Helpers;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Commands\Cashbook\AddChitScan;
use Model\Cashbook\Commands\Cashbook\MoveChitsToDifferentCashbook;
use Model\Cashbook\Repositories\ICashbookRepository;
use Nette\Utils\Image;

final class MoveChitsToDifferentCashbookHandlerTest extends CommandHandlerTest
{
    private const TARGET_CASHBOOK_ID = '50f7a570-102b-4d12-aa9f-1391a132b02d';
    private const SOURCE_CASHBOOK_ID = 'b10db507-aed9-4abd-bdc1-95631d775e65';

    private ICashbookRepository $cashbooks;

    public function testMovingChits(): void
    {
        $sourceCashbookId = CashbookId::fromString(self::SOURCE_CASHBOOK_ID);
        $targetCashbookId = CashbookId::fromString(self::TARGET_CASHBOOK_ID);

        $type = CashbookType::get(CashbookType::EVENT);
        $this->cashbooks->save(new Cashbook($targetCashbookId, $type));
        $sourceCashbook = new Cashbook($sourceCashbookId, $type);
        $categoryId     = 123;
        $category       = Helpers::mockChitItemCategory($categoryId);

        for ($i = 0; $i < 3; $i++) {
            $sourceCashbook->addChit(
                new Cashbook\ChitBody(null, new ChronosDate(), null),
                Cashbook\PaymentMethod::get(Cashbook\PaymentMethod::CASH),
                [new Cashbook\ChitItem(new Amount('100'), $category, 'test')],
                Helpers::mockCashbookCategories($categoryId),
            );
        }

        // https://github.com/skaut/Skautske-hospodareni/issues/1478
        $this->cashbooks->save($sourceCashbook);
        $this->commandBus->handle(
            new AddChitScan(
                $sourceCashbookId,
                1,
                'foo.jpg',
                Image::fromBlank(1, 1)->toString(),
            ),
        );

        $this->commandBus->handle(
            new MoveChitsToDifferentCashbook([1, 3], $sourceCashbookId, $targetCashbookId),
        );

        $this->entityManager->clear();

        $sourceCashbook = $this->cashbooks->find($sourceCashbookId);
        $targetCashbook = $this->cashbooks->find($targetCashbookId);

        $this->assertCount(1, $sourceCashbook->getChits());
        $this->assertCount(2, $targetCashbook->getChits());
        $this->assertCount(1, $targetCashbook->getChits()[0]->getScans());
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Cashbook::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/MoveChitsToDifferentCashbookHandlerTest.neon']);

        parent::_before();

        $this->cashbooks = $this->tester->grabService(ICashbookRepository::class);
    }
}
