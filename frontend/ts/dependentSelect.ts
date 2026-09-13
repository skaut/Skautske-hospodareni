/**
 * Native dependent selectboxes (replaces the abandoned skaut/dependent-select-box package).
 *
 * A child <select> declares:
 *   - `data-depends` = the html name of the parent <select>
 *   - `data-items`   = JSON map { parentValue: { optionValue: optionLabel } }
 *
 * When the parent changes, the child's options are rebuilt from `data-items`.
 * The server rebuilds the same items in Form::$onAnchor, so validation and the
 * no-JavaScript fallback keep working.
 */
export function initializeDependentSelect(container: ParentNode, selectorAttribute: string): void {
    container.querySelectorAll<HTMLSelectElement>(`select[${selectorAttribute}]`).forEach(child => {
        const parentName = child.getAttribute(selectorAttribute);
        const form = child.form;
        if (parentName === null || form === null) {
            return;
        }

        const parent = form.querySelector<HTMLSelectElement>(`[name="${CSS.escape(parentName)}"]`);
        if (parent === null) {
            return;
        }

        let itemsByParent: Record<string, Record<string, string>> = {};
        try {
            itemsByParent = JSON.parse(child.getAttribute('data-items') ?? '{}');
        } catch {
            itemsByParent = {};
        }

        const promptOption = child.querySelector<HTMLOptionElement>('option[value=""]');
        const promptLabel = promptOption !== null ? promptOption.textContent : null;

        parent.addEventListener('change', () => {
            const items = itemsByParent[parent.value] ?? {};
            const previousValue = child.value;

            child.replaceChildren();
            if (promptLabel !== null) {
                child.add(new Option(promptLabel, ''));
            }
            for (const [value, label] of Object.entries(items)) {
                child.add(new Option(label, value));
            }

            if ([...child.options].some(option => option.value === previousValue)) {
                child.value = previousValue;
            }

            // Let netteForms re-evaluate toggles bound to this control.
            child.dispatchEvent(new Event('change', {bubbles: true}));
        });
    });
}
