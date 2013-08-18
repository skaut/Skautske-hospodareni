/**
 * Vychází z ajaxForm
 */

jQuery.fn.extend({
    offlineSubmit: function(callback) {
        var form;
        var sendValues = {};

        // submit button
        if (this.is(":submit")) {
            form = this.parents("form");
//            sendValues[this.attr("name")] = this.val() || "";
        } else if (this.is("form")) {// form
            form = this;
        } else {// invalid element, do nothing
            return null;
        }
        // validation
        if (form.get(0).onsubmit && !form.get(0).onsubmit())
            return null;
        // get values
        var values = form.serializeArray();

        for (var i = 0; i < values.length; i++) {
            var name = values[i].name;
            // multi
            if (name in sendValues) {
                var val = sendValues[name];
                if (!(val instanceof Array)) {
                    val = [val];
                }
                val.push(values[i].value);
                sendValues[name] = val;
            } else {
                sendValues[name] = values[i].value;
            }
        }
//        console.log(sendValues);
        var msgBlock;
        sendValues['priceText'] = sendValues['price'];
        sendValues['price'] = eval(sendValues['price']);
        if (sendValues['date'] !== "" && sendValues['purpose'] !== "" && sendValues['category'] !== "") {
            form.get(0).reset();
            queueSave(sendValues);
            msgBlock = $('#addOk');
        } else {
            msgBlock = $('#addBad');
        }
        $('.offlineMsg').hide();
        msgBlock.slideDown(500).delay(5000).slideUp(1000);

//        // send ajax request
//        var ajaxOptions = {
//            url: form.attr("action"),
//            data: sendValues,
//            type: form.attr("method") || "get"
//        };
//
//        if (callback) {
//            ajaxOptions.success = callback;
//        }
// 
//		return jQuery.ajax(ajaxOptions);
    }
});

function queueSave(msg) {
    var queue = queueLoad();
    queue.push(msg);
    localStorage['queue'] = JSON.stringify(queue);
}

function queueLoad() {
    return (typeof localStorage['queue'] === 'undefined') ? [] : JSON.parse(localStorage['queue']);
}

function online() {
}
function offline() {
}


function offlineReady() {
//    if (!navigator.onLine) {
//    }
    //test prohlizece
    if (!navigator.geolocation || !(('localStorage' in window) && window['localStorage'] !== null) || !window.applicationCache || !window.JSON) {
//        var e = document.getElementById('nojs');
//        e.style.display = "none";
    } else {
        var e = document.getElementById('nojs').style.display = "none";
        e = document.getElementById('js').style.display = "block";
        $('#topBar').hide();
        $('footer').hide();
        document.body.addEventListener('online', online, false);
        document.body.addEventListener('offline', offline, false);
        document.body.ononline = online;
        document.body.onoffline = offline;
    }
}