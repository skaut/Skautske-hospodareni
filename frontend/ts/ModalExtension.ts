import Modal from 'bootstrap/js/dist/modal';

export class ModalExtension {
    private modalInstance: Modal | null = null;
    private readonly naja: any;

    constructor(naja: any) {
        this.naja = naja;
        naja.snippetHandler.addEventListener('afterUpdate', (event: any) => this.processSnippet(event.snippet));
        naja.snippetHandler.addEventListener('beforeUpdate', (event: any) => this.beforeSnippetUpdate(event.snippet));
    }

    private beforeSnippetUpdate(snippet: HTMLElement): void
    {
        if (! snippet.classList.contains('modal-container')) {
            return;
        }

        const modalElement = snippet.querySelector('.modal');

        if (modalElement) {
            this.modalInstance = Modal.getInstance(modalElement);
        }
    }

    private processSnippet(snippet: HTMLElement): void {
        if (! snippet.classList.contains('modal-container')) {
            return;
        }

        const modalElement = snippet.querySelector<HTMLElement>('.modal');

        if (! modalElement) {
            this.hideCurrentModal();

            return;
        }

        const modalShouldBeVisible = modalElement.innerHTML.trim() !== '';

        if (modalShouldBeVisible) {
            // Reset protective inline styles set by server-side template
            modalElement.style.removeProperty('display');
            modalElement.style.removeProperty('pointer-events');

            // Move modal to body to avoid stacking context issues (same as DesignShowcase)
            if (modalElement.parentElement !== document.body) {
                document.body.appendChild(modalElement);
            }

            // Remove any stale backdrops before showing
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

            const bootstrapModalInstance = Modal.getOrCreateInstance(modalElement);
            this.initializeButtons(modalElement);
            this.modalInstance = bootstrapModalInstance;
            bootstrapModalInstance.show();
        } else {
            this.hideCurrentModal();
        }
    }

    private hideCurrentModal(): void
    {
        if (this.modalInstance !== null) {
            try {
                this.modalInstance.hide();
            } catch {
                // Modal element may have been removed from DOM already
            }
            this.modalInstance = null;
        }

        // Force-remove any leftover backdrops (Bootstrap adds these to document.body)
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

        // Restore body scroll state that Bootstrap's modal sets
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    private initializeButtons(modal: Element): void {
        const forms = modal.querySelectorAll('form');
        const footer = modal.querySelector('.modal-footer');

        if (forms.length === 0 || footer === null) {
            return;
        }

        const form: HTMLFormElement = forms.item(0);
        const buttons = Array.from(form.querySelectorAll<HTMLInputElement>('input[type="submit"]'));
        const formId = this.ensureFormId(form);

        // Render footer actions as native submit buttons bound to the form.
        footer.prepend(
            ...buttons.map(button => {
                const newButton = button.cloneNode() as HTMLInputElement;
                newButton.classList.remove('d-none');
                newButton.setAttribute('form', formId);

                return newButton;
            })
        );

        // Hide original buttons
        buttons.forEach(button => button.classList.add('d-none'));
        this.naja.uiHandler.bindUI(footer);
    }

    private ensureFormId(form: HTMLFormElement): string
    {
        if (form.id !== '') {
            return form.id;
        }

        const formId = `modal-form-${Math.random().toString(36).slice(2)}`;
        form.id = formId;

        return formId;
    }
} 
