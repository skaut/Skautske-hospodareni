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
