<?php

declare(strict_types=1);

namespace App\AccountancyModule\EducationModule;

use Model\Auth\Resources\Education;
use Model\Auth\Resources\Grant;

class PrivilegesPresenter extends BasePresenter
{
    public function renderDefault(int $aid): void
    {
        $this->setLayout('layout.new');
        $isDraft = $this->event->getState() === 'draft';
        $grantId = $this->event->grantId->toInt();

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
                        'label' => 'Upravovat účastníky',
                        'value' => $this->authorizator->isAllowed(Education::UPDATE_PARTICIPANT, $aid),
                        'desc' => 'Lze upravovat účastníky této akce.',
                    ],
                    [
                        'label' => 'Zobrazovat účastníky kurzů',
                        'value' => $this->authorizator->isAllowed(Education::ACCESS_COURSE_PARTICIPANTS, $aid),
                        'desc' => 'Lze zobrazovat počty účastníků kurzů této akce.',
                    ],
                    [
                        'label' => 'Zobrazovat účasti účastníků',
                        'value' => $this->event->grantId !== null && $this->authorizator->isAllowed(Grant::ACCESS_PARTICIPANT_PARTICIPATION, $grantId),
                        'desc' => 'Lze zobrazovat údaje o účasti jednotlivých účastníků této akce.',
                    ],
                ],
            ],
            'budget' => [
                'label' => 'Rozpočet',
                'items' => [
                    [
                        'label' => 'Zobrazovat rozpočet',
                        'value' => $isDraft && $this->authorizator->isAllowed(Education::ACCESS_BUDGET, $aid),
                        'desc' => 'Lze zobrazovat položky rozpočtu této akce.',
                    ],
                    [
                        'label' => 'Zobrazovat dotaci',
                        'value' => $this->event->grantId !== null && $this->authorizator->isAllowed(Grant::ACCESS_DETAIL, $grantId),
                        'desc' => 'Lze zobrazovat dotaci této akce.',
                    ],
                    [
                        'label' => 'Upravovat závěrečný rozpočet',
                        'value' => $isDraft && $this->authorizator->isAllowed(Grant::UPDATE_REAL_BUDGET_SPENDING, $grantId),
                        'desc' => 'Lze upravovat závěrečný rozpočet této akce ve SkautISu.',
                    ],
                ],
            ],
        ];
        $this->template->setParameters(['privileges' => $privileges]);
    }
}
