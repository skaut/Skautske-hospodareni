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

    // odeslání na formulářích
    $("form.ajax").submit(function () {
        $(this).ajaxSubmit();
        return false;
    });

    // odeslání pomocí tlačítek
    $("form.ajax :submit").click(function () {
        $(this).ajaxSubmit();
        return false;
    });

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

});

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
