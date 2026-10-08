function isElementFullyVisible(element: HTMLElement): boolean {
    const rect = element.getBoundingClientRect();
    const viewportHeight = window.visualViewport?.height ?? window.innerHeight;

    return rect.top >= 0 && rect.bottom <= viewportHeight;
}

export function initializeMassAddStickyActions(root: ParentNode = document): void {
    root.querySelectorAll<HTMLFormElement>('form[data-mass-add-form]').forEach(form => {
        if (form.dataset.massAddStickyActionInitialized === '1') {
            return;
        }

        const actions = form.querySelector<HTMLElement>('[data-mass-add-form-actions]');
        const stickyAction = form.querySelector<HTMLElement>('[data-mass-add-sticky-action]');

        if (actions === null || stickyAction === null) {
            return;
        }

        form.dataset.massAddStickyActionInitialized = '1';

        let actionsVisible = isElementFullyVisible(actions);

        const updateStickyAction = (): void => {
            const hasSelectedPerson = [...form.querySelectorAll<HTMLInputElement>('[data-mass-add-person-selection]')]
                .some(checkbox => checkbox.checked);
            const isVisible = hasSelectedPerson && !actionsVisible;

            stickyAction.hidden = !isVisible;
            stickyAction.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
        };

        const observer = new IntersectionObserver(entries => {
            actionsVisible = entries.some(entry => entry.isIntersecting && isElementFullyVisible(actions));
            updateStickyAction();
        }, {threshold: 1});

        observer.observe(actions);
        form.addEventListener('change', updateStickyAction);
        form.querySelectorAll<HTMLInputElement>('[data-dependent-checkboxes]').forEach(checkbox => {
            checkbox.addEventListener('click', () => {
                queueMicrotask(updateStickyAction);
            });
        });
        window.addEventListener('resize', () => {
            actionsVisible = isElementFullyVisible(actions);
            updateStickyAction();
        });

        updateStickyAction();
    });
}
