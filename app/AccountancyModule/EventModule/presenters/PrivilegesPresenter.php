<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use Model\Auth\Resources\Event;

class PrivilegesPresenter extends BasePresenter
{
    public function renderDefault(int $aid) : void
    {
        $this->setLayout('layout.new');
        $isDraft = $this->event->getState() === 'draft';

        $privileges = [
            'general' => [
                'label'=> 'Obecná oprávnění',
                'items' => [
                    'create-event' => [
                        'label' => 'Založit novou akci',
                        'value' => $this->authorizator->isAllowed(Event::CREATE, null),
                        'desc' => 'Lze založil novou akci nezávisle na této?',
                    ],
                ],
            ],
            'event' => [
                'label'=> 'Základní informace o akci',
                'items' => [
                    [
                        'label' => 'Upravovat tuto akci',
                        'value' => $this->authorizator->isAllowed(Event::UPDATE, $aid),
                        'desc' => 'Lze upravovat základní údaje o této akci.',
                    ],
                    'close-event' => [
                        'label' => 'Uzavřít tuto akci',
                        'value' => $this->authorizator->isAllowed(Event::CLOSE, $aid),
                        'desc' => 'Lze uzavřít tuto akci. Akce musí být otevřená, aby mohla být uzavřena.',
                    ],
                    [
                        'label' => 'Otevřít tuto akci',
                        'value' => $this->authorizator->isAllowed(Event::OPEN, $aid),
                        'desc' => 'Lze otevřít tuto akci. Akce musí být uzavřena, aby mohla být opět otevřena.',
                    ],
                    [
                        'label' => 'Upravovat funkce této akce',
                        'value' => $this->authorizator->isAllowed(Event::UPDATE_FUNCTION, $aid),
                        'desc' => 'Lze měnit obrazení funkcí (vedoucí, zástupce, hospodář) této akce.',
                    ],
                ],
            ],
            'participant' => [
                'label'=> 'Účastníci',
                'items' => [
                    [
                        'label' => 'Přidávat účastníky',
                        'value' => $this->authorizator->isAllowed(Event::UPDATE_PARTICIPANT, $aid),
                        'desc' => 'Lze přidat účastníky této akce.',
                    ],
                    [
                        'label' => 'Odebírat účastníky',
                        'value' => $this->authorizator->isAllowed(Event::REMOVE_PARTICIPANT, $aid),
                        'desc' => 'Lze odebrat účastníky této akce.',
                    ],
                ],
            ],
            'cashbook'=>[
                'label'=>'Evidence plateb',
                'items'=> [
                    [
                        'label' => 'Upravovat pokladní knihu',
                        'value' => $isDraft && $this->authorizator->isAllowed(Event::UPDATE_PARTICIPANT, $this->aid),
                        'desc' => 'Lze editovat evidenci plateb této akce.',
                    ],
                ],
            ],
        ];
        $this->template->setParameters(['privileges' => $privileges]);
    }
}
