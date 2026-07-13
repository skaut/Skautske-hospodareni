<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Log\Monolog;

use Nette\Http\Request;

use function in_array;
use function json_encode;
use function strtolower;

class FormContextProcessor
{
    private const REDACTED_VALUE = '<redacted>';
    private const SENSITIVE_KEYS = [
        'password',
        'token',
        'skautis_token',
        '_token_',
    ];

    public function __construct(private Request $request)
    {
    }

    /**
     * @param mixed[] $record
     *
     * @return mixed[]
     */
    public function __invoke(array $record): array
    {
        if ($this->request->isMethod(Request::POST)) {
            $record['context']['post'] = json_encode($this->redactSensitiveValues($this->request->getPost()));
        }

        return $record;
    }

    /**
     * @param  mixed[] $values
     * @return mixed[]
     */
    private function redactSensitiveValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $values[$key] = self::REDACTED_VALUE;
                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->redactSensitiveValues($value);
            }
        }

        return $values;
    }
}
