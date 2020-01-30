<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use Model\Payment\Payment\State;
use Model\Payment\Summary;
use function array_reduce;

final class GroupProgress extends BaseControl
{
    /** @var array<string, Summary> */
    private $summaries;

    /**
     * @param array<string, Summary> $summaries
     */
    public function __construct(array $summaries)
    {
        parent::__construct();
        $this->summaries = $summaries;
    }

    public function render() : void
    {
        $template = $this->template;

        $template->setFile(__DIR__ . '/templates/GroupProgress.latte');
        $template->setParameters([
            'allPayments' => array_reduce(
                $this->summaries,
                function (Summary $first, Summary $second) : Summary {
                    return $first->add($second);
                },
                new Summary(0, 0),
            ),
            'completedPayments' => $this->summaries[State::COMPLETED],
        ]);

        $template->render();
    }
}
