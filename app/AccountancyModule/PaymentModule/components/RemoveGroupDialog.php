<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use Model\Payment\Commands\Group\RemoveGroup;
use Model\PaymentService;
use Nette\Application\BadRequestException;

final class RemoveGroupDialog extends BaseControl
{
    /** @var bool */
    private $opened = false;

    /** @var int */
    private $groupId;

    /** @var CommandBus */
    private $commandBus;

    /** @var PaymentService */
    private $paymentService;

    public function __construct(
        int $groupId,
        CommandBus $commandBus,
        PaymentService $paymentService
    ) {
        parent::__construct();

        $this->groupId        = $groupId;
        $this->commandBus     = $commandBus;
        $this->paymentService = $paymentService;
    }

    public function open() : void
    {
        $this->opened = true;
        $this->redrawControl();
    }

    public function render() : void
    {
        $group = $this->paymentService->getGroup($this->groupId);

        if ($group === null) {
            throw new BadRequestException('Skupina plateb neexistuje');
        }

        $this->template->setParameters(
            [
            'groupName' => $group->getName(),
            'renderModal' => $this->opened,
            ]
        );

        $this->template->setFile(__DIR__ . '/templates/RemoveGroupDialog.latte');
        $this->template->render();
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();

        $form->addSubmit('delete', 'Smazat')
            ->setAttribute('class', 'btn-danger');

        $form->onSuccess[] = function () : void {
            $this->commandBus->handle(new RemoveGroup($this->groupId));

            $this->getPresenter()->flashMessage('Skupina plateb byla odstraněna', 'success');
            $this->getPresenter()->redirect('default');
        };

        return $form;
    }
}
