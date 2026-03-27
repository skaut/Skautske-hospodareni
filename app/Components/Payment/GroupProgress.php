<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;
use App\Model\Payment\Payment\State;
use App\Model\Payment\Summary;

use function array_reduce;

final class GroupProgress extends BaseControl
{
    /** @param array<string, Summary> $summaries */
    public function __construct(private array $summaries)
    {
    }

    public function render(): void
    {
        $template = $this->template;

        $template->setFile(__DIR__.'/templates/GroupProgress.latte');
        $template->setParameters([
            'allPayments' => array_reduce(
                $this->summaries,
                function (Summary $first, Summary $second): Summary {
                    return $first->add($second);
                },
                new Summary(0, 0),
            ),
            'completedPayments' => $this->summaries[State::COMPLETED],
        ]);

        $template->render();
    }
}
