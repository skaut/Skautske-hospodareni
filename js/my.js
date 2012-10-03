$(document).ready(function() {
    //Combobox
    $( ".combobox" ).combobox(); //nejde předávat parametry
    
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
    
});

function jqCheckAll( id, name ) {
    $("input[name^=" + name + "][type='checkbox']").attr('checked', $('#' + id).is(':checked'));
}

/**
 * checkboxIds - ID vsech zavyslich ceckboxů
 * elemClass - zavislé prvky mají třídu
 */
function onlyWithCheckbox(checkboxIds, elemClass) { 
    $("input[id^=" + checkboxIds + "]").change(function (){
        var isChecked = false;
        $("input[id^=" + checkboxIds + "]").each(function(){
            if(this.checked){
                isChecked = true;
                return false;//konec cyklu
            }
        });
        if(isChecked)
            $("." + elemClass).removeClass("disabled").removeAttr("disabled");
        else
            $("." + elemClass).addClass("disabled").attr("disabled", "disabled");
    });
}