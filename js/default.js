$(function(){
	

        //Autocompleter
        var text = {$autoCompleter};
        $('#'+{$formIn['komu']->htmlId}).autocomplete(text);
        $('#'+{$formOut['komu']->htmlId}).autocomplete(text);


        //AJAX FORMS
        // odeslání na formulářích
        $("form").submit(function () {
                $(this).ajaxSubmit();
                return false;
        });

        // odeslání pomocí tlačítek
        $("form :submit").click(function () {
                $(this).ajaxSubmit();
                return false;
        });

        //CALC
        var priceInputIn = $('#' + {$formIn['price']->htmlId});
        var priceInputOut = $('#' + {$formOut['price']->htmlId});
        priceInputIn.parent().after('</td><td id="calc"> </div>');
        priceInputOut.parent().after('</td><td id="calc2"> </div>');

        //reurn bool
        function isNumber(val) {
            return /^-?((\d+\.?\d?)|(\.\d+))$/.test(val);
        }
        //method onchange on form
        function priceControl(obj){
            obj.change(
                function () {
                    var val = this.value.trim(), endIndex, isError = false;

                    while(1) {
                        endIndex = val.length-1;
                        if(isNumber(val.charAt(endIndex)) || val.length < 1){
                            if(isError)
                                alert("Cena byla upravena, aby končila číslem");
                            break;
                        }
                        else {
                            val = val.substr(0, endIndex);
                            obj.val(val);
                            isError = true;
                        }
                    }
                    if(val.length < 1)
                        $('#calc').html('');
                    else
                        $('#calc').html(' = : '+eval(val));
                }
            )
        }

        priceControl(priceInputIn);
        priceControl(priceInputOut);


    })

