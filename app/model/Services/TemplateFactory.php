<?php

namespace Model\Services;

use Latte\Engine;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Bridges\ApplicationLatte\ILatteFactory;

class TemplateFactory
{

    public const SMTP_CREDENTIALS_ADDED = __DIR__ . '/../emails/smtpAdded.latte';
    public const PAYMENT_DETAILS = __DIR__ . '/../emails/payment.base.latte';

    /** @var ILatteFactory */
    private $latteFactory;

    /** @var Engine|NULL */
    private $engine = NULL;

    public function __construct(ILatteFactory $latteFactory)
    {
        $this->latteFactory = $latteFactory;
    }

    private function getEngine() : Engine
    {
        if($this->engine === NULL) {
            $this->engine = $this->latteFactory->create();
        }
        return $this->engine;
    }

    public function create(string $file, array $parameters): string
    {
        $template = new Template($this->getEngine());

        $template->setFile($file);
        $template->setParameters($parameters);

        return (string)$template;
    }

}
