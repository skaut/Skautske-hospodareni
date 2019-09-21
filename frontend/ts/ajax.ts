import naja from 'naja';
// @ts-ignore
import netteForms from 'nette-forms';
import {ProgressBar} from './ProgressBar';
import {ModalExtension} from './ModalExtension';
import {TravelModule} from "../modules/travel";
import {SnippetProcessor} from "./SnippetProcessor";
import {initializeAutoSubmit} from "./autoSubmitForm";
import {initializeLinksThatRequireConfirmation} from "./confirmDialogs";
import {initializeCheckAllCheckboxes} from "./checkAllChekboxes";
import {DataGridExtension} from "./DataGridExtension";
import {initializeDatePicker} from "./datePicker";

export default function (): void {
    naja.registerExtension(ProgressBar);
    naja.registerExtension(ModalExtension);

    naja.registerExtension(TravelModule);
    naja.registerExtension(SnippetProcessor, snippet => {
        initializeAutoSubmit(naja, snippet, '.auto-submit');
        initializeLinksThatRequireConfirmation(snippet, 'data-confirm');
        initializeCheckAllCheckboxes(snippet, 'data-dependent-checkboxes');
        snippet.querySelectorAll<HTMLElement>('.date').forEach(initializeDatePicker);
    });

    naja.registerExtension(DataGridExtension);

    naja.formsHandler.netteForms = netteForms;

    naja.initialize({history: false});
}
