<?php

declare(strict_types=1);

namespace App\Model\Payment\Group;

use App\Model\Common\EmailAddress;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\Group;
use App\Model\Payment\Mailing\Payment;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Mockery as m;

class EmailTemplateTest extends Unit
{
    public function testCreate(): void
    {
        $template = new EmailTemplate('subject', 'body');
        $this->assertSame('subject', $template->getSubject());
        $this->assertSame('body', $template->getBody());
    }

    public function testEvaluate(): void
    {
        $subject = '%groupname% | %name% | %account% | %amount% | %maturity% | %maturityus% | %vs% | %ks% | %note% | %user%';
        $body = 'Body: '.$subject.' | %qrcode%';

        $template = new EmailTemplate($subject, $body);

        $group = m::mock(Group::class);
        $group->shouldReceive('getName')->andReturn('Skupina');
        $payment = new Payment('František Maša', 200, [new EmailAddress('frantisekmasa1@gmail.com')], new DateTimeImmutable('2017-04-27'), 4554, 303, 'Poznámka');

        $evaluatedTemplate = $template->evaluate($group, $payment, '19-2000145399/0800', 'Sináček', 'qr-code@example.test');

        $expectedSubject = 'Skupina | František Maša | 19-2000145399/0800 | 200 | 27.4.2017 | 2017-04-27 | 4554 | 303 | Poznámka | Sináček';

        $expectedBody = 'Body: '.$expectedSubject.' | <img alt="QR platbu se nepodařilo zobrazit" src="cid:qr-code@example.test">';

        $this->assertSame($expectedSubject, $evaluatedTemplate->getSubject());

        $this->assertSame($expectedBody, $evaluatedTemplate->getBody());
    }
}
