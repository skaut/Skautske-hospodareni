import 'bootstrap.native';
import {dom} from './icons';
import {initializeLogoutTimer} from './logoutTimer';
import {toggleAllCheckboxes} from './utils';

dom.watch();
initializeLogoutTimer();

document.toggleAllCheckboxes = toggleAllCheckboxes;
