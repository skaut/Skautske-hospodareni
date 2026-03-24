import moment from 'moment';
import 'moment/locale/cs';
import 'bootstrap/js/dist/collapse';
import Dropdown from 'bootstrap/js/dist/dropdown';
import {dom} from './icons';
import {DarkModeToggle} from './DarkModeToggle';
import { DesignShowcase } from './modules/DesignShowcase';
import {LogoutTimer} from './LogoutTimer';
import './ts/checkAll';
import initializeAjax from './ts/ajax';
import './app.scss';

// Use czech language for dates
moment.locale('cs');

function initializeDropdowns(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-bs-toggle="dropdown"]').forEach((element) => {
        Dropdown.getOrCreateInstance(element);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeAjax();
    new DarkModeToggle('darkModeToggle');
    new LogoutTimer('timer', 'timer-minutes');
    dom.watch();
    initializeDropdowns();

    if (document.querySelector('[data-test="design-showcase"]')) {
        new DesignShowcase().initialize();
    }
});
