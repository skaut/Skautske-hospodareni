<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule\Components;

use App\AccountancyModule\Components\BaseControl;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Commands\Cashbook\LockChit;
use Model\Cashbook\Commands\Cashbook\UnlockChit;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\DTO\Cashbook\Chit;
use Nette\Security\User;
use function array_filter;
use function count;

final class ChitListControl extends BaseControl
{
    /** @var Chit[]|NULL */
    private $chits;

    /** @var CashbookId */
    private $cashbookId;

    /** @var bool */
    private $onlyUnlocked;

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    /** @var User */
    private $user;

    public function __construct(
        CashbookId $cashbookId,
        bool $onlyUnlocked,
        CommandBus $commandBus,
        QueryBus $queryBus,
        User $user
    ) {
        parent::__construct();
        $this->cashbookId   = $cashbookId;
        $this->onlyUnlocked = $onlyUnlocked;
        $this->commandBus   = $commandBus;
        $this->queryBus     = $queryBus;
        $this->user         = $user;
    }

    public function handleLockChit(int $chitId) : void
    {
        $this->commandBus->handle(new LockChit($this->cashbookId, $chitId, $this->user->getId()));

        $this->flashMessage('Doklad byl uzamčen', 'success');
        $this->redrawControl();
    }

    public function handleUnlockChit(int $chitId) : void
    {
        $this->commandBus->handle(new UnlockChit($this->cashbookId, $chitId));

        $this->flashMessage('Doklad byl odemčen', 'success');
        $this->redrawControl();
    }

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/ChitListControl.latte');
        $this->template->setParameters(['chits' => $this->getChits()]);

        $this->template->render();
    }

    public function isEmpty() : bool
    {
        return count($this->getChits()) === 0;
    }

    /**
     * @return Chit[]
     */
    private function getChits() : array
    {
        if ($this->chits === null) {
            $chits = $this->queryBus->handle(ChitListQuery::all($this->cashbookId));

            if ($this->onlyUnlocked) {
                $chits = array_filter(
                    $chits,
                    function (Chit $chit) : bool {
                        return ! $chit->isLocked();
                    }
                );
            }

            $this->chits = $chits;
        }

        return $this->chits;
    }
}
