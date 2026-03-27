<?php

declare(strict_types=1);

namespace App\Model\Payment\Group;

use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;

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
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Group::class, inversedBy="emails")
     * @ORM\JoinColumn(nullable=false)
     */
    private Group $group;

    /** @ORM\Column(type="boolean") */
    private bool $enabled = true;

    /**
     * @ORM\Column(type="string_enum")
     *
     * @Enum(class=EmailType::class)
     * @var EmailType
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    private $type;

    /** @ORM\Embedded(class=EmailTemplate::class) */
    private EmailTemplate $template;

    public function __construct(Group $group, EmailType $type, EmailTemplate $template)
    {
        $this->group = $group;
        $this->type = $type;
        $this->template = $template;
    }

    public function updateTemplate(EmailTemplate $template): void
    {
        $this->template = $template;
        $this->enabled = true;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function disable(): void
    {
        $this->enabled = false;
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
