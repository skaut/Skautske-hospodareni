function initializeChitForm($element) {
    $('.chit-form', $element).keydown(function (event) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        $('.btn-primary', $(this)).click();
    });
}

$(document).ready(() => {
    initializeChitForm($(document));

    $.nette.ext({
        success: function (payload) {
            if (payload.snippets === undefined) {
                return;
            }

            $.each(payload.snippets, (snippet) => {
                initializeChitForm($('#' + snippet));
            });
        }
    });
});
