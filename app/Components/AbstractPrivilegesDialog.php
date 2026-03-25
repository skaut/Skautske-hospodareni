<?php

declare(strict_types=1);

namespace App\Components;

/**
 * Abstract parent for privileges dialogs across event types (Events, Education, Camps).
 *
 * Subclasses only need to implement buildPrivileges() with type-specific privilege checks.
 */
abstract class AbstractPrivilegesDialog extends Dialog
{
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
    abstract protected function buildPrivileges(): array;
}
