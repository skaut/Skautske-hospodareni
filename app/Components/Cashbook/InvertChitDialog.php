<?php

declare(strict_types=1);

namespace App\Components\Cashbook;

use App\Components\Dialog;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\Commands\Cashbook\AddInverseChit;
use App\Model\Cashbook\ObjectType;
use App\Model\Cashbook\ReadModel\Queries\ChitQuery;
use App\Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\DTO\Cashbook\Chit;
use App\Model\DTO\Cashbook\UnitCashbook;
use App\Model\Unit\Unit;
use App\Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use App\Model\User\ReadModel\Queries\EditableUnitsQuery;
use Component\Forms\BaseForm;
use LogicException;
use Nette\Application\Attributes\Persistent;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;
use RuntimeException;

use function count;
use function in_array;
use function sprintf;

class InvertChitDialog extends Dialog
{
    /** (string because persistent parameters aren't auto-casted). */
    #[Persistent]
    public int|string|null $chitId = null;

    /** @var array<string, string>|null */
    private ?array $cashbooks = null;

    public function __construct(private CashbookId $cashbookId, private CommandBus $commandBus, private QueryBus $queryBus)
    {
    }

    public function handleOpen(?int $chitId = null): void
    {
        $this->chitId = $chitId;
        if ($this->chitId !== null && ! $this->isChitValid()) {
            throw new BadRequestException(sprintf('Chit %d doesn\'t exist or can\'t be inverted', $this->chitId), IResponse::S404_NotFound);
        }

        $this->show();
    }

    public function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->setFile(__DIR__.'/templates/InvertChitDialog.latte');
        $this->template->setParameters([
            'renderModal' => $this->chitId !== null,
            'noCashbooks' => ! $this->isChitValid() || count($this->getCashbooks()) === 0,
        ]);
    }

    /** @return CashbookType[] */
    public static function getValidInverseCashbookTypes(): array
    {
        return [
            CashbookType::get(CashbookType::TROOP),
            CashbookType::get(CashbookType::OFFICIAL_UNIT),
        ];
    }

    protected function createComponentForm(): BaseForm
    {
        if (! $this->isChitValid()) {
            throw new RuntimeException('Chit is not set or is not valid for inverting');
        }

        $form = new BaseForm();

        $form->addSelect('cashbookId', 'Evidence plateb', $this->getCashbooks())
            ->setRequired('Musíte vybrat evidenci plateb');

        $form->addSubmit('send', 'Vytvořit protidoklad')
            ->setHtmlAttribute('class', 'ajax btn btn-primary');

        $form->onSuccess[] = function (BaseForm $form, array $values): void {
            $cashbookId = CashbookId::fromString((string) $values['cashbookId']);
            $this->commandBus->handle(new AddInverseChit($this->cashbookId, $cashbookId, (int) $this->chitId));
            $this->flashMessage('Protidoklad byl vytvořen', 'success');
            $this->close();
        };

        return $form;
    }

    /** @return string[] */
    private function getCashbooks(): array
    {
        if ($this->cashbooks !== null) {
            return $this->cashbooks;
        }

        $role = $this->queryBus->handle(new ActiveSkautisRoleQuery());
        $chit = $this->getChit();

        if ($chit === null) {
            return [];
        }

        $units = $this->queryBus->handle(new EditableUnitsQuery($role));
        $cashbooks = [];
        foreach ($units as $unit) {
            if (! $unit instanceof Unit) {
                throw new LogicException('Assertion failed.');
            }
            $type = CashbookType::get($unit->isOfficial() ? CashbookType::OFFICIAL_UNIT : CashbookType::TROOP);

            if (! in_array($type, $chit->getInverseCashbookTypes(), true)) {
                continue;
            }

            $unitCashbooks = $this->queryBus->handle(new UnitCashbookListQuery(new UnitId($unit->getId())));

            foreach ($unitCashbooks as $cashbook) {
                if (! $cashbook instanceof UnitCashbook) {
                    throw new LogicException('Assertion failed.');
                }
                $id = $cashbook->getCashbookId()->toString();
                $cashbooks[$id] = $unit->getDisplayName().' '.$cashbook->getYear();
            }
        }

        $this->cashbooks = $cashbooks;

        return $cashbooks;
    }

    private function isChitValid(): bool
    {
        // No chit selected -> modal closed
        if ($this->chitId === null) {
            return false;
        }

        $chit = $this->getChit();

        // Nonexistent chit
        if ($chit === null) {
            return false;
        }

        // Right now only inverting to unit cashbook is supported
        foreach ($chit->getInverseCashbookTypes() as $type) {
            if ($type->getSkautisObjectType()->equalsValue(ObjectType::UNIT)) {
                return true;
            }
        }

        return false;
    }

    private function getChit(): ?Chit
    {
        return $this->queryBus->handle(new ChitQuery($this->cashbookId, (int) $this->chitId));
    }

    private function close(): void
    {
        $this->chitId = null;
        $this->hide();
    }
}
