import naja from 'naja';
import {ProgressBar} from './ProgressBar';
import {ModalExtension} from './ModalExtension';
import {TravelModule} from "../modules/travel";
import {SnippetProcessor} from "./SnippetProcessor";
import {initializeAutoSubmit} from "./autoSubmitForm";
import {initializeLinksThatRequireConfirmation} from "./confirmDialogs";
import {initializeCheckAllCheckboxes, initializeCheckboxToggle} from "./checkboxes";
import {DataGridExtension} from "./DataGridExtension";
import {initializeDatePicker} from "./datePicker";
import netteForms from "./netteForms";
import {initializeSendMassForm} from "./ChitListExtension"
import {initializeEditForm} from "./ChitListExtension"

export default function (): void {
    naja.registerExtension(new ProgressBar());
    naja.registerExtension(new ModalExtension());

    naja.registerExtension(new TravelModule());
    naja.registerExtension(new SnippetProcessor(snippet => {
        initializeAutoSubmit(naja, snippet, '.auto-submit');
        initializeLinksThatRequireConfirmation(snippet, 'data-confirm');
        initializeCheckAllCheckboxes(snippet, 'data-dependent-checkboxes');
        initializeCheckboxToggle(snippet, 'data-visible-if-checked', 'data-visible-if-not-checked');
        initializeSendMassForm(snippet, 'chits-');
        initializeEditForm(snippet);
        snippet.querySelectorAll<HTMLElement>('.date').forEach(initializeDatePicker);
    }));

    naja.registerExtension(new DataGridExtension());

    naja.formsHandler.netteForms = netteForms;

    // Prevents NS_ERROR_ILLEGAL_VALUE on large pages in Firefox
    naja.historyHandler.historyAdapter = {
        replaceState: () => {},
        pushState: () => {},
    };

    naja.initialize({history: false, forceRedirect: true});
}
