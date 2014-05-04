$(document).ready(function() {
    //Combobox
    $(".combobox").combobox(); //nejde předávat parametry

    //fancybox2
    $(".fancybox").fancybox();

    //bootstrap tooltip
    if ($("[rel=tooltip]").length) {
        $("[rel=tooltip]").tooltip();
    }

    // odeslání na formulářích
    $("form.ajax").submit(function() {
        $(this).ajaxSubmit();
        return false;
    });

    // odeslání pomocí tlačítek
    $("form.ajax :submit").click(function() {
        $(this).ajaxSubmit();
        return false;
    });

});

function jqCheckAll(id, name) {
    $("input[name^=" + name + "][type='checkbox']").attr('checked', $('#' + id).is(':checked'));
}

/**
 * checkboxIds - ID vsech zavyslich ceckboxů
 * dependentButtonClass - zavislé prvky mají třídu
 */
function onlyWithCheckbox(checkboxNameStart, dependentButtonClass) {
    $("input[name^=" + checkboxNameStart + "]").change(function() {
        var isSomeChecked = false;
        
        $("input[name^=" + checkboxNameStart + "]").each(function() {
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