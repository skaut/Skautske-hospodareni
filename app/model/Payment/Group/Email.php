<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group;

class Email
{

    /** @var int */
    private $id;

    /** @var Group */
    private $group;

    /** @var bool */
    private $enabled = TRUE;

    /** @var EmailType */
    private $type;

    /** @var EmailTemplate */
    private $template;

    public function __construct(Group $group, EmailType $type, EmailTemplate $template)
    {
        $this->group = $group;
        $this->type = $type;
        $this->template = $template;
    }

    public function updateTemplate(EmailTemplate $template): void
    {
        $this->template = $template;
        $this->enabled = TRUE;
    }

    public function disable(): void
    {
        $this->enabled = FALSE;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getType(): EmailType
    {
        return $this->type;
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
