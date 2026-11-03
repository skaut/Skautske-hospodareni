// Keeps digits and one decimal separator (comma or dot) with at most 2 decimals.
export function sanitizeDecimal(value: string): string {
    const [whole, ...rest] = value.replace(/[^\d,.]/g, '').split(/[,.]/);
    const separator = value.match(/[,.]/)?.[0];

    if (separator === undefined || rest.length === 0) {
        return whole;
    }

    return whole + separator + rest.join('').slice(0, 2);
}

// Delegated on document, so inputs in AJAX-loaded modals are covered too.
export function initializeDecimalInputs(): void {
    document.addEventListener('input', event => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.hasAttribute('data-decimal')) {
            return;
        }

        const sanitized = sanitizeDecimal(input.value);
        if (sanitized === input.value) {
            return;
        }

        const caret = Math.min(sanitizeDecimal(input.value.slice(0, input.selectionStart ?? input.value.length)).length, sanitized.length);
        input.value = sanitized;
        input.setSelectionRange(caret, caret);
    });
}
