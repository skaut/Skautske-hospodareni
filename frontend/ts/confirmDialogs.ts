export function initializeLinksThatRequireConfirmation(container: Element, messageAttribute: string): void {
    container.querySelectorAll(`[${messageAttribute}]`)
        .forEach(link => {
            link.addEventListener('click', event => {
                if (! confirm(link.getAttribute(messageAttribute) as string)) {
                    event.preventDefault();
                }
            })
        });
}
