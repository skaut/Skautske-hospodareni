<?php

declare(strict_types=1);

namespace App\Presentation\Unit\Accessory\Components;

use App\Components\BaseControl;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Commands\Cashbook\LockChit;
use App\Model\Cashbook\Commands\Cashbook\UnlockChit;
use App\Model\Cashbook\ReadModel\Queries\ChitListQuery;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Chit;
use Nette\Security\User;

use function array_filter;
use function count;

class ChitListControl extends BaseControl
{
    /** @var Chit[]|null */
    private ?array $chits = null;

    public function __construct(
        private CashbookId $cashbookId,
        private bool $onlyUnlocked,
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private User $user,
    ) {
    }

    public function handleLockChit(int $chitId): void
    {
        $this->commandBus->handle(new LockChit($this->cashbookId, $chitId, $this->user->getId()));

        $this->flashMessage('Doklad byl uzamčen', 'success');
        $this->redrawControl();
    }

    public function handleUnlockChit(int $chitId): void
    {
        $this->commandBus->handle(new UnlockChit($this->cashbookId, $chitId));

        $this->flashMessage('Doklad byl odemčen', 'success');
        $this->redrawControl();
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/ChitListControl.latte');
        $this->template->setParameters(['chits' => $this->getChits()]);

        $this->template->render();
    }

    public function isEmpty(): bool
    {
        return count($this->getChits()) === 0;
    }

    /** @return Chit[] */
    private function getChits(): array
    {
        if ($this->chits === null) {
            $chits = $this->queryBus->handle(ChitListQuery::all($this->cashbookId));

            if ($this->onlyUnlocked) {
                $chits = array_filter(
                    $chits,
                    function (Chit $chit): bool {
                        return ! $chit->isLocked();
                    },
                );
            }

            $this->chits = $chits;
        }

        return $this->chits;
    }
}
