<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Model\Cashbook\ReadModel\Queries\ParticipantListQuery;
use Model\Participant\Event;
use Model\Participant\Participant;
use Model\PaymentService;
use Model\Utils\MoneyFactory;
use function array_fill_keys;
use function array_key_exists;

class CampPresenter extends BasePresenter
{
    /** @var string[] */
    protected $readUnits;

    /** @var PaymentService */
    private $model;

    /** @var IMassAddFormFactory */
    private $massAddFormFactory;

    /** @var int */
    private $id;

    public function __construct(PaymentService $model, IMassAddFormFactory $massAddFormFactory)
    {
        parent::__construct();
        $this->model              = $model;
        $this->massAddFormFactory = $massAddFormFactory;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->template->unitPairs = $this->readUnits = $units = $this->unitService->getReadUnits($this->user);
    }

    /**
     * @param null $aid - NEZBYTNÝ PRO FUNKCI VÝBĚRU JINÉ JEDNOTKY
     */
    public function actionMassAdd(int $id, ?int $aid = null) : void
    {
        $this->id = $id;
        $group    = $this->model->getGroup($id);

        if ($group === null || ! $this->isEditable) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect('Payment:default');
        }

        $campId = $group->getSkautisId();

        if ($campId === null) {
            $this->flashMessage('Neplatné propojení skupiny plateb s táborem.', 'warning');
            $this->redirect('Default:');
        }

        /** @var Participant[] $participants */
        $participants = $this->queryBus->handle(new ParticipantListQuery(new Event(Event::CAMP, $campId)));

        $form = $this['massAddForm'];
        /** @var MassAddForm $form */

        $personsWithPayment = array_fill_keys($this->model->getPersonsWithActivePayment($id), null);

        foreach ($participants as $p) {
            if (array_key_exists($p->getPersonId(), $personsWithPayment)) {
                continue;
            }

            $form->addPerson(
                $p->getPersonId(),
                $this->model->getPersonEmails($p->getPersonId()),
                $p->getDisplayName(),
                $p->getPayment()->isZero() ? null : MoneyFactory::toFloat($p->getPayment())
            );
        }

        $this->template->id       = $id;
        $this->template->showForm = ! empty($participants);
    }

    protected function createComponentMassAddForm() : MassAddForm
    {
        return $this->massAddFormFactory->create($this->id);
    }
}
