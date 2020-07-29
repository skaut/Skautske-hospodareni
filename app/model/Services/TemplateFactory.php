<?php

declare(strict_types=1);

namespace Model\Services;

use Latte\Engine;
use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\Bridges\ApplicationLatte\Template;

class TemplateFactory
{
    public const OAUTH_ADDED     = __DIR__ . '/../emails/oAuthAdded.latte';
    public const PAYMENT_DETAILS = __DIR__ . '/../emails/payment.base.latte';

    /** @var ILatteFactory */
    private $latteFactory;

    /** @var Engine|NULL */
    private $engine = null;

    public function __construct(ILatteFactory $latteFactory)
    {
        $this->latteFactory = $latteFactory;
    }

    private function getEngine() : Engine
    {
        if ($this->engine === null) {
            $this->engine = $this->latteFactory->create();
        }

        return $this->engine;
    }

    /**
     * @param mixed[] $parameters
     */
    public function create(string $file, array $parameters) : string
    {
        $template = new Template($this->getEngine());

        $template->setFile($file);
        $template->setParameters($parameters);

        return (string) $template;
    }
}
