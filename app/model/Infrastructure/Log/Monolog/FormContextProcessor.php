<?php

declare(strict_types=1);

namespace Model\Infrastructure\Log\Monolog;

use Nette\Http\Request;
use function json_encode;

class FormContextProcessor
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param mixed[] $record
     *
     * @return mixed[]
     */
    public function __invoke(array $record) : array
    {
        if ($this->request->isMethod(Request::POST)) {
            $record['context']['post'] = json_encode($this->request->getPost());
        }

        return $record;
    }
}
