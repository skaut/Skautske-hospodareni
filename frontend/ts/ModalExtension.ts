import { Modal } from "bootstrap";

export class ModalExtension {
    constructor(naja: any) {
        naja.snippetHandler.addEventListener('afterUpdate', (event: any) => this.processSnippet(event.snippet));
    }

    private processSnippet(snippet: HTMLElement): void {
        if (! snippet.classList.contains('modal-container')) {
            return;
        }

        const modalElement = snippet.querySelector('.modal');

        if (modalElement) {
            const bootstrapModalInstance = new Modal(modalElement);
            const modalShouldBeVisible = modalElement.innerHTML !== '';

            if (modalShouldBeVisible) {
                this.initializeButtons(modalElement);
                bootstrapModalInstance.show();
            } else {
                bootstrapModalInstance.hide();
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
