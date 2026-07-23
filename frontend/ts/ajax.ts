import naja from 'naja';
import Dropdown from 'bootstrap/js/dist/dropdown';
import Tooltip from 'bootstrap/js/dist/tooltip';
// @ts-ignore
import {ProgressBar} from './ProgressBar';
import {ModalExtension} from './ModalExtension';
import {TravelModule} from "../modules/travel";
import {SnippetProcessor} from "./SnippetProcessor";
import {initializeAutoSubmit} from "./autoSubmitForm";
import {initializeLinksThatRequireConfirmation} from "./confirmDialogs";
import {initializeCheckAllCheckboxes, initializeCheckboxToggle} from "./checkboxes";
import {initializeDependentSelect} from "./dependentSelect";
import {DataGridExtension} from "./DataGridExtension";
import {initializeDatePicker} from "./datePicker";
import {initializeMassEmailSelection} from "./massEmailSelection";
import netteForms from "./netteForms";
import {initializeSendMassForm} from "./ChitListExtension"
import {initializeEditForm} from "./ChitListExtension"
import {initializePageEnhancements} from "./pageEnhancements";

function initializeDropdowns(root: ParentNode): void {
    root.querySelectorAll<HTMLElement>('[data-bs-toggle="dropdown"]').forEach((element) => {
        Dropdown.getOrCreateInstance(element);
    });
}

function initializeTooltips(root: ParentNode): void {
    root.querySelectorAll<HTMLElement>('[data-bs-toggle="tooltip"]').forEach((element) => {
        Tooltip.getOrCreateInstance(element);
    });
}

export default function (): void {
    naja.registerExtension(ProgressBar);

    naja.registerExtension(TravelModule);
    naja.registerExtension(SnippetProcessor, snippet => {
        initializeAutoSubmit(naja, snippet, '.auto-submit');
        initializeLinksThatRequireConfirmation(snippet, 'data-confirm');
        initializeCheckAllCheckboxes(snippet, 'data-dependent-checkboxes');
        initializeCheckboxToggle(snippet, 'data-visible-if-checked', 'data-visible-if-not-checked');
        initializeDependentSelect(snippet, 'data-depends');
        initializeSendMassForm(snippet, 'chits-');
        initializeEditForm(snippet,'chits-');
        snippet.querySelectorAll<HTMLElement>('.date').forEach(initializeDatePicker);
        initializeDropdowns(snippet);
        initializeTooltips(snippet);
        initializeMassEmailSelection(snippet);
        initializePageEnhancements(snippet);
    });

    naja.registerExtension(ModalExtension);
    naja.registerExtension(DataGridExtension);

    naja.formsHandler.netteForms = netteForms;

    // Prevents NS_ERROR_ILLEGAL_VALUE on large pages in Firefox
    (naja as any).historyHandler.historyAdapter = {
        replaceState: () => {},
        pushState: () => {},
    };

    naja.initialize({history: false, forceRedirect: true});
}
