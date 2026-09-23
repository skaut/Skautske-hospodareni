<?php

declare(strict_types=1);

namespace App\Model\Announcement\Entity;

use App\Model\Announcement\Enum\AnnouncementCategoryCode;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'announcement_category')]
class AnnouncementCategory
{
    #[Id]
    #[Column(name: 'code', type: 'string', length: 16)]
    private string $code;

    public function __construct(AnnouncementCategoryCode $code)
    {
        $this->code = $code->value;
    }

    public function getCode(): AnnouncementCategoryCode
    {
        return AnnouncementCategoryCode::from($this->code);
    }
}
