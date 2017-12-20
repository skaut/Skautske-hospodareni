// Should be called when DOM is loaded or after AJAX request
function domInit() {
    //Combobox
    $(".combobox").data('live-search', true).selectpicker({
        dropupAuto: false,
        noneSelectedText: 'Nevybráno',
        noneResultsText: 'Nenalezeny žádné výsledky {0}'
    });
}

$(document).ready(function () {

    $.nette.ext({
        load: domInit,
        init: domInit
    });

    //nette.ajax.js
    $.nette.init();

    //fancybox2
    $(".fancybox").fancybox();

    //bootstrap tooltip
    if ($("[rel=tooltip]").length) {
        $("[rel=tooltip]").tooltip();
    }

    $('.dropdown-form').click(function(e) {
        e.stopPropagation();
    });

    Nette.validators.MyValidators_hasSelectedAny = function (elem, args, val) {
        args = Object.keys(args);
        for(var i = 0; i < args.length; i++) {
            if(val.indexOf(args[i]) > -1) {
                return true;
            }
        }
        return false;
    };

    $('[data-confirm]').click(function(e) {
        if(!confirm($(this).data('confirm'))) {
            e.preventDefault();
        }
    });

    prepareGroupActions();

});


function prepareGroupActions() {
    $('.datagrid').each(function() {
        prepareGridGroupActions($(this));
    });

}

function prepareGridGroupActions(grid) {
    const buttons = [];
    const groupActionsRow = grid.find('.row-group-actions th');
    const groupActionsSubmit = groupActionsRow.find('[type="submit"]');

    groupActionsRow.find('select').each(function() {
        const select = $(this);

        select.find('option').each(function () {
            var option = $(this);

            if(option.val() === '') {
                return;
            }

            const btn = $('<input>')
                .attr({
                    'type': 'submit',
                    'name': groupActionsSubmit.attr('name'),
                    'class': 'btn btn-default btn-xs pull-right disabled',
                    'value': option.html(),
                });

            buttons.push(btn);
            btn.appendTo(groupActionsRow);

            $(btn).click(function () {
                select.val(option.val());
            });
        });
    });

    const $buttons = $(buttons);
    const checkboxes = grid.find('[type="checkbox"]');

    checkboxes.change(function() {
        $buttons.each(function() {
            this.toggleClass('disabled', !checkboxes.is(':checked'))
        });
    });

}

function jqCheckAll(id, name) {
    $("input[name^=" + name + "][type='checkbox']").prop('checked', $('#' + id).is(':checked'));
}

function jqCheckAll2(el, className)
{
    var selector = "." + className + " input[type='checkbox']";
    $(selector).prop('checked', $(el).is(':checked'));
}

/**
 * checkboxIds - ID vsech zavyslich ceckboxů
 * dependentButtonClass - zavislé prvky mají třídu
 */
function onlyWithCheckbox(checkboxNameStart, dependentButtonClass) {
    $("input[name^=" + checkboxNameStart + "]").change(function () {
        var isSomeChecked = false;

        $("input[name^=" + checkboxNameStart + "]").each(function () {
            if (this.checked) {
                isSomeChecked = true;
                return false;//konec cyklu
            }
        });
        if (isSomeChecked)
            $("." + dependentButtonClass).removeClass("disabled").removeAttr("disabled");
        else
            $("." + dependentButtonClass).addClass("disabled").attr("disabled", "disabled");
    });

//    //Vyřešeno přes class="ajaxA"
//    //zajisteni pohybu mezi strankami pres ajax
//    $('#navAjax a').click(function(e) {
//        href = $(this).attr("href");
//        history.pushState('', href, href);//state, title, url
//        e.preventDefault();
//    });
//
//    window.onpopstate = function(event) {
//        $.post(location.href);
//    };
}
