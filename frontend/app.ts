import moment from 'moment';
import 'moment/locale/cs';
import {dom} from './icons';
import {DarkModeToggle} from './DarkModeToggle';
import {LogoutTimer} from './LogoutTimer';
import './ts/checkAll';
import initializeAjax from './ts/ajax';
import './app.scss';

import { HelpPanelModule } from './modules/HelpPanelModule';

// Use czech language for dates
moment.locale('cs');

document.addEventListener('DOMContentLoaded', () => {
    initializeAjax();
    new DarkModeToggle('darkModeToggle');
    new LogoutTimer('timer', 'timer-minutes');
    dom.watch();

    // Naše nová inicializace
    if (document.getElementById('toggleHelpButton')) {
        // Pokud tlačítko existuje, modul se inicializuje.
        const helpPanel = new HelpPanelModule();
        helpPanel.initialize();
    }
});