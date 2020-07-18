<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Model\Payment\EmailTemplate;

final class GroupEmail
{
    private EmailTemplate $template;

    private bool $enabled;

    public function __construct(EmailTemplate $template, bool $enabled)
    {
        $this->template = $template;
        $this->enabled  = $enabled;
    }

    public function getTemplate() : EmailTemplate
    {
        return $this->template;
    }

    public function isEnabled() : bool
    {
        return $this->enabled;
    }
}
