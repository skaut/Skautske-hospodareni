import moment from 'moment';
import Pikaday from 'pikaday';

export function initializeDatePicker(element: HTMLElement): void {
    if (element.dataset.datePickerInitialized === 'true') {
        return;
    }

    // Prevent browser date picker for date fields
    element.setAttribute('type', 'text');

    const picker = new Pikaday({
        field: element,
        i18n: {
            months: moment.localeData().months(),
            weekdays: moment.localeData().weekdays(),
            weekdaysShort: moment.localeData().weekdaysShort(),
            previousMonth: '<-',
            nextMonth: '->',
        },
        format: 'DD.MM.YYYY',
        firstDay: 1,
        disableWeekends: element.getAttribute('data-disable-weekends') === 'true',
    });

    element.dataset.datePickerInitialized = 'true';
    initializeDatePickerTrigger(element, picker);
}

function initializeDatePickerTrigger(element: HTMLElement, picker: Pikaday): void {
    const inputGroup = element.closest('.input-group');
    const trigger = inputGroup?.querySelector<HTMLElement>('.input-group-text');

    if (trigger === undefined || trigger === null || trigger.dataset.datePickerTriggerInitialized === 'true') {
        return;
    }

    trigger.dataset.datePickerTriggerInitialized = 'true';
    trigger.classList.add('cursor-pointer');
    trigger.setAttribute('role', 'button');
    trigger.setAttribute('tabindex', '0');
    trigger.setAttribute('aria-label', 'Vybrat datum');

    const openPicker = (event: Event): void => {
        event.preventDefault();
        event.stopPropagation();
        element.focus();
        picker.show();
    };

    trigger.addEventListener('click', openPicker);
    trigger.addEventListener('keydown', (event: KeyboardEvent) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        openPicker(event);
    });
}
