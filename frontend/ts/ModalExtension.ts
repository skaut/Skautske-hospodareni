import Modal from 'bootstrap/js/dist/modal';
import type { AfterUpdateEvent, BeforeUpdateEvent, Extension, Naja } from 'naja';

import { initializeDatePicker } from './datePicker';

export class ModalExtension implements Extension {
    private modalInstance: Modal | null = null;
    private modalElement: HTMLElement | null = null;
    private naja: any;

    public initialize(naja: Naja): void {
        this.naja = naja;
        naja.snippetHandler.addEventListener('afterUpdate', (event: AfterUpdateEvent) => this.processSnippet(event.detail.snippet));
        naja.snippetHandler.addEventListener('beforeUpdate', (event: BeforeUpdateEvent) => this.beforeSnippetUpdate(event.detail.snippet));
    }

    private beforeSnippetUpdate(snippet: Element): void
    {
        if (! snippet.classList.contains('modal-container')) {
            return;
        }

        const modalElement = snippet.querySelector('.modal');

        if (modalElement) {
            this.modalInstance = Modal.getInstance(modalElement);
        }
    }

    private processSnippet(snippet: Element): void {
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
            this.closeOpenDropdowns();
            this.removeDetachedModals(modalElement);

            // Reset protective inline styles set by server-side template
            modalElement.style.removeProperty('display');
            modalElement.style.removeProperty('pointer-events');

            // Move modal to body to avoid stacking context issues (same as DesignShowcase)
            if (modalElement.parentElement !== document.body) {
                document.body.appendChild(modalElement);
            }

            // Re-bind Naja's UI handlers on the moved modal. Naja binds form submits and
            // `.ajax` links when it processes the updated snippet, but by then this modal
            // (and its form) has already been moved out of the snippet into <body>, so it
            // would otherwise be left unbound and its form would submit as a full page load.
            this.naja.uiHandler.bindUI(modalElement);

            // Remove any stale backdrops before showing
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

            const bootstrapModalInstance = Modal.getOrCreateInstance(modalElement);
            this.initializeDatePickers(modalElement);
            this.initializeButtons(modalElement);
            this.modalElement = modalElement;
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
                this.modalInstance.dispose();
            } catch {
                // Modal element may have been removed from DOM already
            }
            this.modalInstance = null;
        }

        if (this.modalElement !== null) {
            this.modalElement.classList.remove('show');
            this.modalElement.style.display = 'none';
            this.modalElement.style.pointerEvents = 'none';

            if (this.modalElement.parentElement === document.body) {
                this.modalElement.remove();
            }

            this.modalElement = null;
        }

        // Force-remove any leftover backdrops (Bootstrap adds these to document.body)
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

        // Restore body scroll state that Bootstrap's modal sets
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        this.closeOpenDropdowns();
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

    private initializeDatePickers(modal: Element): void
    {
        modal.querySelectorAll<HTMLElement>('.date').forEach(initializeDatePicker);
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

    private closeOpenDropdowns(): void
    {
        document.querySelectorAll<HTMLElement>('[data-bs-toggle="dropdown"][aria-expanded="true"]').forEach(toggle => {
            toggle.setAttribute('aria-expanded', 'false');
        });

        document.querySelectorAll<HTMLElement>('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    }

    private removeDetachedModals(currentModal: HTMLElement): void
    {
        document.querySelectorAll<HTMLElement>('body > .modal').forEach(modal => {
            if (modal !== currentModal) {
                modal.remove();
            }
        });
    }
} 
