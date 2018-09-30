<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group;

/**
 * @ORM\Entity()
 * @ORM\Table(name="pa_group_email")
 */
class Email
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Group
     * @ORM\ManyToOne(targetEntity=Group::class, inversedBy="emails")
     */
    private $group;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    /**
     * @var EmailType
     * @ORM\Column(type="string_enum")
     * @Enum(class=EmailType::class)
     */
    private $type;

    /**
     * @var EmailTemplate
     * @ORM\Embedded(class=EmailTemplate::class)
     */
    private $template;

    public function __construct(Group $group, EmailType $type, EmailTemplate $template)
    {
        $this->group    = $group;
        $this->type     = $type;
        $this->template = $template;
    }

    public function updateTemplate(EmailTemplate $template) : void
    {
        $this->template = $template;
        $this->enabled  = true;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function disable() : void
    {
        $this->enabled = false;
    }

    public function getGroup() : Group
    {
        return $this->group;
    }

    public function getType() : EmailType
    {
        return $this->type;
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
