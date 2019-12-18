import moment from 'moment';
import Pikaday from 'pikaday';

export function initializeDatePicker(element: HTMLElement): void {
    // Prevent browser date picker for date fields
    element.setAttribute('type', 'text');

    const value : string | null = element.getAttribute('value');

    if (value !== null) {
        element.setAttribute('value', moment(value).format('L'));
    }

    new Pikaday({
        field: element,
        i18n: {
            months: moment.localeData().months(),
            weekdays: moment.localeData().weekdays(),
            weekdaysShort: moment.localeData().weekdaysShort(),
            previousMonth: '<-',
            nextMonth: '->',
        },
        format: moment.localeData().longDateFormat('L'),
        firstDay: 1,
        disableWeekends: element.getAttribute('data-disable-weekends') === 'true',
    });
}
