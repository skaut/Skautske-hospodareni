$(document).ready(function() {
    //Combobox
    $( ".combobox" ).combobox(); //nejde předávat parametry
    
    //fancybox2
    $(".fancybox").fancybox();
    
    
    
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