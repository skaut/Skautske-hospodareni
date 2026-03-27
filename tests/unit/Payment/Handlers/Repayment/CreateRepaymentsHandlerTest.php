<?php

declare(strict_types=1);

namespace App\Model\Payment\Handlers\Repayment;

use App\Model\Bank\Fio\IUploaderFactory;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Payment\BankError;
use App\Model\Payment\Commands\Repayment\CreateRepayments;
use App\Model\Payment\Repayment;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use FioApi\Upload\Entity\UploadResponse;
use FioApi\Upload\Uploader;
use Mockery as m;
use Money\Money;

final class CreateRepaymentsHandlerTest extends Unit
{
    public function testUsesFioUploaderForRepayments(): void
    {
        $command = $this->createCommand();

        $uploader = m::mock(Uploader::class);
        $uploader->shouldReceive('addPaymentOrder')->twice();
        $uploader->shouldReceive('uploadPaymentOrders')
            ->once()
            ->andReturn(new UploadResponse($this->successResponse()));

        $factory = m::mock(IUploaderFactory::class);
        $factory->shouldReceive('create')
            ->once()
            ->with('token', '2000942144')
            ->andReturn($uploader);

        $handler = new CreateRepaymentsHandler($factory);

        $handler($command);
    }

    public function testMapsApiErrorsToBankError(): void
    {
        $command = $this->createCommand();

        $uploader = m::mock(Uploader::class);
        $uploader->shouldReceive('addPaymentOrder')->twice();
        $uploader->shouldReceive('uploadPaymentOrders')
            ->once()
            ->andReturn(new UploadResponse($this->errorResponse()));

        $factory = m::mock(IUploaderFactory::class);
        $factory->shouldReceive('create')
            ->once()
            ->with('token', '2000942144')
            ->andReturn($uploader);

        $handler = new CreateRepaymentsHandler($factory);

        $this->expectException(BankError::class);
        $this->expectExceptionMessage('Transakce "vratka 1" obsahuje nasledujici chybu: "Chyba 1"');

        $handler($command);
    }

    public function testMapsApiErrorsWithoutOrderDetailsToBankError(): void
    {
        $command = $this->createCommand();

        $uploader = m::mock(Uploader::class);
        $uploader->shouldReceive('addPaymentOrder')->twice();
        $uploader->shouldReceive('uploadPaymentOrders')
            ->once()
            ->andReturn(new UploadResponse($this->genericErrorResponse()));

        $factory = m::mock(IUploaderFactory::class);
        $factory->shouldReceive('create')
            ->once()
            ->with('token', '2000942144')
            ->andReturn($uploader);

        $handler = new CreateRepaymentsHandler($factory);

        $this->expectException(BankError::class);
        $this->expectExceptionMessage('API Error: Obecna chyba');

        $handler($command);
    }

    private function createCommand(): CreateRepayments
    {
        return new CreateRepayments(
            new AccountNumber(null, '2000942144', '2010'),
            ChronosDate::create(2026, 3, 4),
            [
                new Repayment(
                    new AccountNumber(null, '2000942144', '2010'),
                    Money::CZK(10000),
                    'vratka 1',
                ),
                new Repayment(
                    new AccountNumber(null, '2000942144', '2010'),
                    Money::CZK(2500),
                    'vratka 2',
                ),
            ],
            'token',
        );
    }

    private function successResponse(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<Import>
  <result>
    <status>ok</status>
    <errorCode>0</errorCode>
  </result>
</Import>
XML;
    }

    private function errorResponse(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<Import>
  <result>
    <status>error</status>
    <errorCode>500</errorCode>
  </result>
  <ordersDetails>
    <detail id="1">
      <messages>
        <message status="error" errorCode="500">Chyba 1</message>
      </messages>
    </detail>
    <detail id="2">
      <messages>
        <message status="ok" errorCode="0">OK</message>
      </messages>
    </detail>
  </ordersDetails>
</Import>
XML;
    }

    private function genericErrorResponse(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<Import>
  <result>
    <status>error</status>
    <errorCode>500</errorCode>
    <message>Obecna chyba</message>
  </result>
</Import>
XML;
    }
}
