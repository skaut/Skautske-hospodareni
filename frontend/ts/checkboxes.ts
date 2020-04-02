/**
 * Support for dependent selectboxes, such as:
 * [ ] select all
 * - [ ] first
 * - [ ] second
 * - [ ] third
 */
export function initializeCheckAllCheckboxes(container: Element, selectorAttribute: string): void {
    container.querySelectorAll<HTMLInputElement>(`[${selectorAttribute}]`).forEach(mainCheckbox => {
        if (mainCheckbox.tagName.toLowerCase() !== 'input') {
            throw new Error(`Element initialized for auto submit must be "input", "${mainCheckbox.tagName}" given.`);
        }

        const parent = mainCheckbox.getAttribute(selectorAttribute) as string;
        const dependentCheckboxes = [...container.querySelectorAll<HTMLInputElement>(parent + " input[type='checkbox']")];

        // Toggle dependent checkboxes when main checkbox is clicked
        mainCheckbox.addEventListener('click', () => {
            dependentCheckboxes.forEach(checkbox => checkbox.checked = mainCheckbox.checked)
        });

        // Toggle main checkbox when any of dependent checkboxes is clicked
        dependentCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('click', () => {
                mainCheckbox.checked = dependentCheckboxes.every(checkbox => checkbox.checked);
            })
        });
    });
}

function toggleElement(element: Element, visibility: boolean): void {
    if (visibility) {
        element.classList.remove('d-none');
    } else {
        element.classList.add('d-none');
    }
}

export function initializeCheckboxToggle(container: Element, visibleIfCheckedAttribute: string, visibleIfNotCheckedAttribute: string): void {
    const toggles = {
        [visibleIfCheckedAttribute]:
            (element: Element, allUnchecked: boolean) => toggleElement(element, ! allUnchecked),
        [visibleIfNotCheckedAttribute]:
            (element: Element, allUnchecked: boolean) => toggleElement(element, allUnchecked)
    };

    Object.entries(toggles).forEach(([selectorAttribute, toggle]) => {
        container.querySelectorAll(`[${selectorAttribute}]`).forEach(element => {
            const checkboxes = [...container.querySelectorAll<HTMLInputElement>(element.getAttribute(selectorAttribute) as string)];

            toggle(element, checkboxes.every(c => ! c.checked));

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    toggle(element, checkboxes.every(c => ! c.checked));
                })
            });
        });
    });
}
