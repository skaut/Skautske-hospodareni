import 'bootstrap.native';
import {dom} from './icons';
import {initializeLogoutTimer} from './logoutTimer';
import {toggleAllCheckboxes} from './utils';
import naja from 'naja';
import { initializeDatePicker } from "./js/datePicker";
import moment from 'moment';
import 'moment/locale/cs';

// Use czech language for dates
moment.locale('cs');

document.addEventListener('DOMContentLoaded', () => {
    naja.initialize();
    document.querySelectorAll('.date').forEach(initializeDatePicker);
    initializeLogoutTimer();
    dom.watch();
});

document.toggleAllCheckboxes = toggleAllCheckboxes;
