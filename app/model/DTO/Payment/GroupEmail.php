<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Model\Payment\EmailTemplate;

final class GroupEmail
{
    public function __construct(private EmailTemplate $template, private bool $enabled)
    {
    }

    public function getTemplate(): EmailTemplate
    {
        return $this->template;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
