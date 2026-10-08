// The e-mail list of a form (Component\Forms\EmailListControl): the offered addresses are checkboxes, and an
// address typed into the input below becomes a checked row on "Přidat" or Enter (Enter must not submit the
// form); an address that is already listed is just ticked. An address left in the input is taken by the
// server on submit anyway.
export function initializeEmailLists(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-email-list]').forEach((list) => {
        if (list.dataset.emailListInitialized === '1') {
            return;
        }

        const items = list.querySelector<HTMLElement>('[data-email-list-items]');
        const input = list.querySelector<HTMLInputElement>('[data-email-list-input]');
        const addButton = list.querySelector<HTMLButtonElement>('[data-email-list-add]');
        if (items === null || input === null || addButton === null) {
            return;
        }

        list.dataset.emailListInitialized = '1';
        let added = 0;

        const add = (): void => {
            const address = input.value.trim();
            if (address === '') {
                return;
            }

            if (!input.checkValidity()) {
                input.reportValidity();

                return;
            }

            const existing = Array.from(items.querySelectorAll<HTMLInputElement>('input[type="checkbox"]'))
                .find((checkbox) => checkbox.value.toLowerCase() === address.toLowerCase());
            if (existing !== undefined) {
                existing.checked = true;
            } else {
                items.append(createItem(list, address, added++));
            }

            input.value = '';
            input.focus();
        };

        addButton.addEventListener('click', add);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                add();
            }
        });
    });
}

function createItem(list: HTMLElement, address: string, index: number): HTMLElement {
    const id = `${list.dataset.emailListId ?? 'email-list'}-new-${index}`;

    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.className = 'form-check-input';
    checkbox.name = list.dataset.emailListName ?? '';
    checkbox.id = id;
    checkbox.value = address;
    checkbox.checked = true;

    const source = document.createElement('span');
    source.className = 'text-body-secondary';
    source.textContent = `(${list.dataset.emailListFallbackLabel ?? ''})`;

    const label = document.createElement('label');
    label.className = 'form-check-label email-list__label';
    label.htmlFor = id;
    label.append(`${address} `, source);

    const item = document.createElement('div');
    item.className = 'form-check email-list__item';
    item.dataset.test = 'email-list-item';
    item.append(checkbox, label);

    return item;
}
