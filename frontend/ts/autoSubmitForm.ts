export function initializeAutoSubmit(naja: any, form: Element): void {
    if (form.tagName.toLowerCase() !== 'form') {
        throw new Error(`Element initialized for auto submit must be "form", "${form.tagName}" given.`);
    }

    form.querySelectorAll('input, select, textarea')
        .forEach(element => element.addEventListener('change', () => naja.uiHandler.submitForm(form, {history: true})));
}
