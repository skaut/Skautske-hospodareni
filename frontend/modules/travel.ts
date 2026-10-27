import type { Extension, Naja } from "naja";

export class TravelModule implements Extension {
    public initialize(naja: Naja): void {
        // Delegated listener: the upload control lives inside an AJAX snippet and is replaced on
        // every redraw, so binding it directly would only work until the first snippet update.
        document.addEventListener('change', event => {
            const control = event.target;

            if (control instanceof HTMLInputElement && control.id === 'scan-upload-control' && control.form !== null) {
                naja.uiHandler.submitForm(control.form);
            }

            if (control instanceof HTMLSelectElement && control.dataset.consumptionUnitTarget !== undefined) {
                showConsumptionUnit(control);
            }
        });

        // Browsers may restore a previously chosen option without firing a change event.
        document.querySelectorAll<HTMLSelectElement>('select[data-consumption-unit-target]').forEach(showConsumptionUnit);
    }
}

// The unit text comes from the server-rendered option, so no fuel type mapping lives here.
function showConsumptionUnit(select: HTMLSelectElement): void {
    const target = document.getElementById(select.dataset.consumptionUnitTarget ?? '');
    const unit = select.selectedOptions[0]?.dataset.consumptionUnit;

    if (target !== null && unit !== undefined) {
        target.textContent = unit;
    }
}


