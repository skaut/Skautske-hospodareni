<?php

declare(strict_types=1);

namespace Model\Infrastructure\Skautis;

use Codeception\Test\Unit;
use Mockery;
use Skautis\Skautis;
use Skautis\Wsdl\WebServiceInterface;

final class LazyWebServiceTest extends Unit
{
    public function testCallMethodCallIsPassedToWebService(): void
    {
        $skautis = Mockery::mock(Skautis::class);

        $webService = Mockery::mock(WebServiceInterface::class);
        $webService->shouldReceive('call')
            ->once()
            ->withArgs(['foo1', ['arg1' => 'foo']])
            ->andReturn('result1');

        $webService->shouldReceive('call')
            ->once()
            ->withArgs(['foo2', ['arg2' => 'foo']])
            ->andReturn('result2');

        $skautis->shouldReceive('getWebService')
            ->once()
            ->withArgs(['org'])
            ->andReturn($webService);

        $lazyWebService = new LazyWebService('org', $skautis);

        self::assertSame(
            'result1',
            $lazyWebService->call('foo1', ['arg1' => 'foo']),
        );

        self::assertSame(
            'result2',
            $lazyWebService->call('foo2', ['arg2' => 'foo'])
        );
    }

    public function testMagicCallMethodCallIsPassedToWebService(): void
    {
        $skautis = Mockery::mock(Skautis::class);

        $webService = Mockery::mock(WebServiceInterface::class);

        $webService->shouldReceive('foo1')
            ->once()
            ->withArgs([['arg1' => 'foo']])
            ->andReturn('result1');

        $webService->shouldReceive('foo2')
            ->once()
            ->withArgs([['arg2' => 'foo']])
            ->andReturn('result2');

        $skautis->shouldReceive('getWebService')
            ->once()
            ->withArgs(['org'])
            ->andReturn($webService);

        $lazyWebService = new LazyWebService('org', $skautis);

        self::assertSame(
            'result1',
            $lazyWebService->foo1(['arg1' => 'foo']),
        );

        self::assertSame(
            'result2',
            $lazyWebService->foo2(['arg2' => 'foo'])
        );
    }

    public function testWebServiceIsNotCreatedBeforeFirstCall(): void
    {
        new LazyWebService('org', Mockery::mock(Skautis::class));
    }
}
