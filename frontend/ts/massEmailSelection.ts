export function initializeMassEmailSelection(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-mass-email-selection]').forEach(selection => {
        if (selection.dataset.massEmailSelectionInitialized === '1') {
            return;
        }

        const form = selection.closest('form');
        if (form === null) {
            return;
        }

        selection.dataset.massEmailSelectionInitialized = '1';
        selection.querySelectorAll<HTMLInputElement>('[data-mass-email-type-toggle]').forEach(toggle => {
            toggle.addEventListener('change', () => {
                const emailType = toggle.dataset.massEmailTypeToggle;
                if (emailType === undefined) {
                    return;
                }

                form.querySelectorAll<HTMLSelectElement>('[data-mass-email-select]').forEach(select => {
                    Array.from(select.options).forEach(option => {
                        if (option.dataset.emailType === emailType) {
                            option.selected = toggle.checked;
                        }
                    });

                    select.dispatchEvent(new Event('change', {bubbles: true}));
                });
            });
        });
    });
}
