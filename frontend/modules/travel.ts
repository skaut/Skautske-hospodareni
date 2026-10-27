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

            if (control instanceof HTMLSelectElement) {
                UNITS.forEach(unit => showUnit(control, unit));
            }
        });

        // Browsers may restore a previously chosen option without firing a change event.
        document.querySelectorAll<HTMLSelectElement>('select').forEach(select => UNITS.forEach(unit => showUnit(select, unit)));
    }
}

// A select with data-<unit>-target="<id>" copies data-<unit> of its selected option into that element.
const UNITS = ['consumptionUnit', 'fuelPriceUnit'] as const;

// The unit text comes from the server-rendered option, so no fuel type mapping lives here.
function showUnit(select: HTMLSelectElement, key: typeof UNITS[number]): void {
    const targetId = select.dataset[`${key}Target`];

    if (targetId === undefined) {
        return;
    }

    const target = document.getElementById(targetId);
    const unit = select.selectedOptions[0]?.dataset[key];

    if (target !== null && unit !== undefined) {
        target.textContent = unit;
    }
}


