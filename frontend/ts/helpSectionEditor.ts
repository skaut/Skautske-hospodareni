const initializedAttribute = 'data-help-editor-initialized';

/**
 * Shows only the help blocks that carry content and lets the editor add or remove
 * blocks. The form always posts every block; hiding is purely visual, so an empty
 * block simply is not saved.
 */
export function initializeHelpSectionEditor(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-help-sections]').forEach((container) => {
        if (container.hasAttribute(initializedAttribute)) {
            return;
        }

        container.setAttribute(initializedAttribute, 'true');

        const sections = Array.from(container.querySelectorAll<HTMLElement>('[data-help-section]'));
        const addButton = container.querySelector<HTMLElement>('[data-help-section-add]');

        if (sections.length === 0) {
            return;
        }

        const fieldsOf = (section: HTMLElement) =>
            Array.from(section.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>('input, textarea'));

        const hasContent = (section: HTMLElement) => fieldsOf(section).some((field) => field.value.trim() !== '');

        const setVisible = (section: HTMLElement, visible: boolean) => {
            section.hidden = !visible;
        };

        const visibleSections = () => sections.filter((section) => !section.hidden);

        const renumber = () => {
            visibleSections().forEach((section, index) => {
                const label = section.querySelector<HTMLElement>('[data-help-section-number]');

                if (label !== null) {
                    label.textContent = String(index + 1);
                }
            });
        };

        const refreshAddButton = () => {
            if (addButton === null) {
                return;
            }

            addButton.hidden = visibleSections().length >= sections.length;
        };

        // An untouched form opens with a single block; an existing help opens with the
        // blocks it actually uses.
        let shown = 0;
        sections.forEach((section) => {
            const visible = hasContent(section);
            setVisible(section, visible);

            if (visible) {
                shown += 1;
            }
        });

        if (shown === 0) {
            setVisible(sections[0], true);
        }

        sections.forEach((section) => {
            const removeButton = section.querySelector<HTMLElement>('[data-help-section-remove]');

            if (removeButton === null) {
                return;
            }

            removeButton.addEventListener('click', () => {
                fieldsOf(section).forEach((field) => {
                    field.value = '';
                });

                setVisible(section, false);

                if (visibleSections().length === 0) {
                    setVisible(sections[0], true);
                }

                renumber();
                refreshAddButton();
            });
        });

        addButton?.addEventListener('click', () => {
            const next = sections.find((section) => section.hidden);

            if (next === undefined) {
                return;
            }

            setVisible(next, true);
            next.querySelector<HTMLInputElement>('input, textarea')?.focus();
            renumber();
            refreshAddButton();
        });

        renumber();
        refreshAddButton();
    });
}
