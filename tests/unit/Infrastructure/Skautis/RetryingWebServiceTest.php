<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Skautis;

use Codeception\Test\Unit;
use Mockery;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WebServiceInterface;
use Skautis\Wsdl\WsdlException;

final class RetryingWebServiceTest extends Unit
{
    public function testRetryableWsdlExceptionIsRetried(): void
    {
        $inner = Mockery::mock(WebServiceInterface::class);
        $attempt = 0;
        $inner->shouldReceive('call')
            ->twice()
            ->withArgs(['eventGeneralAll', []])
            ->andReturnUsing(static function () use (&$attempt): string {
                ++$attempt;

                if ($attempt === 1) {
                    throw new WsdlException('Could not connect to host');
                }

                return 'result';
            });

        $webService = new RetryingWebService($inner);

        self::assertSame('result', $webService->call('eventGeneralAll'));
    }

    public function testNonRetryableWsdlExceptionIsNotRetried(): void
    {
        $inner = Mockery::mock(WebServiceInterface::class);
        $inner->shouldReceive('call')
            ->once()
            ->andThrow(new WsdlException('Server was unable to process request.'));

        $webService = new RetryingWebService($inner);

        $this->expectException(WsdlException::class);
        $this->expectExceptionMessage('Server was unable to process request.');

        $webService->call('eventGeneralAll');
    }

    public function testPermissionExceptionIsNotRetried(): void
    {
        $inner = Mockery::mock(WebServiceInterface::class);
        $inner->shouldReceive('call')
            ->once()
            ->andThrow(new PermissionException('Nemáte oprávnění.'));

        $webService = new RetryingWebService($inner);

        $this->expectException(PermissionException::class);

        $webService->call('eventGeneralAll');
    }
}
