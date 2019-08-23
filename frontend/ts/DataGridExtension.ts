import najaInstance, {InteractionEvent} from 'naja';

export class DataGridExtension {
    public constructor(naja: typeof najaInstance) {
        naja.addEventListener('interaction', DataGridExtension.enableSortHistory);
    }

    private static enableSortHistory(event: InteractionEvent): void {
        const element = event.element;

        if (element.getAttribute('data-naja-history') !== 'off' && element.closest('.datagrid') !== null) {
            (event.options as any).history = true;
        }
    }
}

