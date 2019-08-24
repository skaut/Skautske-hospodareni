export function initializeAutoSubmit(naja: any, container: Element, selector: string): void {
    container.querySelectorAll(selector)
        .forEach(snippet => {
            const form = (snippet.tagName.toLowerCase() === 'form' ? snippet : snippet.closest('form')) as HTMLFormElement | null;

            if (form === null) {
                throw new Error('Element initialized for auto submit must be "form" or element inside form');
            }

            snippet.querySelectorAll('input, select, textarea')
                .forEach(element => {
                    element.addEventListener('change', () => naja.uiHandler.submitForm(form, {history: true}));
                });
        });
}
