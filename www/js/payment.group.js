jQuery(function ($) {
    $('.date').each(function (i, el) {
        el = $(el);
        el.datetimepicker('setDaysOfWeekDisabled', [0, 6]).datetimepicker('setStartDate', '+1d').datetimepicker('setEndDate', '+6m');
    });

    $('#camp-select').change(function (e) {
        $('#group-name-input').val($("#camp-select option:selected").text());
    });
});