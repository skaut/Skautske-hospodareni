// Repeated form rows added and removed in the browser. Markup contract:
// [data-form-items] holds only rows [data-form-item="<index>"]; each row has a [data-form-item-remove] button;
// a [data-form-item-add] button in the same form appends an empty copy of the last row.
// One delegated listener keeps working after a snippet redraw.
export function initializeFormItems(): void {
    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const addButton = event.target.closest<HTMLElement>('[data-form-item-add]');
        if (addButton !== null) {
            addItem(addButton);
            return;
        }

        const removeButton = event.target.closest<HTMLElement>('[data-form-item-remove]');
        if (removeButton !== null) {
            removeItem(removeButton);
        }
    });
}

function addItem(addButton: HTMLElement): void {
    const rows = addButton.closest('form')?.querySelector<HTMLElement>('[data-form-items]');
    if (rows === null || rows === undefined) {
        return;
    }

    const items = [...rows.querySelectorAll<HTMLElement>(':scope > [data-form-item]')];
    const last = items[items.length - 1];
    if (last === undefined) {
        return;
    }

    const oldIndex = last.dataset.formItem ?? '';
    const newIndex = String(Math.max(...items.map((item) => Number(item.dataset.formItem))) + 1);
    const row = last.cloneNode(true) as HTMLElement;
    row.dataset.formItem = newIndex;

    row.querySelectorAll<HTMLElement>('[name], [id]').forEach((element) => {
        const name = element.getAttribute('name');
        if (name !== null) {
            element.setAttribute('name', name.replace(`[${oldIndex}]`, `[${newIndex}]`));
        }
        if (element.id !== '') {
            element.id = element.id.replace(`-${oldIndex}-`, `-${newIndex}-`);
        }
    });
    row.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>('input[type="text"], input[type="hidden"], textarea')
        .forEach((field) => { field.value = ''; });
    row.querySelectorAll('select').forEach((select) => { select.selectedIndex = 0; });
    row.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
    row.querySelectorAll('.invalid-feedback').forEach((message) => message.remove());

    rows.append(row);
    row.querySelector<HTMLElement>('input[type="text"], select, textarea')?.focus();
}

function removeItem(removeButton: HTMLElement): void {
    const form = removeButton.closest('form');
    removeButton.closest('[data-form-item]')?.remove();
    form?.querySelector<HTMLElement>('[data-form-item-add]')?.focus();
}
