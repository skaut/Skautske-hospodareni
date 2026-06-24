import moment from 'moment';
import 'moment/locale/cs';
import 'bootstrap/js/dist/collapse';
import Dropdown from 'bootstrap/js/dist/dropdown';
import Tooltip from 'bootstrap/js/dist/tooltip';
import {dom} from './icons';
import {DarkModeToggle} from './DarkModeToggle';

import {LogoutTimer} from './LogoutTimer';
import './ts/checkAll';
import initializeAjax from './ts/ajax';
import {initializeMassEmailSelection} from './ts/massEmailSelection';
import {initializePageEnhancements} from './ts/pageEnhancements';
import './app.scss';

// Use czech language for dates
moment.locale('cs');

function initializeDropdowns(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-bs-toggle="dropdown"]').forEach((element) => {
        Dropdown.getOrCreateInstance(element);
    });
}

function initializeTooltips(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-bs-toggle="tooltip"]').forEach((element) => {
        Tooltip.getOrCreateInstance(element);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeAjax();
    new DarkModeToggle('darkModeToggle');
    new LogoutTimer('timer', 'timer-minutes');
    dom.watch();
    initializeDropdowns();
    initializeTooltips();
    initializeMassEmailSelection();
    initializePageEnhancements();
});
