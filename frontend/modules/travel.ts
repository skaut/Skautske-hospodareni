export class TravelModule {
    private readonly naja: any;

    private autoSubmitListener: EventListener;

    constructor(naja: any) {
        this.naja = naja;
        naja.addEventListener('load', () => this.enableRoadworthyUploadAutoSubmit());
    }

    enableRoadworthyUploadAutoSubmit() {
        const control = document.getElementById('scan-upload-control') as HTMLInputElement | null;

        if (control === null) {
            return;
        }

        if (this.autoSubmitListener) {
            control.removeEventListener('change', this.autoSubmitListener);
        }

        control.addEventListener('change', () => {
            this.naja.uiHandler.submitForm(control.form);
        });
    }
}


