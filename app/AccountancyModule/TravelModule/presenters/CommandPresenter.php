<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule;

use App\AccountancyModule\TravelModule\Components\CommandForm;
use App\AccountancyModule\TravelModule\Factories\ICommandFormFactory;
use Model\TravelService;
use Nette\Application\BadRequestException;

class CommandPresenter extends BasePresenter
{
    /** @var ICommandFormFactory */
    private $commandFormFactory;

    /** @var TravelService */
    private $model;

    /** @var int */
    private $id;

    public function __construct(ICommandFormFactory $commandFormFactory, TravelService $model)
    {
        parent::__construct();
        $this->commandFormFactory = $commandFormFactory;
        $this->model              = $model;
    }

    public function actionEdit(int $id) : void
    {
        $command = $this->model->getCommandDetail($id);
        if ($command === null || $command->getUnitId() !== $this->getUnitId() || $command->getClosedAt() !== null) {
            throw new BadRequestException('Cestovní příkaz #' . $id . ' neexistuje');
        }

        $this->id = $id;
    }

    protected function createComponentForm() : CommandForm
    {
        $form              = $this->commandFormFactory->create($this->getUnitId(), $this->id);
        $form->onSuccess[] = function () : void {
            if ($this->id !== null) {
                $this->redirect('Default:detail', ['id' => $this->id]);
            }
            $this->redirect('Default:');
        };

        return $form;
    }
}
