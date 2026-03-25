<?php

declare(strict_types=1);

namespace App\Components\Camps;

use App\Components\AbstractPrivilegesDialog;
use App\Model\Auth\IAuthorizator;
use App\Model\Auth\Resources\Camp;

final class PrivilegesDialog extends AbstractPrivilegesDialog
{
    public function __construct(
        private int $campId,
        private IAuthorizator $authorizator,
    ) {
    }

    /** @return array<string, array{label: string, items: array<mixed>}> */
    protected function buildPrivileges(): array
    {
        $aid = $this->campId;

        return [
            'event' => [
                'label' => 'Základní informace o táboře',
                'items' => [
                    [
                        'label' => 'Zobrazovat detaily tábora',
                        'value' => $this->authorizator->isAllowed(Camp::ACCESS_DETAIL, $aid),
                        'desc' => 'Lze zobrazovat další údaje o tomto táboře.',
                    ],
                    [
                        'label' => 'Upravovat tento tábor',
                        'value' => $this->authorizator->isAllowed(Camp::UPDATE, $aid),
                        'desc' => 'Lze upravovat základní údaje o tomto táboře.',
                    ],
                    [
                        'label' => 'Upravovat po uzavření',
                        'value' => $this->authorizator->isAllowed(Camp::UPDATE_REAL, $aid),
                        'desc' => 'Lze upravovat údaje tábora i po uzavření.',
                    ],
                    [
                        'label' => 'Zobrazovat vedení tábora',
                        'value' => $this->authorizator->isAllowed(Camp::ACCESS_FUNCTIONS, $aid),
                        'desc' => 'Lze zobrazovat vedení tohoto tábora.',
                    ],
                ],
            ],
            'participant' => [
                'label' => 'Účastníci',
                'items' => [
                    [
                        'label' => 'Zobrazovat účastníky',
                        'value' => $this->authorizator->isAllowed(Camp::ACCESS_PARTICIPANTS, $aid),
                        'desc' => 'Lze zobrazovat účastníky tohoto tábora.',
                    ],
                    [
                        'label' => 'Zobrazovat detaily účastníků',
                        'value' => $this->authorizator->isAllowed(Camp::ACCESS_PARTICIPANT_DETAIL, $aid),
                        'desc' => 'Lze zobrazovat podrobnosti o jednotlivých účastnících.',
                    ],
                    [
                        'label' => 'Přidávat účastníky',
                        'value' => $this->authorizator->isAllowed(Camp::ADD_PARTICIPANT, $aid),
                        'desc' => 'Lze přidat účastníky tohoto tábora.',
                    ],
                    [
                        'label' => 'Odebírat účastníky',
                        'value' => $this->authorizator->isAllowed(Camp::REMOVE_PARTICIPANT, $aid),
                        'desc' => 'Lze odebrat účastníky tohoto tábora.',
                    ],
                    [
                        'label' => 'Upravovat účastníky',
                        'value' => $this->authorizator->isAllowed(Camp::UPDATE_PARTICIPANT, $aid),
                        'desc' => 'Lze upravovat údaje o účastnících tohoto tábora.',
                    ],
                    [
                        'label' => 'Nastavit automatické dopočítávání',
                        'value' => $this->authorizator->isAllowed(Camp::SET_AUTOMATIC_PARTICIPANTS_CALCULATION, $aid),
                        'desc' => 'Lze aktivovat automatický dopočet osobodnů a dětodnů.',
                    ],
                ],
            ],
            'cashbook' => [
                'label' => 'Evidence plateb a rozpočet',
                'items' => [
                    [
                        'label' => 'Upravovat evidenci plateb',
                        'value' => $this->authorizator->isAllowed(Camp::UPDATE_REAL_COST, $aid),
                        'desc' => 'Lze editovat evidenci plateb tohoto tábora.',
                    ],
                    [
                        'label' => 'Upravovat rozpočet',
                        'value' => $this->authorizator->isAllowed(Camp::UPDATE_BUDGET, $aid),
                        'desc' => 'Lze upravovat částky rozpočtu tohoto tábora ve SkautISu.',
                    ],
                ],
            ],
        ];
    }
}
