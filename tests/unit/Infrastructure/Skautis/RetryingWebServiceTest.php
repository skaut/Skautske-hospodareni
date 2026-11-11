<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Skautis;

use Codeception\Test\Unit;
use Mockery;
use Skaut\Skautis\Wsdl\PermissionException;
use Skaut\Skautis\Wsdl\WebServiceInterface;
use Skaut\Skautis\Wsdl\WsdlException;
use SoapFault;

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

    public function testLostResponseOfReadIsRetried(): void
    {
        $inner = Mockery::mock(WebServiceInterface::class);
        $attempt = 0;
        $inner->shouldReceive('call')
            ->twice()
            ->withArgs(['UnitDetail', []])
            ->andReturnUsing(static function () use (&$attempt): string {
                ++$attempt;

                if ($attempt === 1) {
                    throw self::lostResponse();
                }

                return 'result';
            });

        $webService = new RetryingWebService($inner);

        self::assertSame('result', $webService->call('UnitDetail'));
    }

    public function testLostResponseOfInsertIsNotRetried(): void
    {
        $inner = Mockery::mock(WebServiceInterface::class);
        $inner->shouldReceive('call')
            ->once()
            ->andThrow(self::lostResponse());

        $webService = new RetryingWebService($inner);

        $this->expectException(WsdlException::class);
        $this->expectExceptionMessage('Error Fetching http headers');

        $webService->call('ParticipantGeneralInsert');
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

    /** The exception skautis/skautis throws when skautIS does not answer: the SoapFault message without its "HTTP" fault code. */
    private static function lostResponse(): WsdlException
    {
        return new WsdlException('Error Fetching http headers', 0, new SoapFault('HTTP', 'Error Fetching http headers'));
    }
}
