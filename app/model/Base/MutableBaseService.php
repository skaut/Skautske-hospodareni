<?php

declare(strict_types=1);

namespace Model;

use Skautis\Skautis;
use function strtolower;

/**
 * Třída pro odvozování tříd, které jsou přispůsobiltené parametry v konstruktoru
 * $longName a $name chci sjednotit - prověřit, jestli to ma stale skautis ruzně
 */
abstract class MutableBaseService extends BaseService
{
    protected $typeName;

    /** @var string */
    public $type;

    public function __construct(string $name, Skautis $skautIS)
    {
        parent::__construct($skautIS);
        $this->typeName = $name;
        $this->type     = strtolower($name);
    }
}
