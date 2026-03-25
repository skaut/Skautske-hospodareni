<?php

declare(strict_types=1);

namespace App\Components\Event;

use App\Components\Dialog;
use App\Model\Auth\IAuthorizator;
use App\Model\Auth\Resources\Event;

final class PrivilegesDialog extends Dialog
{
    public function __construct(
        private int $eventId,
        private bool $isDraft,
        private IAuthorizator $authorizator,
    ) {
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->setFile(__DIR__ . '/templates/PrivilegesDialog.latte');
        $this->template->setParameters([
            'customClasses' => 'modal-lg',
            'privileges' => $this->buildPrivileges(),
        ]);
    }

    /** @return array<string, array{label: string, items: array<mixed>}> */
    private function buildPrivileges(): array
    {
        $aid = $this->eventId;

        return [
            'general' => [
                'label' => 'Obecná oprávnění',
                'items' => [
                    [
                        'label' => 'Založit novou akci',
                        'value' => $this->authorizator->isAllowed(Event::CREATE, null),
                        'desc' => 'Lze založil novou akci nezávisle na této?',
                    ],
                ],
            ],
            'event' => [
                'label' => 'Základní informace o akci',
                'items' => [
                    [
                        'label' => 'Upravovat tuto akci',
                        'value' => $this->authorizator->isAllowed(Event::UPDATE, $aid),
                        'desc' => 'Lze upravovat základní údaje o této akci.',
                    ],
                    [
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
                'label' => 'Účastníci',
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
            'cashbook' => [
                'label' => 'Evidence plateb',
                'items' => [
                    [
                        'label' => 'Upravovat pokladní knihu',
                        'value' => $this->isDraft && $this->authorizator->isAllowed(Event::UPDATE_PARTICIPANT, $aid),
                        'desc' => 'Lze editovat evidenci plateb této akce.',
                    ],
                ],
            ],
        ];
    }
}
