import 'bootstrap.native';
import {dom} from './icons';
import {LogoutTimer} from './LogoutTimer';
import {toggleAllCheckboxes, toggleMainCheckbox} from './utils';

// @ts-ignore no support for TypeScript yet, follow https://github.com/jiripudil/Naja/issues/23
import naja from 'naja';
//@ts-ignore
import netteForms from 'nette-forms';

import { initializeDatePicker } from "./ts/datePicker";
import * as moment from 'moment';
import 'moment/locale/cs';
import { ProgressBar } from "./ts/ProgressBar";
import {ModalExtension} from "./ts/ModalExtension";

// Use czech language for dates
moment.locale('cs');

naja.registerExtension(ProgressBar);
naja.registerExtension(ModalExtension);
naja.formsHandler.netteForms = netteForms;

document.addEventListener('DOMContentLoaded', () => {
    naja.initialize({history: false});
    document.querySelectorAll<HTMLElement>('.date').forEach(initializeDatePicker);
    new LogoutTimer('timer', 'timer-minutes');
    dom.watch();
});

(document as any).toggleAllCheckboxes = toggleAllCheckboxes;
(document as any).toggleMainCheckbox = toggleMainCheckbox;
