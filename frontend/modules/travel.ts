import type { Extension, Naja } from "naja/dist/Naja";

export class TravelModule implements Extension {
    public initialize(naja: Naja): void {
        const control = document.getElementById('scan-upload-control') as HTMLInputElement | null;

        if (control === null) {
            return;
        }

        control.addEventListener('change', () => {
            naja.uiHandler.submitForm(control.form!);
        });
    }
}


