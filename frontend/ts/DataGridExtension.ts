import type { Extension, Naja } from 'naja/dist/Naja';
import type { InteractionEvent } from 'naja/dist/core/UIHandler';

export class DataGridExtension implements Extension {
    public initialize(naja: Naja): void {
        naja.uiHandler.addEventListener('interaction', DataGridExtension.enableSortHistory);
    }

    private static enableSortHistory(event: InteractionEvent): void {
        const element = event.detail.element;

        if (element.getAttribute('data-naja-history') !== 'off' && element.closest('[data-naja-history="on"]') !== null) {
            event.detail.options.history = true;
        }
    }
}

