import 'bootstrap.native';
import {dom} from './icons';
import {initializeLogoutTimer} from './logoutTimer';
import {toggleAllCheckboxes} from './utils';
import naja from 'naja';

dom.watch();
initializeLogoutTimer();

document.addEventListener('DOMContentLoaded', naja.initialize.bind(naja));
document.toggleAllCheckboxes = toggleAllCheckboxes;
