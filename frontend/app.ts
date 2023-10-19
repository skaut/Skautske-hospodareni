import moment from 'moment';
import 'moment/locale/cs';
import {dom} from './icons';
import {DarkModeToggle} from './DarkModeToggle';
import {LogoutTimer} from './LogoutTimer';
import initializeAjax from './ts/ajax';
import './app.scss';

// Use czech language for dates
moment.locale('cs');

document.addEventListener('DOMContentLoaded', () => {
    initializeAjax();
    new DarkModeToggle('darkModeToggle');
    new LogoutTimer('timer', 'timer-minutes');
    dom.watch();
});

