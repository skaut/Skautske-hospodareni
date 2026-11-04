const SELECTOR = 'textarea[data-autogrow]';

function resize(textarea: HTMLTextAreaElement): void {
    textarea.style.height = 'auto';
    textarea.style.height = `${textarea.scrollHeight + textarea.offsetHeight - textarea.clientHeight}px`;
}

export function resizeAutoGrowTextareas(root: ParentNode = document): void {
    root.querySelectorAll<HTMLTextAreaElement>(SELECTOR).forEach(resize);
}

// Single-line text that wraps instead of scrolling horizontally: Enter and pasted line breaks become nothing / spaces.
// Delegated on document, so textareas in AJAX snippets are covered too.
export function initializeAutoGrowTextareas(): void {
    document.addEventListener('keydown', event => {
        if (event.key === 'Enter' && event.target instanceof HTMLTextAreaElement && event.target.matches(SELECTOR)) {
            event.preventDefault();
        }
    });

    document.addEventListener('input', event => {
        const textarea = event.target;
        if (!(textarea instanceof HTMLTextAreaElement) || !textarea.matches(SELECTOR)) {
            return;
        }

        if (/[\r\n]/.test(textarea.value)) {
            const caret = textarea.selectionStart;
            textarea.value = textarea.value.replace(/\r?\n|\r/g, ' ');
            textarea.setSelectionRange(caret, caret);
        }
        resize(textarea);
    });

    // Hidden textareas (collapsed chit form) have no height until shown.
    document.addEventListener('shown.bs.collapse', event => resizeAutoGrowTextareas(event.target as ParentNode));
    window.addEventListener('resize', () => resizeAutoGrowTextareas());
}
