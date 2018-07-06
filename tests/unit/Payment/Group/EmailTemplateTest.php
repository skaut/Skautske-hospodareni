<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Mockery as m;
use Model\Payment\EmailTemplate;
use Model\Payment\Group;
use Model\Payment\Mailing\Payment;
use function urlencode;

class EmailTemplateTest extends Unit
{
    public function testCreate() : void
    {
        $template = new EmailTemplate('subject', 'body');
        $this->assertSame('subject', $template->getSubject());
        $this->assertSame('body', $template->getBody());
    }

    public function testEvaluate() : void
    {
        $subject = '%groupname% | %name% | %account% | %amount% | %maturity% | %vs% | %ks% | %note% | %user%';
        $body    = "Body: $subject | %qrcode%";

        $template = new EmailTemplate($subject, $body);

        $group = m::mock(Group::class);
        $group->shouldReceive('getName')->andReturn('Skupina');
        $payment = new Payment('František Maša', 200, 'frantisekmasa1@gmail.com', new DateTimeImmutable('2017-04-27'), 4554, 303, 'Poznámka');

        $evaluatedTemplate = $template->evaluate($group, $payment, '123456789/2100', 'Sináček');

        $expectedSubject = 'Skupina | František Maša | 123456789/2100 | 200 | 27.4.2017 | 4554 | 303 | Poznámka | Sináček';

        $qrCode       = '<img alt="QR platbu se nepodařilo zobrazit" src="http://api.paylibo.com/paylibo/generator/czech/image?accountNumber=123456789&bankCode=2100&amount=200&currency=CZK&size=200&vs=4554&ks=303&message=' . urlencode('František Maša') . '">';
        $expectedBody = "Body: $expectedSubject | $qrCode";

        $this->assertSame($expectedSubject, $evaluatedTemplate->getSubject());

        $this->assertSame($expectedBody, $evaluatedTemplate->getBody());
    }
}
