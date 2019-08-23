import najaInstance, {InteractionEvent} from 'naja';

export class DataGridExtension {
    public constructor(naja: typeof najaInstance) {
        naja.addEventListener('interaction', DataGridExtension.enableSortHistory);
    }

    private static enableSortHistory(event: InteractionEvent): void {
        if (event.element.closest('.datagrid') !== null) {
            (event.options as any).history = true;
        }
    }
}

