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
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Group::class, inversedBy="emails")
     *
     * @var Group
     */
    private $group;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $enabled = true;

    /**
     * @ORM\Column(type="string_enum")
     *
     * @var EmailType
     * @Enum(class=EmailType::class)
     */
    private $type;

    /**
     * @ORM\Embedded(class=EmailTemplate::class)
     *
     * @var EmailTemplate
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
