// Should be called when DOM is loaded or after AJAX request
function domInit() {
    //Combobox
    $(".combobox").data('live-search', true).selectpicker({
        dropupAuto: false,
        noneSelectedText: 'Nevybráno',
        noneResultsText: 'Nenalezeny žádné výsledky {0}'
    });

    $('[data-dependentselectbox]').dependentSelectBox();
}

$(document).ready(function () {

    $.nette.ext({
        load: domInit,
        init: domInit
    });

    $.nette.ext({
        load: domInit,
        init: domInit
    });

    //nette.ajax.js
    $.nette.init();

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

    $('.num-edit, .price-edit').click(function(e) {
        e.stopPropagation();
    });
    $('.edit-cell').click(function() {
        $(this).find('.num-edit, .price-edit').first().focus();
    })
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
    const checked = document.getElementById(id).checked;

    getCheckboxesWithNamePrefix(name).forEach(function (checkbox) {
        checkbox.checked = checked;
        checkbox.dispatchEvent(new Event('change'));
    });
}

function getCheckboxesWithNamePrefix(name) {
    return document.querySelectorAll('input[name^="' + name + '"][type="checkbox"]');
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
            if ($(this).is(':checked')) {
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

function startLoginTimer() {
    var start = 30;
    var count = start;
    var state = "success";
    var perc;
    timer();
    var counter = setInterval(timer, 60000); //1000 will  run it every 1 minute
    function timer() {
        count = count - 1;
        perc = 100 * (count / start);
        $("#timer .progress-bar").css("width", perc + "%");
        $("#timer").attr("title", count + " min");
        if (perc < 33 && state === "success") {
            state = "danger";
            $("#timer .progress-bar").addClass("progress-bar-danger").removeClass("progress-bar-success");
        }
        if (count <= 0) {
            $("#timer").addClass("navbar-text").removeAttr("style").html('<span class="label label-danger">Byl jsi odhlášen!</span> ')
            clearInterval(counter);
            return;
        }
    }
}
