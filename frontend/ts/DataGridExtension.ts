import type { Extension, InteractionEvent, Naja } from 'naja';

export class DataGridExtension implements Extension {

    private static groupHandlersBound = false;

    public initialize(naja: Naja): void {
        naja.uiHandler.addEventListener('interaction', DataGridExtension.enableSortHistory);
        if (!DataGridExtension.groupHandlersBound) {
            DataGridExtension.groupHandlersBound = true;
            document.addEventListener('change', DataGridExtension.onChange, true);
            document.addEventListener('click', DataGridExtension.onShiftClick, true); // volitelné: shift-range
        }


    }

    private static enableSortHistory(event: InteractionEvent): void {
        const element = event.detail.element;

        if (element.getAttribute('data-naja-history') !== 'off' && element.closest('[data-naja-history="on"]') !== null) {
            event.detail.options.history = true;
        }
    }

    // === Datagrid group actions ===
    private static onChange(e: Event): void {
        const t = e.target as HTMLElement | null;
        if (!t) return;

        // 1) řádkový checkbox → přepnout tlačítka/select + spočítat
        const gridKey = t.getAttribute('data-check');
        if (gridKey) {
            const checked = document.querySelectorAll<HTMLInputElement>(`input[data-check-all-${gridKey}]:checked`);
            const select  = document.querySelector<HTMLSelectElement>(`.datagrid-${gridKey} select[name="group_action[group_action]"]`);
            const buttons = document.querySelectorAll<HTMLButtonElement>(`.datagrid-${gridKey} .row-group-actions *[type="submit"]`);
            const counter = document.querySelector<HTMLElement>(`.datagrid-${gridKey} .datagrid-selected-rows-count`);

            const any = checked.length > 0;
            buttons.forEach(b => { b.disabled = !any; });
            if (select) {
                select.disabled = !any;
                if (!any) select.value = '';
            }
            if (counter) {
                const total = document.querySelectorAll<HTMLInputElement>(`input[data-check-all-${gridKey}]`).length;
                counter.innerHTML = any ? `${checked.length}/${total}` : '';
            }

            if (select) select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // 2) master checkbox → (od)škrtnout všechny + vyvolat jejich change
        const masterKey = t.getAttribute('data-check-all');
        if (masterKey && (t as HTMLInputElement).type === 'checkbox') {
            const checked = (t as HTMLInputElement).checked;
            const inputs = document.querySelectorAll<HTMLInputElement>(`input[type=checkbox][data-check-all-${masterKey}]`);
            inputs.forEach(input => {
                input.checked = checked;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    }

    // Volitelné: SHIFT výběr rozsahu mezi dvěma kliky
    private static lastCheckboxCell: HTMLElement | null = null;
    private static onShiftClick(e: MouseEvent): void {
        const path = (e.composedPath?.() as HTMLElement[]) ?? [];
        const cell = path.find((el: any) => el?.classList?.contains('col-checkbox')) as HTMLElement | undefined;
        if (!cell) return;

        if (DataGridExtension.lastCheckboxCell && e.shiftKey) {
            const currentRow = cell.closest('tr');
            const lastRow    = DataGridExtension.lastCheckboxCell.closest('tr');
            const tbody      = lastRow?.closest('tbody');
            if (!currentRow || !lastRow || !tbody) {
                DataGridExtension.lastCheckboxCell = cell;
                return;
            }

            const rows = Array.from(tbody.querySelectorAll('tr'));
            const i1 = rows.indexOf(lastRow);
            const i2 = rows.indexOf(currentRow);
            if (i1 >= 0 && i2 >= 0) {
                const [from, to] = i1 < i2 ? [i1, i2] : [i2 + 1, i1];
                rows.slice(from, to).forEach(r => {
                    const cb = r.querySelector<HTMLInputElement>('.col-checkbox input[type=checkbox]');
                    if (cb && !cb.checked) {
                        cb.checked = true;
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }
        }
        DataGridExtension.lastCheckboxCell = cell;
    }
}

