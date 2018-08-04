$(function () {
    $(".allDependend-bank, .allDependend-cash").addClass("disabled").attr("disabled", "disabled");
    onlyWithCheckbox("chits-bank", "allDependend-bank");
    onlyWithCheckbox("chits-cash", "allDependend-cash");

    //CALC
    var priceInputOut = $('#form-out-price');

    //reurn bool
    function isNumber(val) {
        return /^-?((\d+\.?\d?)|(\.\d+))$/.test(val);
    }

    //method onchange on form
    function priceControl(obj) {
        var textPlace = obj.parent().prev().children().after('<span> </span>').next();
        obj.change(
            function () {
                var val = this.value.trim(), endIndex, isError = false;
                if (val.indexOf("(") > -1 || val.indexOf(")") > -1) {
                    alert("Cena obsahuje závorky, které však nejsou platnými znaky.");
                }
                while (1) {
                    endIndex = val.length - 1;
                    if (isNumber(val.charAt(endIndex)) || val.length < 1) {
                        if (isError)
                            alert("Cena byla upravena, aby končila číslem");
                        break;
                    }
                    else {
                        val = val.substr(0, endIndex);
                        obj.val(val);
                        isError = true;
                    }
                }
            }
        )
    }

    if (priceInputOut.length !== 0) {
        priceControl(priceInputOut);
    }
});

function initAutoComplete() {
    $('[data-autocomplete]').each(function () {
        $(this).typeahead({source: $(this).data('autocomplete')});
    });
}

$(document).ready(function () {
    $.nette.ext({
        load: initAutoComplete,
        init: initAutoComplete,
    });
});
