type Processor = (snippet: HTMLElement, naja: Naja) => void;
type Naja = any & EventTarget;

export class SnippetProcessor {
    private readonly naja: Naja;
    private readonly process: Processor;

    constructor(naja: Naja, process: Processor) {
        this.naja = naja;
        this.process = process;
        this.naja.addEventListener('init', () => this.initialize());
    }

    private initialize() {
        this.process(window.document.body, this.naja);
        this.naja.snippetHandler
            .addEventListener('afterUpdate', (event: any) => this.process(event.snippet, this.naja));
    }
}
