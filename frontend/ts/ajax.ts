import naja from 'naja';
// @ts-ignore
import netteForms from 'nette-forms';
import {ProgressBar} from './ProgressBar';
import {ModalExtension} from './ModalExtension';
import {TravelModule} from "../modules/travel";
import {SnippetProcessor} from "./SnippetProcessor";
import {initializeAutoSubmit} from "./autoSubmitForm";

export default function (): void {
    naja.registerExtension(ProgressBar);
    naja.registerExtension(ModalExtension);

    naja.registerExtension(TravelModule);
    naja.registerExtension(SnippetProcessor, (snippet: Element) => {
        snippet.querySelectorAll('.auto-submit').forEach(form => initializeAutoSubmit(naja, form));
    });

    naja.formsHandler.netteForms = netteForms;

    naja.initialize({history: false});
}
