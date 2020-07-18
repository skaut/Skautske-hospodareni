<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Doctrine\ORM\Mapping as ORM;
use Model\Common\FilePath;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_chit_scan")
 */
class ChitScan
{
    public const FILE_PATH_PREFIX = 'chits';

    /**
     * @internal only for infrastructure
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /** @ORM\ManyToOne(targetEntity=Chit::class, inversedBy="scans") */
    private Chit $chit;

    /** @ORM\Column(type="file_path") */
    private FilePath $filePath;

    public function __construct(Chit $chit, FilePath $filePath)
    {
        $this->chit     = $chit;
        $this->filePath = $filePath;
    }

    public function getFilePath() : FilePath
    {
        return $this->filePath;
    }
}
