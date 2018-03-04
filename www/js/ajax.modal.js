(function ($) {
    $.nette.ext('bs-modal', {
        success: function (payload) {
            var self = this;

            this.ext('snippets', true).after($.proxy(function ($el) {
                if (!$el.is('.modal')) {
                    return;
                }

                self.open($el);
            }, this));

            if (payload.snippets === undefined) {
                return;
            }

            $.each(payload.snippets, function (snippet) {
                self.open($('.modal[id="' + snippet + '"]'));
            });
        }
    }, {
        open: function (el) {
            var content = el.find('.modal-content');
            if (!content.length) {
                el.modal('hide');
                return;
            }

            var form = el.find('form');

            if (form.length === 1) {
                this.initButtons(form, el);
            }

            el.modal();
        },
        initButtons: function (form, modal) {
            var footer = $('.modal-footer', modal);

            if (!footer.length) {
                return;
            }

            $('input[type="button"]', footer).remove();

            $(':submit, :button', form).each(function (i, btn) {
                $(btn).hide();

                var button = $('<input type="button" value="' + $(btn).val() + '" class="btn btn-primary ' + $(btn).attr('class') + '">').on('click', function () {
                    $(btn).trigger('click');
                });

                footer.prepend(button);
            });
        }
    });
})(jQuery);
