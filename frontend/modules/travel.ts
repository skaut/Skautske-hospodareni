import type { Extension, Naja } from "naja/dist/Naja";

export class TravelModule implements Extension {
    public initialize(naja: Naja): void {
        // Delegated listener: the upload control lives inside an AJAX snippet and is replaced on
        // every redraw, so binding it directly would only work until the first snippet update.
        document.addEventListener('change', event => {
            const control = event.target;

            if (control instanceof HTMLInputElement && control.id === 'scan-upload-control' && control.form !== null) {
                naja.uiHandler.submitForm(control.form);
            }
        });
    }
}


