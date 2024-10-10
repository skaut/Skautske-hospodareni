import type { Extension, Naja } from "naja/dist/Naja";
import type { AfterUpdateEvent } from "naja/dist/core/SnippetHandler";

type Processor = (snippet: Element, naja: Naja) => void;

export class SnippetProcessor implements Extension {
    private readonly process: Processor;

    constructor(process: Processor) {
        this.process = process;
    }

    public initialize(naja: Naja): void {
        this.process(window.document.body, naja);
        naja.snippetHandler
            .addEventListener('afterUpdate', (event: AfterUpdateEvent) => this.process(event.detail.snippet, naja));
    }
}
