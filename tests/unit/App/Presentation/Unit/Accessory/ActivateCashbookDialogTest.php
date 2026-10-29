<?php

declare(strict_types=1);

namespace App\Presentation\Unit\Accessory\Components;

use App\BasePresenter;
use App\Model\Cashbook\ReadModel\Queries\ActiveUnitCashbookQuery;
use App\Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use Codeception\Test\Unit;
use LogicException;
use Mockery;
use Nette\Application\BadRequestException;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;

final class ActivateCashbookDialogTest extends Unit
{
    /** Jednotka bez pokladní knihy dialog nevykreslí, odeslání jeho formuláře je proto chybný požadavek. */
    public function testFormForUnitWithoutCashbookIsBadRequest(): void
    {
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')->andReturnUsing(
            static fn (object $query): mixed => match (true) {
                $query instanceof UnitCashbookListQuery => [],
                $query instanceof ActiveUnitCashbookQuery => null,
                default => throw new LogicException('Neočekávaný dotaz '.$query::class),
            },
        );

        $presenter = new class extends BasePresenter {
        };
        $presenter->injectPrimary(new Request(new UrlScript('http://localhost/')), new Response());
        $dialog = new ActivateCashbookDialog(true, new UnitId(27266), Mockery::mock(CommandBus::class), $queryBus);
        $presenter->addComponent($dialog, 'activateCashbookDialog');

        $this->expectException(BadRequestException::class);

        $dialog->getComponent('form');
    }

    protected function _after(): void
    {
        Mockery::close();
    }
}
