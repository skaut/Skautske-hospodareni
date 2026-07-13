<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Log\Monolog;

use App\Model\Infrastructure\Log\Monolog\FormContextProcessor;
use Codeception\Test\Unit;
use Nette\Http\Request;
use Nette\Http\UrlScript;

final class FormContextProcessorTest extends Unit
{
    public function testPostContextRedactsSensitiveValues(): void
    {
        $processor = new FormContextProcessor(new Request(
            new UrlScript('https://example.test/'),
            [
                'token' => 'secret-token',
                '_token_' => 'csrf-token',
                'nested' => [
                    'password' => 'secret-password',
                    'name' => 'visible',
                ],
            ],
            method: Request::POST,
        ));

        $record = $processor(['context' => []]);

        self::assertSame(
            '{"token":"<redacted>","_token_":"<redacted>","nested":{"password":"<redacted>","name":"visible"}}',
            $record['context']['post'],
        );
    }
}
