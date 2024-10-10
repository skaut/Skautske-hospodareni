import BSN from 'bootstrap.native';

import type { Extension, Naja } from 'naja/dist/Naja';
import type { AfterUpdateEvent } from 'naja/dist/core/SnippetHandler';

export class ModalExtension implements Extension {
    public initialize(naja: Naja): void {
        naja.snippetHandler.addEventListener('afterUpdate', (event: AfterUpdateEvent) => this.processSnippet(event.detail.snippet));
    }

    private processSnippet(snippet: Element): void {
        if (! snippet.classList.contains('modal')) {
            return;
        }

        const modal = new BSN.Modal(snippet);

        if (snippet.innerHTML === '') {
            modal.hide();
            return;
        }

        this.initializeButtons(snippet);

        modal.show();
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
