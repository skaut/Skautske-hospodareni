import 'bootstrap.native';
import moment from 'moment';
import 'moment/locale/cs';
import {dom} from './icons';
import {LogoutTimer} from './LogoutTimer';
import initializeAjax from './ts/ajax';
import { initializeDatePicker } from "./ts/datePicker";

// Use czech language for dates
moment.locale('cs');

document.addEventListener('DOMContentLoaded', () => {
    initializeAjax();
    document.querySelectorAll<HTMLElement>('.date').forEach(initializeDatePicker);
    new LogoutTimer('timer', 'timer-minutes');
    dom.watch();
});

