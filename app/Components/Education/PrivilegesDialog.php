<?php

declare(strict_types=1);

namespace App\Components\Education;

use App\Components\AbstractPrivilegesDialog;
use App\Model\Auth\IAuthorizator;
use App\Model\Auth\Resources\Education;
use App\Model\Auth\Resources\Grant;

final class PrivilegesDialog extends AbstractPrivilegesDialog
{
    public function __construct(
        private int $eventId,
        private ?int $grantId,
        private IAuthorizator $authorizator,
    ) {
    }

    /** @return array<string, array{label: string, items: array<mixed>}> */
    protected function buildPrivileges(): array
    {
        $aid = $this->eventId;

        $privileges = [
            'event' => [
                'label' => 'Základní informace o akci',
                'items' => [
                    [
                        'label' => 'Zobrazovat detaily o akci',
                        'value' => $this->authorizator->isAllowed(Education::ACCESS_DETAIL, $aid),
                        'desc' => 'Lze zobrazovat další údaje o této akci.',
                    ],
                    [
                        'label' => 'Upravovat tuto akci',
                        'value' => $this->authorizator->isAllowed(Education::UPDATE, $aid),
                        'desc' => 'Lze upravovat základní údaje o této akci.',
                    ],
                    [
                        'label' => 'Zobrazovat vedení akce',
                        'value' => $this->authorizator->isAllowed(Education::ACCESS_FUNCTIONS, $aid),
                        'desc' => 'Lze zobrazovat vedení této akce.',
                    ],
                ],
            ],
            'participant' => [
                'label' => 'Účastníci',
                'items' => [
                    [
                        'label' => 'Zobrazovat účastníky',
                        'value' => $this->authorizator->isAllowed(Education::ACCESS_PARTICIPANTS, $aid),
                        'desc' => 'Lze zobrazovat účastníky této akce.',
                    ],
                    [
                        'label' => 'Zobrazovat účastníky kurzů',
                        'value' => $this->authorizator->isAllowed(Education::ACCESS_COURSE_PARTICIPANTS, $aid),
                        'desc' => 'Lze zobrazovat počty účastníků kurzů této akce.',
                    ],
                ],
            ],
            'budget' => [
                'label' => 'Rozpočet',
                'items' => [
                    [
                        'label' => 'Zobrazovat rozpočet',
                        'value' => $this->authorizator->isAllowed(Education::ACCESS_BUDGET, $aid),
                        'desc' => 'Lze zobrazovat položky rozpočtu této akce.',
                    ],
                ],
            ],
        ];

        if ($this->grantId !== null) {
            $privileges['participant']['items'][] = [
                'label' => 'Zobrazovat účasti účastníků',
                'value' => $this->authorizator->isAllowed(Grant::ACCESS_PARTICIPANT_PARTICIPATION, $this->grantId),
                'desc' => 'Lze zobrazovat údaje o účasti jednotlivých účastníků této akce.',
            ];
            $privileges['budget']['items'][] = [
                'label' => 'Zobrazovat dotaci',
                'value' => $this->authorizator->isAllowed(Grant::ACCESS_DETAIL, $this->grantId),
                'desc' => 'Lze zobrazovat dotaci této akce.',
            ];
            $privileges['budget']['items'][] = [
                'label' => 'Upravovat závěrečný rozpočet',
                'value' => $this->authorizator->isAllowed(Grant::UPDATE_REAL_BUDGET_SPENDING, $this->grantId),
                'desc' => 'Lze upravovat závěrečný rozpočet této akce ve SkautISu.',
            ];
        }

        return $privileges;
    }
}
