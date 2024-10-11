import { Modal } from "bootstrap";

import type { AfterUpdateEvent, BeforeUpdateEvent, Extension, Naja } from 'naja';

export class ModalExtension implements Extension {
    private modalInstance: Modal | null;

    public initialize(naja: Naja): void {
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

        const modalElement = snippet.querySelector('.modal');

        if (modalElement) {
            const bootstrapModalInstance = new Modal(modalElement);
            const modalShouldBeVisible = modalElement.innerHTML.trim() !== '';

            if (modalShouldBeVisible) {
                this.initializeButtons(modalElement);
                bootstrapModalInstance.show();
            } else {
                if (this.modalInstance) {
                    this.modalInstance.hide();
                } else {
                    // This should never happen
                    console.warn('Modal instance not set! Can\'t close modal');
                }
            }
        }
    }

    private initializeButtons(modal: Element): void {
        const forms = modal.querySelectorAll('form');
        const footer = modal.querySelector('.modal-footer');

        if (forms.length === 0 || footer === null) {
            return;
        }

        const form: HTMLFormElement = forms.item(0);
        const buttons = Array.from(form.querySelectorAll<HTMLInputElement>('input[type="submit"]'));

        // Add "copy" of buttons to footer
        footer.prepend(
            ...buttons.map(button => {
                const newButton = document.createElement('button');
                newButton.classList.add(...button.classList);
                newButton.innerHTML = button.value;

                newButton.addEventListener('click', () => button.click());

                return newButton;
            })
        );

        // Hide original buttons
        buttons.forEach(button => button.classList.add('d-none'));
    }
} 
