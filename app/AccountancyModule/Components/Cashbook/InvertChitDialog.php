<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Cashbook;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Commands\Cashbook\AddInverseChit;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\ChitQuery;
use Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\UnitCashbook;
use Model\Unit\Unit;
use Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use Model\User\ReadModel\Queries\EditableUnitsQuery;
use Model\User\SkautisRole;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;
use function count;
use function in_array;
use function sprintf;

class InvertChitDialog extends BaseControl
{
    /**
     * (string because persistent parameters aren't auto-casted)
     *
     * @var        int|string|NULL
     * @persistent
     */
    public $chitId;

    /** @var CashbookId */
    private $cashbookId;

    /** @var array<string, string>|NULL */
    private $cashbooks;

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(CashbookId $cashbookId, CommandBus $commandBus, QueryBus $queryBus)
    {
        parent::__construct();
        $this->cashbookId = $cashbookId;
        $this->commandBus = $commandBus;
        $this->queryBus   = $queryBus;
    }

    public function handleOpen(int $chitId) : void
    {
        $this->chitId = $chitId;
        $this->redrawControl();
    }

    public function render() : void
    {
        if ($this->chitId !== null && ! $this->isChitValid()) {
            throw new BadRequestException(
                sprintf('Chit %d doesn\'t exist or can\'t be inverted', $this->chitId),
                IResponse::S404_NOT_FOUND
            );
        }

        $template = $this->template;

        $template->setParameters(
            [
            'renderModal' => $this->chitId !== null,
            'noCashbooks' => ! $this->isChitValid() || count($this->getCashbooks()) === 0,
            ]
        );

        $template->setFile(__DIR__ . '/templates/InvertChitDialog.latte');
        $template->render();
    }

    /**
     * @return CashbookType[]
     */
    public static function getValidInverseCashbookTypes() : array
    {
        return [
            CashbookType::get(CashbookType::TROOP),
            CashbookType::get(CashbookType::OFFICIAL_UNIT),
        ];
    }

    protected function createComponentForm() : BaseForm
    {
        if (! $this->isChitValid()) {
            throw new \RuntimeException('Chit is not set or is not valid for inverting');
        }

        $form = new BaseForm();

        $form->addSelect('cashbookId', 'Pokladní kniha', $this->getCashbooks())
            ->setRequired('Musíte vybrat pokladní knihu');

        $form->addSubmit('send', 'Vytvořit protidoklad')
            ->setAttribute('class', 'ajax');

        $form->onSuccess[] = function (BaseForm $form, array $values) : void {
            $cashbookId = CashbookId::fromString((string)$values['cashbookId']);
            $this->commandBus->handle(new AddInverseChit($this->cashbookId, $cashbookId, (int) $this->chitId));
            $this->presenter->flashMessage('Protidoklad byl vytvořen', 'success');
            $this->close();
        };

        return $form;
    }

    /**
     * @return array<string, string>
     */
    private function getCashbooks() : array
    {
        if ($this->cashbooks !== null) {
            return $this->cashbooks;
        }

        /**
 * @var SkautisRole|NULL $role
*/
        $role = $this->queryBus->handle(new ActiveSkautisRoleQuery());
        $chit = $this->getChit();

        if ($role === null || $chit === null) {
            return [];
        }

        $units = $this->queryBus->handle(new EditableUnitsQuery($role));

        $cashbooks = [];

        foreach ($units as $unit) {
            /**
 * @var Unit $unit
*/
            $type = CashbookType::get($unit->isOfficial() ? CashbookType::OFFICIAL_UNIT : CashbookType::TROOP);

            if (! in_array($type, $chit->getInverseCashbookTypes(), true)) {
                continue;
            }

            /**
 * @var UnitCashbook[] $unitCashbooks
*/
            $unitCashbooks = $this->queryBus->handle(new UnitCashbookListQuery($unit->getId()));

            foreach ($unitCashbooks as $cashbook) {
                $cashbooks[$cashbook->getCashbookId()->toString()] = $unit->getDisplayName();
            }
        }

        $this->cashbooks = $cashbooks;

        return $cashbooks;
    }

    private function isChitValid() : bool
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

    private function getChit() : ?Chit
    {
        return $this->queryBus->handle(new ChitQuery($this->cashbookId, (int) $this->chitId));
    }

    private function close() : void
    {
        $this->chitId = null;
        $this->redrawControl();
    }
}
