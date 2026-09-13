// @ts-ignore
import netteForms from '../../vendor/nette/forms/src/assets/netteForms';
interface NetteForms {
    showFormErrors(form: HTMLFormElement, messages: { element: Element, message: string }[]): void;
    validators : Object;
}

const invalidControlClass = 'is-invalid';
const errorMessageClass = 'invalid-feedback';

const netteFormsInstance: NetteForms = netteForms;
const originalHandler = netteFormsInstance.showFormErrors;

netteFormsInstance.showFormErrors = (form: HTMLFormElement, messages: { element: Element; message: string }[]) => {
    if (!form.classList.contains('inline-errors')) {
        originalHandler(form, messages);
        return;
    }

    // Make all inputs seem valid
    form.querySelectorAll('.' + invalidControlClass)
        .forEach(control => control.classList.remove(invalidControlClass));

    // Remove old error messages
    form.querySelectorAll('.' + errorMessageClass)
        .forEach(message => message.parentElement!.removeChild(message));

    // Process error messages
    messages.forEach(message => {
        message.element.classList.add(invalidControlClass);

        const messageElement = document.createElement('div');
        messageElement.innerText = message.message;
        messageElement.setAttribute('class', errorMessageClass + ' d-block');

        // Prefer the Bootstrap form group so the message lines up under the whole
        // field. Forms without one still have to show the error, otherwise failed
        // client-side validation would be silent.
        const formGroup = message.element.closest('.mb-3');

        if (formGroup !== null) {
            formGroup.appendChild(messageElement);

            return;
        }

        const anchor = message.element.closest('.input-group') ?? message.element;
        anchor.parentNode?.insertBefore(messageElement, anchor.nextSibling);
    });
};

// @ts-ignore
netteFormsInstance.validators.AppMyValidators_hasSelectedAny = function (elem: HTMLInputElement, arg: Object, val: Array)
    {
        return Object.keys(arg).map(e => {return e.toLowerCase()}).filter(
            value => val.includes(value)
        ).length !== 0;
    };

export default netteFormsInstance;
