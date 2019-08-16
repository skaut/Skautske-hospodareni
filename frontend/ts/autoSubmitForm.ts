export function initializeAutoSubmit(naja: any, container: Element, selector: string): void {
    container.querySelectorAll(selector)
        .forEach(form => {
            if (form.tagName.toLowerCase() !== 'form') {
                throw new Error(`Element initialized for auto submit must be "form", "${form.tagName}" given.`);
            }

            form.querySelectorAll('input, select, textarea')
                .forEach(element => {
                    element.addEventListener('change', () => naja.uiHandler.submitForm(form, {history: true}));
                });
        });
}
