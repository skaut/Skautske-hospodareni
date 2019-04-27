// @ts-ignore no support for TypeScript yet, follow https://github.com/jiripudil/Naja/issues/23
import naja from 'naja';
// @ts-ignore
import netteForms from 'nette-forms';
import {ProgressBar} from './ProgressBar';
import {ModalExtension} from './ModalExtension';

export default function (): void {
    naja.registerExtension(ProgressBar);
    naja.registerExtension(ModalExtension);
    naja.formsHandler.netteForms = netteForms;

    naja.initialize({history: false});
}
