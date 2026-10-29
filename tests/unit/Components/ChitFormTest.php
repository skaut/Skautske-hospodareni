<?php

declare(strict_types=1);

namespace App\Components;

use App\Model\Auth\IAuthorizator;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\ReadModel\Queries\ChitQuery;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use Codeception\Test\Unit;
use Latte\Engine;
use Mockery;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Http\IResponse;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

final class ChitFormTest extends Unit
{
    /** Doklad mohl mezitím někdo smazat – úprava má skončit 404, ne voláním metody na null. */
    public function testEditingMissingChitIsNotFound(): void
    {
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')->once()->with(Mockery::type(ChitQuery::class))->andReturnNull();

        $form = new ChitForm(
            CashbookId::generate(),
            true,
            new UnitId(27266),
            Mockery::mock(CommandBus::class),
            $queryBus,
            Mockery::mock(IAuthorizator::class),
            Mockery::mock(LoggerInterface::class),
        );
        (new ReflectionProperty(Control::class, 'template'))->setValue($form, new DefaultTemplate(new Engine()));

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(IResponse::S404_NotFound);

        $form->editChit(123);
    }

    protected function _after(): void
    {
        Mockery::close();
    }
}
